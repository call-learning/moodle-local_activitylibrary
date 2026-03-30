<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for local utils.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\local;

use local_activitylibrary\customfield\coursemodule_handler;
use local_activitylibrary\test\testcase;

/**
 * Unit tests for utils.
 */
final class utils_test extends testcase {
    /**
     * Reset hidden fields static cache.
     */
    private function reset_hiddenfields_cache(): void {
        $property = new \ReflectionProperty(utils::class, 'hiddenfields');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Test handler component helpers.
     *
     * @covers \local_activitylibrary\local\utils::get_handler_full_component
     * @covers \local_activitylibrary\local\utils::get_hidden_filter_config_name
     */
    public function test_handler_component_helpers(): void {
        $handler = coursemodule_handler::create();

        $this->assertEquals('local_activitylibrary_coursemodule', utils::get_handler_full_component($handler));
        $this->assertEquals(
            'filter_hidden_local_activitylibrary_coursemodule',
            utils::get_hidden_filter_config_name($handler)
        );
    }

    /**
     * Test hidden fields retrieval returns empty when no config exists.
     *
     * @covers \local_activitylibrary\local\utils::get_hidden_fields_filters
     */
    public function test_get_hidden_fields_filters_returns_empty_without_config(): void {
        $handler = coursemodule_handler::create();
        $this->reset_hiddenfields_cache();

        $hidden = utils::get_hidden_fields_filters($handler);
        $this->assertSame([], $hidden);
    }

    /**
     * Test catalog URL helper returns expected local URL.
     *
     * @covers \local_activitylibrary\local\utils::get_catalog_url
     */
    public function test_get_catalog_url_returns_local_activitylibrary_url(): void {
        [$text, $url] = utils::get_catalog_url();

        $this->assertNotEmpty($text);
        $this->assertInstanceOf(\moodle_url::class, $url);
        $this->assertStringContainsString('/local/activitylibrary/index.php', $url->out(false));
    }

    /**
     * Test configured hidden course IDs are parsed from CSV.
     *
     * @covers \local_activitylibrary\local\utils::get_hidden_course_ids
     */
    public function test_get_hidden_course_ids_from_csv_config(): void {
        set_config('hiddencoursesid', ' 12, 7,0,12, abc, 4 ', 'local_activitylibrary');

        $hiddenids = utils::get_hidden_course_ids();

        $this->assertSame([12, 7, 4], $hiddenids);
    }

    /**
     * Test hidden activity IDs are read from the dedicated visibility table.
     *
     * @covers \local_activitylibrary\local\utils::get_hidden_activity_ids
     * @covers \local_activitylibrary\local\utils::set_activity_hidden_from_catalogue
     */
    public function test_get_hidden_activity_ids_from_visibility_table(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $activity1 = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Hidden 1',
        ] + $this->get_simple_cf_data()));
        $activity2 = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Hidden 2',
        ] + $this->get_simple_cf_data()));

        $cm1 = get_coursemodule_from_instance('label', $activity1->id, $course->id, false, MUST_EXIST);
        $cm2 = get_coursemodule_from_instance('label', $activity2->id, $course->id, false, MUST_EXIST);

        utils::set_activity_hidden_from_catalogue((int)$cm1->id, true);
        utils::set_activity_hidden_from_catalogue((int)$cm2->id, true);

        $hiddenids = utils::get_hidden_activity_ids();

        sort($hiddenids);
        $this->assertSame([(int)$cm1->id, (int)$cm2->id], $hiddenids);
    }

    /**
     * Test hidden activity status records use class constants and are removed when unhidden.
     *
     * @covers \local_activitylibrary\local\utils::set_activity_hidden_from_catalogue
     * @covers \local_activitylibrary\local\utils::is_activity_hidden_from_catalogue
     */
    public function test_set_activity_hidden_from_catalogue_persists_and_removes_status_record(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $activity = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Visibility status target',
        ] + $this->get_simple_cf_data()));
        $cm = get_coursemodule_from_instance('label', $activity->id, $course->id, false, MUST_EXIST);

        utils::set_activity_hidden_from_catalogue((int)$cm->id, true);

        $record = $DB->get_record('local_activitylibrary_status', ['coursemoduleid' => $cm->id], '*', MUST_EXIST);
        $this->assertSame(utils::ITEM_HIDDEN, (int)$record->visibility);
        $this->assertTrue(utils::is_activity_hidden_from_catalogue((int)$cm->id));

        utils::set_activity_hidden_from_catalogue((int)$cm->id, false);

        $this->assertFalse($DB->record_exists('local_activitylibrary_status', ['coursemoduleid' => $cm->id]));
        $this->assertFalse(utils::is_activity_hidden_from_catalogue((int)$cm->id));
    }

    /**
     * Test available activity tags only expose tags from visible activities in scope.
     *
     * @covers \local_activitylibrary\local\utils::get_available_activity_tags
     */
    public function test_get_available_activity_tags(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $activity = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Tagged activity',
        ] + $this->get_simple_cf_data()));
        $cm = get_coursemodule_from_instance('label', $activity->id, $course->id, false, MUST_EXIST);

        \core_tag_tag::add_item_tag('core', 'course_modules', $cm->id, \context_module::instance($cm->id), 'Visible tag');

        $tags = utils::get_available_activity_tags();

        $this->assertContains('Visible tag', $tags);

        utils::set_activity_hidden_from_catalogue((int)$cm->id, true);
        $tags = utils::get_available_activity_tags();
        $this->assertNotContains('Visible tag', $tags);
    }
}

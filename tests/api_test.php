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
 * Tests for activitylibraryfields in courses and modules
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary;
use local_activitylibrary\external\get_filtered_activities;
use local_activitylibrary_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/activitylibrary/tests/lib.php');

/**
 * Tests for externallib static functions
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api_test extends local_activitylibrary_testcase {

    /**
     * Test that we can retrieve activities for a given course.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_simple(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course([
            'shortname' => 'SN',
            'fullname' => 'FN',
            'summary' => 'DESC',
            'summaryformat' => FORMAT_MOODLE,
        ]);

        $activitydata = [
            'course' => $course->id,
            'name' => 'Activity 1',
            'intro' => 'Description',
            'idnumber' => 'ACT1',
            'visible' => 1,
        ] + $this->get_simple_cf_data();
        $dg->create_module('label', (object)$activitydata);

        $activities = get_filtered_activities::execute([$course->id]);
        $this->assertCount(1, $activities);
        $first = reset($activities);
        $this->assertEquals('Activity 1', $first['fullname']);
        $this->assertEquals($course->id, $first['parentid']);
    }

    /**
     * Test activity filters by module type and fulltext.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_modname_and_fulltext_filters(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Alpha label',
        ] + $this->get_simple_cf_data()));
        $dg->create_module('page', (object)([
            'course' => $course->id,
            'name' => 'Alpha page',
        ] + $this->get_simple_cf_data()));

        $activities = get_filtered_activities::execute(
            [$course->id],
            [
                ['type' => 'modname', 'operator' => 0, 'value' => 'label'],
                ['type' => 'fulltext', 'operator' => 0, 'value' => 'alpha'],
            ]
        );

        $this->assertCount(1, $activities);
        $first = reset($activities);
        $this->assertEquals('label', $first['modname']);
        $this->assertEquals('Alpha label', $first['fullname']);
    }

    /**
     * Test sorting and pagination on retrieved activities.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_sorting_and_pagination(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        foreach (['Activity C', 'Activity A', 'Activity B'] as $name) {
            $dg->create_module('label', (object)([
                'course' => $course->id,
                'name' => $name,
            ] + $this->get_simple_cf_data()));
        }

        $activities = get_filtered_activities::execute(
            [$course->id],
            [],
            2,
            1,
            [['column' => 'fullname', 'order' => 'ASC']]
        );

        $this->assertCount(2, $activities);
        $this->assertEquals('Activity B', $activities[0]['fullname']);
        $this->assertEquals('Activity C', $activities[1]['fullname']);
    }

    /**
     * Test that invalid sort entries are ignored in SQL.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::get_sort_options_sql
     */
    public function test_get_sort_options_sql_ignores_invalid_entries(): void {
        $method = new \ReflectionMethod(get_filtered_activities::class, 'get_sort_options_sql');
        $method->setAccessible(true);

        $sortsql = $method->invoke(
            null,
            [
                ['column' => 'fullname', 'order' => 'asc'],
                ['column' => 'invalidcolumn', 'order' => 'DESC'],
                ['column' => 'modname', 'order' => 'DESC'],
                ['column' => 'timemodified', 'order' => 'INVALID'],
            ],
            ['fullname', 'modname', 'timemodified']
        );

        $this->assertEquals('fullname ASC,modname DESC', $sortsql);
    }

}

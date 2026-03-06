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
 * Tests for get_filtered_activities external API.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary\external;

use core_external\external_api;
use local_activitylibrary\test\testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Tests for get_filtered_activities external API.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_filtered_activities_test extends testcase {
    /**
     * Helper.
     *
     * @param mixed ...$params
     * @return mixed
     */
    protected function get_filtered_activities(...$params) {
        $activities = get_filtered_activities::execute(...$params);
        return external_api::clean_returnvalue(get_filtered_activities::execute_returns(), $activities);
    }

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

        $activities = $this->get_filtered_activities([$course->id]);
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

        $activities = $this->get_filtered_activities(
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

        $activities = $this->get_filtered_activities(
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

    /**
     * Test catalogue visibility with role, enrolment scope, and availability constraints.
     *
     * @param string $viewer
     * @param bool $useemptycoursescope
     * @param array $enrolledcourses
     * @param array $expectednames
     * @dataProvider visibility_catalogue_provider
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_visibility_catalogue(
        string $viewer,
        bool $useemptycoursescope,
        array $enrolledcourses,
        array $expectednames
    ): void {
        $dg = $this->getDataGenerator();
        $course1 = $dg->create_course(['shortname' => 'C1']);
        $course2 = $dg->create_course(['shortname' => 'C2']);
        $coursemap = [
            'C1' => $course1->id,
            'C2' => $course2->id,
        ];

        $dg->create_module('label', (object)([
            'course' => $course1->id,
            'name' => 'C1 visible',
            'visible' => 1,
        ] + $this->get_simple_cf_data()));
        $dg->create_module('label', (object)([
            'course' => $course2->id,
            'name' => 'C2 visible',
            'visible' => 1,
        ] + $this->get_simple_cf_data()));

        $dg->create_module('label', (object)([
            'course' => $course1->id,
            'name' => 'C1 hidden',
            'visible' => 0,
        ] + $this->get_simple_cf_data()));

        set_config('enableavailability', 1);
        $dg->create_module('label', (object)([
            'course' => $course1->id,
            'name' => 'C1 future',
            'visible' => 1,
            'availability' => json_encode(\core_availability\tree::get_root_json([
                \availability_date\condition::get_json(\availability_date\condition::DIRECTION_FROM, time() + DAYSECS),
            ])),
        ] + $this->get_simple_cf_data()));

        if ($viewer === 'student') {
            $student = $dg->create_user();
            foreach ($enrolledcourses as $courseshortname) {
                if (isset($coursemap[$courseshortname])) {
                    $dg->enrol_user($student->id, $coursemap[$courseshortname], 'student');
                }
            }
            $this->setUser($student);
        } else {
            $this->setAdminUser();
        }

        \course_modinfo::clear_instance_cache();

        $courseids = $useemptycoursescope ? [] : [$course1->id, $course2->id];
        $activities = $this->get_filtered_activities($courseids);
        $returnednames = array_column($activities, 'fullname');
        sort($returnednames);
        sort($expectednames);
        $this->assertSame($expectednames, $returnednames);
    }

    /**
     * Data provider for catalogue visibility scenarios.
     *
     * @return array
     */
    public static function visibility_catalogue_provider(): array {
        return [
            'Admin with explicit scope sees visible activities in both courses' => [
                'admin',
                false,
                [],
                ['C1 future', 'C1 visible', 'C2 visible'],
            ],
            'Admin with empty scope sees visible activities in all courses' => [
                'admin',
                true,
                [],
                ['C1 future', 'C1 visible', 'C2 visible'],
            ],
            'Student enrolled in one course only sees that course visible activities' => [
                'student',
                true,
                ['C1'],
                ['C1 visible'],
            ],
            'Student enrolled in both courses still does not see hidden or future activities' => [
                'student',
                true,
                ['C1', 'C2'],
                ['C1 visible', 'C2 visible'],
            ],
        ];
    }

    /**
     * Test customfield filters by type.
     *
     * @param string $shortname
     * @param mixed $matchingvalue
     * @param mixed $nonmatchingvalue
     * @param string $filtervalue
     * @dataProvider customfield_filter_provider
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_customfield_filters(
        string $shortname,
        $matchingvalue,
        $nonmatchingvalue,
        string $filtervalue
    ): void {
        if ($shortname === 'f4' && !\local_activitylibrary\local\utils::is_multiselect_installed()) {
            $this->markTestSkipped('Multiselect customfield is not installed.');
        }

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $matchdata = $this->get_simple_cf_data();
        $nonmatchdata = $this->get_simple_cf_data();

        if ($shortname === 'f6') {
            $matchdata['customfield_f6_editor'] = ['text' => (string)$matchingvalue, 'format' => FORMAT_HTML];
            $nonmatchdata['customfield_f6_editor'] = ['text' => (string)$nonmatchingvalue, 'format' => FORMAT_HTML];
        } else {
            $matchdata['customfield_' . $shortname] = $matchingvalue;
            $nonmatchdata['customfield_' . $shortname] = $nonmatchingvalue;
        }

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Match',
        ] + $matchdata));
        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'No match',
        ] + $nonmatchdata));

        $activities = $this->get_filtered_activities(
            [$course->id],
            [[
                'type' => 'customfield',
                'shortname' => $shortname,
                'operator' => 1,
                'value' => $filtervalue,
            ]]
        );

        $this->assertCount(1, $activities);
        $this->assertEquals('Match', $activities[0]['fullname']);
    }

    /**
     * Data provider for customfield filters.
     *
     * @return array
     */
    public static function customfield_filter_provider(): array {
        return [
            'text f1' => ['f1', 'needle text', 'other text', 'needle'],
            'checkbox f2' => ['f2', 1, 0, '1'],
            'date f3' => ['f3', 1735689600, 946684800, '1,1,1,2024'],
            'select f5' => ['f5', 2, 1, '2'],
            'textarea f6' => ['f6', 'needle body', 'unrelated body', 'needle'],
            'multiselect f4' => ['f4', [1, 2], [3], '2'],
        ];
    }
}

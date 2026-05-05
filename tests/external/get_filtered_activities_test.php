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
        $result = get_filtered_activities::execute(...$params);
        return external_api::clean_returnvalue(get_filtered_activities::execute_returns(), $result);
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

        $result = $this->get_filtered_activities([$course->id]);
        $activities = $result['entities'];
        $this->assertCount(1, $activities);
        $this->assertSame(1, $result['totalcount']);
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
            'intro' => 'Label intro',
        ] + $this->get_simple_cf_data()));
        $dg->create_module('page', (object)([
            'course' => $course->id,
            'name' => 'Alpha page',
            'intro' => 'Special keyword in page description',
        ] + $this->get_simple_cf_data()));

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                ['type' => 'modname', 'operator' => 0, 'value' => 'label'],
                ['type' => 'fulltext', 'operator' => 0, 'value' => 'alpha'],
            ]
        );
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $first = reset($activities);
        $this->assertEquals('label', $first['modname']);
        $this->assertEquals('Alpha label', $first['fullname']);

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                ['type' => 'fulltext', 'operator' => 0, 'value' => 'keyword'],
            ]
        );
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $first = reset($activities);
        $this->assertEquals('page', $first['modname']);
        $this->assertEquals('Alpha page', $first['fullname']);
    }

    /**
     * Test fulltext search is case-insensitive for multibyte text.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_fulltext_filter_is_case_insensitive_for_utf8(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Énergie solaire',
            'intro' => 'Présentation générale',
        ] + $this->get_simple_cf_data()));

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                ['type' => 'fulltext', 'operator' => 0, 'value' => 'éNERGIE'],
            ]
        );
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertSame('Énergie solaire', $activities[0]['fullname']);
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

        $result = $this->get_filtered_activities(
            [$course->id],
            [],
            2,
            1,
            [['column' => 'fullname', 'order' => 'ASC']]
        );
        $activities = $result['entities'];

        $this->assertCount(2, $activities);
        $this->assertSame(3, $result['totalcount']);
        $this->assertEquals('Activity B', $activities[0]['fullname']);
        $this->assertEquals('Activity C', $activities[1]['fullname']);
    }

    /**
     * Test hidden courses and hidden activities are excluded.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_honours_hidden_ids_settings(): void {
        $dg = $this->getDataGenerator();
        $coursehidden = $dg->create_course(['fullname' => 'Hidden course']);
        $coursevisible = $dg->create_course(['fullname' => 'Visible course']);

        $dg->create_module('label', (object)([
            'course' => $coursehidden->id,
            'name' => 'Activity in hidden course',
        ] + $this->get_simple_cf_data()));

        $hiddenactivity = $dg->create_module('label', (object)([
            'course' => $coursevisible->id,
            'name' => 'Hidden activity',
        ] + $this->get_simple_cf_data()));

        $visibleactivity = $dg->create_module('label', (object)([
            'course' => $coursevisible->id,
            'name' => 'Visible activity',
        ] + $this->get_simple_cf_data()));

        $hiddencm = get_coursemodule_from_instance('label', $hiddenactivity->id, $coursevisible->id, false, MUST_EXIST);
        $visiblecm = get_coursemodule_from_instance('label', $visibleactivity->id, $coursevisible->id, false, MUST_EXIST);

        set_config('hiddencoursesid', (string)$coursehidden->id, 'local_activitylibrary');
        \local_activitylibrary\local\utils::set_activity_hidden_from_catalogue((int)$hiddencm->id, true);

        $result = $this->get_filtered_activities([$coursehidden->id, $coursevisible->id]);
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertSame(1, $result['totalcount']);
        $this->assertEquals('Visible activity', $activities[0]['fullname']);
        $this->assertEquals($coursevisible->id, $activities[0]['parentid']);
        $this->assertSame((int)$visiblecm->id, (int)$activities[0]['id']);
    }

    /**
     * Test activities whose module type is disabled site-wide are excluded.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_excludes_disabled_module_types(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $label = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Disabled module type activity',
            'visible' => 1,
        ] + $this->get_simple_cf_data()));
        $page = $dg->create_module('page', (object)([
            'course' => $course->id,
            'name' => 'Available module type activity',
            'visible' => 1,
        ] + $this->get_simple_cf_data()));

        $labelcm = get_coursemodule_from_instance('label', $label->id, $course->id, false, MUST_EXIST);
        $pagecm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);

        $DB->set_field('modules', 'visible', 0, ['name' => 'label']);
        \course_modinfo::clear_instance_cache();

        $result = $this->get_filtered_activities([$course->id]);
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertSame(1, $result['totalcount']);
        $this->assertSame('Available module type activity', $activities[0]['fullname']);
        $this->assertSame('page', $activities[0]['modname']);
        $this->assertSame((int)$pagecm->id, (int)$activities[0]['id']);
        $this->assertNotSame((int)$labelcm->id, (int)$activities[0]['id']);
    }

    /**
     * Test an empty scoped catalogue still returns the expected external structure.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_returns_empty_result_when_all_scoped_courses_are_hidden(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['fullname' => 'Only hidden course']);
        $student = $dg->create_user();
        $dg->enrol_user($student->id, $course->id, 'student');

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Hidden by course scope',
            'visible' => 1,
        ] + $this->get_simple_cf_data()));

        set_config('hiddencoursesid', (string)$course->id, 'local_activitylibrary');
        $this->setUser($student);
        \course_modinfo::clear_instance_cache();

        $result = $this->get_filtered_activities([]);

        $this->assertSame([], $result['entities']);
        $this->assertSame(0, $result['totalcount']);
    }

    /**
     * Test activity filters by tags.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_tags_filter(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $taggedactivity = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Tagged activity',
        ] + $this->get_simple_cf_data()));
        $otheractivity = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Other activity',
        ] + $this->get_simple_cf_data()));

        $taggedcm = get_coursemodule_from_instance('label', $taggedactivity->id, $course->id, false, MUST_EXIST);
        $othercm = get_coursemodule_from_instance('label', $otheractivity->id, $course->id, false, MUST_EXIST);

        \core_tag_tag::add_item_tag('core', 'course_modules', $taggedcm->id, \context_module::instance($taggedcm->id), 'Important');
        \core_tag_tag::add_item_tag('core', 'course_modules', $othercm->id, \context_module::instance($othercm->id), 'Secondary');

        $tagid = $DB->get_field('tag', 'id', ['rawname' => 'Important'], MUST_EXIST);

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                ['type' => 'tags', 'operator' => 0, 'value' => (string)$tagid],
            ]
        );
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertSame(1, $result['totalcount']);
        $this->assertSame('Tagged activity', $activities[0]['fullname']);
        $this->assertSame((int)$taggedcm->id, (int)$activities[0]['id']);
    }

    /**
     * Test multiple selected tags are combined with OR semantics.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_tags_filter_with_multiple_values_returns_union(): void {
        global $DB;

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $activityone = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Tagged one',
        ] + $this->get_simple_cf_data()));
        $activitytwo = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Tagged two',
        ] + $this->get_simple_cf_data()));
        $activitythree = $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Tagged three',
        ] + $this->get_simple_cf_data()));

        $cmone = get_coursemodule_from_instance('label', $activityone->id, $course->id, false, MUST_EXIST);
        $cmtwo = get_coursemodule_from_instance('label', $activitytwo->id, $course->id, false, MUST_EXIST);
        $cmthree = get_coursemodule_from_instance('label', $activitythree->id, $course->id, false, MUST_EXIST);

        \core_tag_tag::add_item_tag('core', 'course_modules', $cmone->id, \context_module::instance($cmone->id), 'Important');
        \core_tag_tag::add_item_tag('core', 'course_modules', $cmtwo->id, \context_module::instance($cmtwo->id), 'Secondary');
        \core_tag_tag::add_item_tag('core', 'course_modules', $cmthree->id, \context_module::instance($cmthree->id), 'Other');

        $importanttagid = $DB->get_field('tag', 'id', ['rawname' => 'Important'], MUST_EXIST);
        $secondarytagid = $DB->get_field('tag', 'id', ['rawname' => 'Secondary'], MUST_EXIST);

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                ['type' => 'tags', 'operator' => 0, 'value' => $importanttagid . ',' . $secondarytagid],
            ]
        );

        $activities = $result['entities'];
        $returnednames = array_column($activities, 'fullname');
        sort($returnednames);

        $this->assertCount(2, $activities);
        $this->assertSame(2, $result['totalcount']);
        $this->assertSame(['Tagged one', 'Tagged two'], $returnednames);
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
        $result = $this->get_filtered_activities($courseids);
        $activities = $result['entities'];
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

        $result = $this->get_filtered_activities(
            [$course->id],
            [[
                'type' => 'customfield',
                'shortname' => $shortname,
                'operator' => 1,
                'value' => $filtervalue,
            ]]
        );
        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertEquals('Match', $activities[0]['fullname']);
    }

    /**
     * Test multiple selected values on a multiselect customfield return the union of matches.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_multiselect_filter_with_multiple_values_returns_union(): void {
        if (!\local_activitylibrary\local\utils::is_multiselect_installed()) {
            $this->markTestSkipped('Multiselect customfield is not installed.');
        }

        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $firstdata = $this->get_simple_cf_data();
        $firstdata['customfield_f4'] = [1];
        $seconddata = $this->get_simple_cf_data();
        $seconddata['customfield_f4'] = [2];
        $thirddata = $this->get_simple_cf_data();
        $thirddata['customfield_f4'] = [3];

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Match one',
        ] + $firstdata));
        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Match two',
        ] + $seconddata));
        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'No match',
        ] + $thirddata));

        $result = $this->get_filtered_activities(
            [$course->id],
            [[
                'type' => 'customfield',
                'shortname' => 'f4',
                'operator' => 1,
                'value' => '1,2',
            ]]
        );

        $activities = $result['entities'];
        $returnednames = array_column($activities, 'fullname');
        sort($returnednames);

        $this->assertCount(2, $activities);
        $this->assertSame(['Match one', 'Match two'], $returnednames);
    }

    /**
     * Test different filters are combined with AND semantics.
     *
     * @covers \local_activitylibrary\external\get_filtered_activities::execute
     * @runInSeparateProcess
     */
    public function test_get_filtered_activities_different_filters_are_combined_with_and(): void {
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();

        $matchdata = $this->get_simple_cf_data();
        $matchdata['customfield_f1'] = 'alpha text';
        $matchdata['customfield_f5'] = 2;

        $wrongselectdata = $this->get_simple_cf_data();
        $wrongselectdata['customfield_f1'] = 'alpha text';
        $wrongselectdata['customfield_f5'] = 1;

        $wrongtextdata = $this->get_simple_cf_data();
        $wrongtextdata['customfield_f1'] = 'beta text';
        $wrongtextdata['customfield_f5'] = 2;

        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Match both filters',
        ] + $matchdata));
        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Matches text only',
        ] + $wrongselectdata));
        $dg->create_module('label', (object)([
            'course' => $course->id,
            'name' => 'Matches select only',
        ] + $wrongtextdata));

        $result = $this->get_filtered_activities(
            [$course->id],
            [
                [
                    'type' => 'customfield',
                    'shortname' => 'f1',
                    'operator' => 1,
                    'value' => 'alpha',
                ],
                [
                    'type' => 'customfield',
                    'shortname' => 'f5',
                    'operator' => 1,
                    'value' => '2',
                ],
            ]
        );

        $activities = $result['entities'];

        $this->assertCount(1, $activities);
        $this->assertSame(1, $result['totalcount']);
        $this->assertSame('Match both filters', $activities[0]['fullname']);
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

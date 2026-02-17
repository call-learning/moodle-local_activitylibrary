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

        $activities = get_filtered_activities::execute($course->id);
        $this->assertCount(1, $activities);
        $first = reset($activities);
        $this->assertEquals('Activity 1', $first['fullname']);
        $this->assertEquals($course->id, $first['parentid']);
    }

}

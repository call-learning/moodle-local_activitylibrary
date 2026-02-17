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
 * Tests for activitylibraryfields in modules.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary;

use local_activitylibrary\customfield\coursemodule_handler;
use local_activitylibrary\locallib\utils;
use local_activitylibrary_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/activitylibrary/tests/lib.php');

/**
 * Tests for customfields in course modules.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filters_test extends local_activitylibrary_testcase {

    /**
     * Test that we can obtain a single row result for a set of fields for a module.
     * @covers \local_activitylibrary\locallib\customfield_utils::get_sql_for_entity_customfields
     */
    public function test_flat_sql_coursemodule(): void {
        global $DB;
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
        ] + $this->get_simple_cf_data();

        $activity = $dg->create_module('label', (object)$activitydata);
        $cm = get_coursemodule_from_instance('label', $activity->id, $course->id, false, MUST_EXIST);

        $sqlactivity = \local_activitylibrary\locallib\customfield_utils::get_sql_for_entity_customfields('coursemodule');
        $activityrow = $DB->get_records_sql($sqlactivity . ' WHERE e.id = :cmid', ['cmid' => $cm->id]);
        $this->assertCount(1, $activityrow);
        $this->assert_check_simple_cf_data(reset($activityrow));
    }

    /**
     * Test hidden field helpers with module handler.
     * @covers \local_activitylibrary\locallib\utils::is_field_hidden_filters
     */
    public function test_utils_get_hiddenfields_coursemodule(): void {
        $handler = coursemodule_handler::create();
        set_config(utils::get_hidden_filter_config_name($handler), 'f1', 'local_activitylibrary');
        $this->assertTrue(utils::is_field_hidden_filters($handler, 'f1'));
    }

    /**
     * Test hide field helpers with module handler.
     * @covers \local_activitylibrary\locallib\utils::hide_fields_filter
     */
    public function test_utils_set_get_hiddenfields_coursemodule(): void {
        $handler = coursemodule_handler::create();

        utils::hide_fields_filter($handler, ['f1', 'f2']);
        utils::hide_fields_filter($handler, 'f3');

        $this->assertTrue(utils::is_field_hidden_filters($handler, 'f1'));
        $this->assertTrue(utils::is_field_hidden_filters($handler, 'f2'));
        $this->assertTrue(utils::is_field_hidden_filters($handler, 'f3'));
        $this->assertFalse(utils::is_field_hidden_filters($handler, 'f5'));
    }

    /**
     * Test show field helpers with module handler.
     * @covers \local_activitylibrary\locallib\utils::show_fields_filter
     */
    public function test_utils_show_hiddenfields_coursemodule(): void {
        $handler = coursemodule_handler::create();

        utils::hide_fields_filter($handler, ['f1', 'f2', 'f3', 'f5']);

        utils::show_fields_filter($handler, 'f1');
        utils::show_fields_filter($handler, ['f3', 'f5']);

        $this->assertFalse(utils::is_field_hidden_filters($handler, 'f1'));
        $this->assertTrue(utils::is_field_hidden_filters($handler, 'f2'));
        $this->assertFalse(utils::is_field_hidden_filters($handler, 'f3'));
        $this->assertFalse(utils::is_field_hidden_filters($handler, 'f5'));
    }
}

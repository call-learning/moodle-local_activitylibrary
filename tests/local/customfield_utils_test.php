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
 * Unit tests for local customfield utils.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\local;

use local_activitylibrary\customfield\coursemodule_handler;
use local_activitylibrary\local\filters\text_filter;
use local_activitylibrary\test\testcase;

/**
 * Unit tests for customfield_utils.
 */
final class customfield_utils_test extends testcase {
    /**
     * Return a coursemodule custom field by shortname.
     *
     * @param string $shortname
     * @return \core_customfield\field_controller
     */
    private function get_coursemodule_field_by_shortname(string $shortname): \core_customfield\field_controller {
        $handler = coursemodule_handler::create();
        foreach ($handler->get_fields() as $field) {
            if ($field->get('shortname') === $shortname) {
                return $field;
            }
        }
        $this->fail('Expected customfield not found: ' . $shortname);
    }

    /**
     * Test field name normalization helper.
     *
     * @covers \local_activitylibrary\local\customfield_utils::get_field_name
     */
    public function test_get_field_name_normalizes_shortname(): void {
        $fieldname = customfield_utils::get_field_name('customfield', '  F1  ');
        $this->assertEquals('customfield_f1', $fieldname);
    }

    /**
     * Test data column lookup for a custom field.
     *
     * @covers \local_activitylibrary\local\customfield_utils::get_datafieldcolumn_value_from_field_handler
     */
    public function test_get_datafieldcolumn_value_from_field_handler(): void {
        $field = $this->get_coursemodule_field_by_shortname('f1');
        $column = customfield_utils::get_datafieldcolumn_value_from_field_handler($field);
        $this->assertNotEmpty($column);
        $this->assertIsString($column);
    }

    /**
     * Test filter factory returns the expected filter instance.
     *
     * @covers \local_activitylibrary\local\customfield_utils::get_filter_from_field
     */
    public function test_get_filter_from_field_returns_matching_filter(): void {
        $field = $this->get_coursemodule_field_by_shortname('f1');
        $filter = customfield_utils::get_filter_from_field($field);

        $this->assertInstanceOf(text_filter::class, $filter);
    }

    /**
     * Test SQL fragment generation from customfield filters.
     *
     * @covers \local_activitylibrary\local\customfield_utils::get_sql_from_filters_handler
     */
    public function test_get_sql_from_filters_handler_builds_where_and_params(): void {
        $handler = coursemodule_handler::create();
        [$sqlwhere, $sqlparams] = customfield_utils::get_sql_from_filters_handler(
            [['shortname' => 'f1', 'value' => 'needle']],
            $handler
        );

        $this->assertStringContainsString('LIKE', $sqlwhere);
        $this->assertNotEmpty($sqlparams);
        $this->assertContains('%needle%', array_values($sqlparams));
    }
}

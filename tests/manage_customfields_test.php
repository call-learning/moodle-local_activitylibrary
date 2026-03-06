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
 * Unit tests for customfield external management endpoints.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary;

use local_activitylibrary\external\manage_customfields;
use local_activitylibrary\local\utils;
use local_activitylibrary\test\testcase;


/**
 * Unit tests for manage_customfields external methods.
 */
final class manage_customfields_test extends testcase {
    /**
     * Reset static hidden fields cache between assertions.
     */
    protected function reset_hiddenfields_cache(): void {
        $property = new \ReflectionProperty(utils::class, 'hiddenfields');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Test hide/show and read back hidden field names through external endpoints.
     *
     * @covers \local_activitylibrary\external\manage_customfields::hide_fields_filter
     * @covers \local_activitylibrary\external\manage_customfields::show_fields_filter
     * @covers \local_activitylibrary\external\manage_customfields::get_hidden_fields_filters
     * @runInSeparateProcess
     */
    public function test_manage_customfields_hide_show_cycle(): void {
        $this->reset_hiddenfields_cache();

        manage_customfields::hide_fields_filter('local_activitylibrary', 'coursemodule', ['f1', 'f2']);
        $this->reset_hiddenfields_cache();
        $hidden = manage_customfields::get_hidden_fields_filters('local_activitylibrary', 'coursemodule');
        sort($hidden);
        $this->assertEquals(['f1', 'f2'], $hidden);

        manage_customfields::show_fields_filter('local_activitylibrary', 'coursemodule', ['f1']);
        $this->reset_hiddenfields_cache();
        $hidden = manage_customfields::get_hidden_fields_filters('local_activitylibrary', 'coursemodule');
        $this->assertEquals(['f2'], array_values($hidden));
    }
}

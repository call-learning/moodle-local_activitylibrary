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
 * Unit tests for lib.php functions.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_activitylibrary;

use local_activitylibrary\locallib\utils;
use local_activitylibrary\output\base_activitylibrary;
use local_activitylibrary_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/activitylibrary/tests/lib.php');

/**
 * Unit tests for plugin-level lib functions.
 */
final class lib_test extends local_activitylibrary_testcase {
    /**
     * Test that declared user preferences contain expected defaults and choices.
     *
     * @covers ::local_activitylibrary_user_preferences
     */
    public function test_local_activitylibrary_user_preferences_definition(): void {
        $preferences = \local_activitylibrary_user_preferences();

        $this->assertArrayHasKey('local_activitylibrary_user_sort_preference', $preferences);
        $this->assertArrayHasKey('local_activitylibrary_user_view_preference', $preferences);
        $this->assertArrayHasKey('local_activitylibrary_user_paging_preference', $preferences);

        $this->assertEquals(
            base_activitylibrary::SORT_FULLNAME_ASC,
            $preferences['local_activitylibrary_user_sort_preference']['default']
        );
        $this->assertContains(
            base_activitylibrary::SORT_LASTMODIF_DESC,
            $preferences['local_activitylibrary_user_sort_preference']['choices']
        );

        $this->assertEquals(
            base_activitylibrary::VIEW_CARD,
            $preferences['local_activitylibrary_user_view_preference']['default']
        );
        $this->assertContains(
            base_activitylibrary::VIEW_LIST,
            $preferences['local_activitylibrary_user_view_preference']['choices']
        );

        $this->assertEquals(
            base_activitylibrary::PAGING_15,
            $preferences['local_activitylibrary_user_paging_preference']['default']
        );
        $this->assertEquals(
            [base_activitylibrary::PAGING_15, base_activitylibrary::PAGING_25, base_activitylibrary::PAGING_50],
            $preferences['local_activitylibrary_user_paging_preference']['choices']
        );
    }

    /**
     * Test menu override parsing with multi-line language entries and course suffix.
     *
     * @covers \local_activitylibrary\locallib\utils::get_resource_library_menu_text
     */
    public function test_get_resource_library_menu_text_with_language_override(): void {
        $lang = current_language();
        set_config('menutextoverride', "English text|en\nCustom text|{$lang}", 'local_activitylibrary');

        $menutext = utils::get_resource_library_menu_text('Course Name');
        $this->assertEquals('Custom text (Course Name)', $menutext);
    }
}

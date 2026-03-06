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
 * Activity Library additional steps
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_activitylibrary\local\utils;
use Behat\Mink\Exception\ExpectationException;
use Moodle\BehatExtension\Exception\SkippedException;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions
 *
 * @package    local_activitylibrary
 * @category   test
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_activitylibrary extends behat_base {
    /**
     * Skip tagged scenarios when the multiselect customfield plugin is unavailable.
     *
     * @BeforeScenario @activitylibrary_requires_multiselect
     */
    public function skip_if_multiselect_field_is_not_installed(): void {
        if (!utils::is_multiselect_installed()) {
            throw new SkippedException('Multiselect customfield is not installed.');
        }
    }

    /**
     * Add a step to navigate to /local/activitylibrary/index.php
     *
     * @param string $coursefullname
     * @Given /^I navigate to activity library "(?P<coursefullname_string>(?:[^"]|\\")*)" page$/
     */
    public function i_navigate_to_activity_library_page(string $coursefullname) {
        $url = new moodle_url('/local/activitylibrary/index.php');
        if ($coursefullname != "Home") {
            $courseid = $this->get_course_id($coursefullname);
            $url->param('courseid', $courseid);
        }
        $this->execute('behat_general::i_visit', [$url]);
    }

    /**
     * Navigate to the custom field management page.
     *
     * @Given /^I navigate to activity library custom field management page$/
     */
    public function i_navigate_to_activity_library_custom_field_management_page() {
        $this->execute('behat_general::i_visit', [new moodle_url('/local/activitylibrary/activityfields.php')]);
    }

    /**
     * Set hidden filter state for a custom field shortname.
     *
     * @param string $shortname
     * @param string $hidden
     * @Given /^I set hidden filter of field "(?P<shortname_string>(?:[^"]|\\")*)" to "(?P<hidden_string>(?:0|1))"$/
     */
    public function i_set_hidden_filter_of_field_to(string $shortname, string $hidden) {
        $selector = 'input.hide_field_filter[data-field-shortname="' . $shortname . '"]';
        $checkbox = $this->find('css', $selector);
        $targetstate = ($hidden === '1');
        if ((bool)$checkbox->isChecked() !== $targetstate) {
            $checkbox->click();
        }
        if ((bool)$checkbox->isChecked() !== $targetstate) {
            throw new ExpectationException(
                'Unable to set hidden filter state for field shortname "' . $shortname . '"',
                $this->getSession()
            );
        }
    }

    /**
     * Hide custom field from filters by shortname.
     *
     * @param string $shortname
     * @Given /^I hide fields filter "(?P<shortname_string>(?:[^"]|\\")*)"$/
     */
    public function i_hide_fields_filter(string $shortname) {
        $this->i_set_hidden_filter_of_field_to($shortname, '1');
    }

    /**
     * Show custom field in filters by shortname.
     *
     * @param string $shortname
     * @Given /^I show fields filter "(?P<shortname_string>(?:[^"]|\\")*)"$/
     */
    public function i_show_fields_filter(string $shortname) {
        $this->i_set_hidden_filter_of_field_to($shortname, '0');
    }
}

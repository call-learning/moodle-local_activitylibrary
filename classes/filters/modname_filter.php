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
 * Module name static filter.
 *
 * @package   local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\filters;

/**
 * Generic module type filter.
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modname_filter implements activitylibrary_filter_interface, static_filter_interface {
    /**
     * Add to form.
     *
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function add_to_form(\MoodleQuickForm &$mform) {
        global $DB;

        $choices = ['' => get_string('filter:anyvalue', 'local_activitylibrary')];
        $stringmanager = get_string_manager();
        $modulenames = $DB->get_fieldset_select('modules', 'name', '', null, 'name ASC');
        foreach ($modulenames as $modname) {
            $component = 'mod_' . $modname;
            $label = $stringmanager->string_exists('modulename', $component)
                ? get_string('modulename', $component)
                : $modname;
            $choices[$modname] = $label;
        }

        utils::add_filter_operators_to_form($mform, 'modname', 'modname', self::OPERATOR_EQUAL);
        $elementname = 'modname[value]';
        $mform->addElement('select', $elementname, $this->get_label(), $choices);
        $mform->setType($elementname, PARAM_ALPHANUMEXT);
    }

    /**
     * Check data.
     *
     * @param \stdClass $formdata
     * @return false|array
     */
    public function check_data($formdata) {
        $field = 'modname';
        if (array_key_exists($field, (array)$formdata) && $formdata->$field !== '') {
            return ['value' => (string)$formdata->$field];
        }
        return false;
    }

    /**
     * Get label.
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_label() {
        return get_string('filter:modname', 'local_activitylibrary');
    }

    /**
     * Get sql filter.
     *
     * @param array|string $data
     * @return array
     */
    public function get_sql_filter($data) {
        $modname = clean_param((string)$data, PARAM_ALPHANUMEXT);
        return $modname !== '' ? ['m.name = :filtermodname', ['filtermodname' => $modname]] : [null, null];
    }
}

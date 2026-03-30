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
 * Activity tags static filter.
 *
 * @package   local_activitylibrary
 * @copyright  2026 CALL Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\local\filters;

use local_activitylibrary\local\utils as activitylibrary_utils;

/**
 * Generic activity tags filter.
 */
class tags_filter implements activitylibrary_filter_interface, static_filter_interface {
    /**
     * Add to form.
     *
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function add_to_form(\MoodleQuickForm &$mform) {
        $choices = activitylibrary_utils::get_available_activity_tags();
        if (empty($choices)) {
            return;
        }

        utils::add_filter_operators_to_form($mform, 'tags', 'tags', self::OPERATOR_EQUAL);
        $elementname = 'tags[value]';
        $mform->addElement(
            'autocomplete',
            $elementname,
            $this->get_label(),
            $choices,
            [
                'multiple' => true,
                'noselectionstring' => get_string('filter:anyvalue', 'local_activitylibrary'),
            ]
        );
        $mform->setType($elementname, PARAM_INT);
    }

    /**
     * Check data.
     *
     * @param \stdClass $formdata
     * @return false|array
     */
    public function check_data($formdata) {
        $field = 'tags';
        if (!array_key_exists($field, (array)$formdata) || $formdata->$field === '') {
            return false;
        }

        return ['value' => (string)$formdata->$field];
    }

    /**
     * Get label.
     *
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function get_label() {
        return get_string('filter:tags', 'local_activitylibrary');
    }

    /**
     * Static filter SQL is handled in the external query.
     *
     * @param array|string $data
     * @return array
     */
    public function get_sql_filter($data) {
        return [null, null];
    }
}

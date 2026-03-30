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

namespace local_activitylibrary\admin;

use core_admin\local\settings\autocomplete;

/**
 * Multi-course autocomplete setting stored as CSV in config.
 *
 * @package    local_activitylibrary
 * @copyright  2026 CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setting_hidden_courses extends autocomplete {
    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param array $defaultsetting
     */
    public function __construct(string $name, string $visiblename, string $description, array $defaultsetting = []) {
        parent::__construct(
            $name,
            $visiblename,
            $description,
            $defaultsetting,
            self::get_course_choices(),
            [
                'placeholder' => get_string('searchcourses'),
                'noselectionstring' => get_string('noselection', 'form'),
            ]
        );
    }

    /**
     * Build the list of selectable courses.
     *
     * @return array
     */
    protected static function get_course_choices(): array {
        global $DB;

        $courses = $DB->get_records_select(
            'course',
            'id <> :siteid',
            ['siteid' => SITEID],
            'sortorder ASC, fullname ASC, shortname ASC',
            'id, fullname, shortname'
        );

        $choices = [];
        foreach ($courses as $course) {
            $label = format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]);
            if ($course->shortname !== '' && $course->shortname !== $course->fullname) {
                $label .= ' ('
                    . format_string($course->shortname, true, ['context' => \context_course::instance($course->id)])
                    . ')';
            }
            $choices[(string) $course->id] = $label;
        }

        return $choices;
    }
}

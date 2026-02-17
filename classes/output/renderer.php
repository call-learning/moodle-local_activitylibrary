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
namespace local_activitylibrary\output;

use plugin_renderer_base;
use renderable;

/**
 * Activity Library renderer
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Return the main content for the course list
     *
     * @param course_activitylibrary $courserl The main renderable
     * @return string HTML string
     * @throws \moodle_exception
     */
    public function render_course_activitylibrary(course_activitylibrary $courserl) {
        return $this->render_from_template(
            'local_activitylibrary/activitylibrary',
            $courserl->export_for_template($this)
        );
    }

    /**
     * Return the main content for activities.
     *
     * @param activity_activitylibrary $activityrl The main renderable
     * @return string HTML string
     * @throws \moodle_exception
     */
    public function render_activity_activitylibrary(activity_activitylibrary $activityrl) {
        return $this->render_from_template(
            'local_activitylibrary/activitylibrary',
            $activityrl->export_for_template($this)
        );
    }

    /**
     * Render custom field management interface.
     *
     * @param customfield_management $list
     * @return string HTML
     * @throws \moodle_exception
     */
    protected function render_customfield_management(customfield_management $list) {
        return $this->render_from_template(
            'local_activitylibrary/customfield_management',
            $list->export_for_template($this)
        );
    }
}

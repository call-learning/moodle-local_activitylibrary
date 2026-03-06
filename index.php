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
 * Activity Library page
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
global $CFG, $PAGE, $DB, $OUTPUT, $USER;
require_once($CFG->dirroot . '/course/lib.php');

$courseid = optional_param('courseid', SITEID, PARAM_INT);
$courseidsparam = optional_param('courseids', '', PARAM_RAW_TRIMMED);
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.
if ($courseid != SITEID) {
    require_login($courseid, true); // We make sure the course exists and we can access it.
} else {
    require_login();
}

$PAGE->set_pagelayout('standard');
$pageparams = [];
$renderable = null;

$context = context_system::instance();

$courseids = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $courseidsparam))));
if ($courseid != SITEID) {
    $courseids = [$courseid];
    $pageparams['courseid'] = $courseid;
    $context = context_course::instance($courseid);
} else if (!empty($courseids)) {
    $pageparams['courseids'] = implode(',', $courseids);
}

$renderable = new local_activitylibrary\output\activity_activitylibrary($courseids);
$PAGE->add_body_class('resource-library-activities');


$site = get_site();

$stractivitylibrary = \local_activitylibrary\local\utils::get_resource_library_menu_text();

$pagedesc = $stractivitylibrary;
$title = $stractivitylibrary;

$pageurl = new moodle_url('/local/activitylibrary/index.php', $pageparams);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title($title);
if ($courseid != SITEID) {
    $PAGE->set_heading($pagedesc);
}

// Toggle the editing state and switches.
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {             // Editing state was specified.
        $USER->editing = $edit;       // Change editing state.
    }
    if (!empty($USER->editing)) {
        $edit = 1;
    } else {
        $edit = 0;
    }
    // Add button for editing page.
    if (!$PAGE->theme->haseditswitch) {
        $params['edit'] = !$edit;
        $url = new moodle_url($pageurl, $params);
        $editactionstring = !$edit ? get_string('turneditingon') : get_string('turneditingoff');
        $editbutton = $OUTPUT->single_button($url, $editactionstring);
        $PAGE->set_button( $editbutton);
    }
} else {
    $USER->editing = $edit = 0;
}

$renderer = $PAGE->get_renderer('local_activitylibrary');
echo $OUTPUT->header();

echo $renderer->render($renderable);

echo $OUTPUT->footer();

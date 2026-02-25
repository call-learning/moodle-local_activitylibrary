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
 * Manage course custom fields for metadata
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
global $CFG, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/adminlib.php');
admin_externalpage_setup('activitylibrary_customfield');

$output = $PAGE->get_renderer('local_activitylibrary');
$handler = \local_activitylibrary\customfield\coursemodule_handler::create();
$outputpage = new \local_activitylibrary\output\customfield_management($handler);

echo $OUTPUT->header(),
$OUTPUT->heading(new lang_string('activitylibrary_customfield', 'local_activitylibrary')),
$output->render($outputpage),
$OUTPUT->footer();


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

namespace local_activitylibrary\customfield;

use core_customfield\api;
use core_customfield\handler;
use core_customfield\field_controller;
use restore_activity_task;

/**
 * Course handler for custom fields
 *
 * @package local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodule_handler extends handler {
    /**
     * @var coursemodule_handler
     */
    protected static $singleton;

    /**
     * @var \context|null
     */
    protected $parentcontext;

    /** @var int Field is displayed in the course listing, visible to everybody */
    const VISIBLETOALL = 2;
    /** @var int Field is displayed in the course listing but only for teachers */
    const VISIBLETOTEACHERS = 1;
    /** @var int Field is not displayed in the course listing */
    const NOTVISIBLE = 0;

    /**
     * Returns a singleton.
     *
     * @param int $itemid
     * @return \core_customfield\handler
     */
    public static function create(int $itemid = 0): \core_customfield\handler {
        if (static::$singleton === null) {
            static::$singleton = new static(0);
        }
        return static::$singleton;
    }

    /**
     * Run reset code after unit tests to reset the singleton usage.
     */
    public static function reset_caches(): void {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('This feature is only intended for use in unit tests');
        }
        static::$singleton = null;
    }

    /**
     * Allows to add custom controls to the field configuration form that will be saved in configdata
     *
     * @param \MoodleQuickForm $mform
     * @throws \coding_exception
     */
    public function config_form_definition(\MoodleQuickForm $mform) {
        $mform->addElement(
            'header',
            'activitylibrary_coursemodule_handler_header',
            get_string('activitylibraryfieldsettings', 'local_activitylibrary')
        );
        $mform->setExpanded('activitylibrary_coursemodule_handler_header', true);

        // If field is locked.
        $mform->addElement(
            'selectyesno',
            'configdata[locked]',
            get_string('activitylibraryfield_islocked', 'local_activitylibrary')
        );
        $mform->addHelpButton('configdata[locked]', 'activitylibraryfield_islocked', 'local_activitylibrary');

        // Field data visibility.
        $visibilityoptions = [self::VISIBLETOALL =>
            get_string('activitylibraryfield_visibletoall', 'local_activitylibrary'),
            self::VISIBLETOTEACHERS =>
                get_string('activitylibraryfield_visibletoteachers', 'local_activitylibrary'),
            self::NOTVISIBLE =>
                get_string('activitylibraryfield_notvisible', 'local_activitylibrary'), ];
        $mform->addElement(
            'select',
            'configdata[visibility]',
            get_string('activitylibraryfield_visibility', 'local_activitylibrary'),
            $visibilityoptions
        );
        $mform->addHelpButton(
            'configdata[visibility]',
            'activitylibraryfield_visibility',
            'local_activitylibrary'
        );
    }

    /**
     * Creates or updates custom field data.
     *
     * @param \restore_task $task
     * @param array $data
     */
    public function restore_instance_data_from_backup(\restore_task $task, array $data) {
        /* @var $task restore_activity_task The current restore task class */
        $moduleid = $task->get_moduleid();
        $context = $this->get_instance_context($moduleid);
        $editablefields = $this->get_editable_fields($moduleid);
        $records = api::get_instance_fields_data($editablefields, $moduleid);
        $target = $task->get_target();
        $override = ($target != \backup::TARGET_CURRENT_ADDING && $target != \backup::TARGET_EXISTING_ADDING);

        foreach ($records as $d) {
            $field = $d->get_field();
            if ($field->get('shortname') === $data['shortname'] && $field->get('type') === $data['type']) {
                if (!$d->get('id') || $override) {
                    $d->set($d->datafield(), $data['value']);
                    $d->set('value', $data['value']);
                    $d->set('valueformat', $data['valueformat']);
                    $d->set('contextid', $context->id);
                    $d->save();
                }
                return;
            }
        }
    }

    /**
     * Returns the context for the data associated with the given instanceid.
     *
     * @param int $instanceid id of the record to get the context for
     * @return \context the context for the given record
     */
    public function get_instance_context(int $instanceid = 0): \context {
        if ($instanceid > 0) {
            return \context_module::instance($instanceid);
        } else {
            return \context_system::instance();
        }
    }

    /**
     * The current user can configure custom fields on this component.
     *
     * @return bool true if the current can configure custom fields, false otherwise
     */
    public function can_configure(): bool {
        return has_capability('local/activitylibrary:configurecustomfields', $this->get_configuration_context());
    }

    /**
     * The current user can edit custom fields on the given field.
     *
     * @param field_controller $field
     * @param int $instanceid id of the course to test edit permission
     * @return bool true if the current can edit custom fields, false otherwise
     */
    public function can_edit(field_controller $field, int $instanceid = 0): bool {
        if ($instanceid) {
            $context = $this->get_instance_context($instanceid);
            return (!$field->get_configdata_property('locked') ||
                has_capability('local/activitylibrary:changelockedcustomfields', $context));
        }

        $context = $this->get_parent_context();
        return (!$field->get_configdata_property('locked') ||
            guess_if_creator_will_have_course_capability('local/activitylibrary:changelockedcustomfields', $context));
    }

    /**
     * Sets parent context for the module.
     *
     * @param \context $context
     */
    public function set_parent_context(\context $context) {
        $this->parentcontext = $context;
    }

    /**
     * Context that should be used for new categories created by this handler.
     *
     * @return \context the context for configuration
     */
    public function get_configuration_context(): \context {
        return \context_system::instance();
    }

    /**
     * Activity custom fields use categories in the management UI.
     *
     * @return bool
     */
    public function uses_categories(): bool {
        return true;
    }

    /**
     * The current user can view custom fields.
     *
     * @param field_controller $field
     * @param int $instanceid id of the course to test view permission
     * @return bool true if the current can view custom fields, false otherwise
     * @throws \coding_exception
     */
    public function can_view(field_controller $field, int $instanceid): bool {
        global $USER;
        $visibility = $field->get_configdata_property('visibility');

        return ($visibility == self::NOTVISIBLE && is_primary_admin($USER->id)) ||
            has_capability('local/activitylibrary:view', $this->get_instance_context($instanceid));
    }

    /**
     * Returns the parent context for the course
     *
     * @return \context
     * @throws \dml_exception
     */
    protected function get_parent_context(): \context {
        global $PAGE;
        if ($this->parentcontext) {
            return $this->parentcontext;
        } else if ($PAGE->context && $PAGE->context instanceof \context_course) {
            return $PAGE->context;
        }
        return \context_system::instance();
    }

    /**
     * URL for configuration of the fields on this handler.
     *
     * @return \moodle_url The URL to configure custom fields for this component
     */
    public function get_configuration_url(): \moodle_url {
        return new \moodle_url('/local/activitylibrary/activityfields.php');
    }

    /**
     * Set up page customfield/edit.php
     *
     * @param field_controller $field
     * @return string page heading
     */
    public function setup_edit_page(field_controller $field): string {
        return $this->setup_edit_page_with_external($field, 'activitylibrary_coursemodule_customfield');
    }

    /**
     * Set up page customfield/edit.php.
     *
     * @param field_controller $field
     * @param string $externalpagename
     * @return string page heading
     */
    protected function setup_edit_page_with_external(field_controller $field, $externalpagename): string {
        global $CFG, $PAGE;
        require_once($CFG->libdir . '/adminlib.php');

        $title = parent::setup_edit_page($field);
        admin_externalpage_setup($externalpagename);
        $PAGE->navbar->add($title);
        return $title;
    }
}

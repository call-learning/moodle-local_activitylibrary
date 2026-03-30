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
 * Internal function and routine for the plugin
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\local;

use core_customfield\handler;
use local_activitylibrary\customfield\course_handler;
use Matrix\Exception;

/**
 * Class utils
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /** @var int Activity visible in catalogue. */
    public const ITEM_VISIBLE = 0;

    /** @var int Activity hidden from catalogue. */
    public const ITEM_HIDDEN = 1;

    /**
     * @var array $hiddenfields
     */
    private static $hiddenfields = null;

    /**
     * Parse a comma-separated config value into unique positive integer IDs.
     *
     * @param string|null $configvalue
     * @return array
     */
    public static function parse_configured_ids(?string $configvalue): array {
        if (!$configvalue) {
            return [];
        }

        $ids = preg_split('/[\s,]+/', $configvalue);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function (int $id): bool {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * Get course IDs hidden from the activity library.
     *
     * @return array
     */
    public static function get_hidden_course_ids(): array {
        return self::parse_configured_ids(get_config('local_activitylibrary', 'hiddencoursesid'));
    }

    /**
     * Get the course ids currently in activity library scope for the active user.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_catalog_scope_course_ids(): array {
        global $DB;

        if (is_siteadmin()) {
            $scopeids = $DB->get_fieldset_select('course', 'id', 'id <> :siteid', ['siteid' => SITEID]);
        } else {
            $scopeids = array_keys(enrol_get_my_courses());
        }

        if (empty($scopeids)) {
            return [];
        }

        return array_values(array_diff(array_map('intval', $scopeids), self::get_hidden_course_ids()));
    }

    /**
     * Get activity IDs hidden from the activity library.
     *
     * @return array
     */
    public static function get_hidden_activity_ids(): array {
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('local_activitylibrary_status')) {
            return [];
        }

        $records = $DB->get_records('local_activitylibrary_status', ['visibility' => self::ITEM_HIDDEN], '', 'coursemoduleid');
        $ids = array_map(function (\stdClass $record): int {
            return (int)$record->coursemoduleid;
        }, $records);

        return array_values(array_unique($ids));
    }

    /**
     * Get the selectable activity tags available in the current catalogue scope.
     *
     * @return array
     * @throws \dml_exception
     */
    public static function get_available_activity_tags(): array {
        global $DB;

        $scopeids = self::get_catalog_scope_course_ids();
        if (empty($scopeids)) {
            return [];
        }

        [$coursesql, $courseparams] = $DB->get_in_or_equal($scopeids, SQL_PARAMS_NAMED, 'tagcourse');
        $params = $courseparams + [
            'tagcomponent' => 'core',
            'tagitemtype' => 'course_modules',
        ];

        $sql = "SELECT DISTINCT t.id, t.rawname
                  FROM {tag} t
                  JOIN {tag_instance} ti
                    ON ti.tagid = t.id
                   AND ti.component = :tagcomponent
                   AND ti.itemtype = :tagitemtype
                  JOIN {course_modules} cm
                    ON cm.id = ti.itemid
                 WHERE cm.visible = 1
                   AND cm.course {$coursesql}";

        $hiddenactivityids = self::get_hidden_activity_ids();
        if (!empty($hiddenactivityids)) {
            [$hiddensql, $hiddenparams] = $DB->get_in_or_equal($hiddenactivityids, SQL_PARAMS_NAMED, 'taghidden', false);
            $sql .= " AND cm.id {$hiddensql}";
            $params += $hiddenparams;
        }

        $sql .= " ORDER BY t.rawname ASC";

        $records = $DB->get_records_sql($sql, $params);
        $choices = [];
        foreach ($records as $record) {
            $choices[(int)$record->id] = $record->rawname;
        }

        return $choices;
    }

    /**
     * Check whether an activity is hidden from the catalogue.
     *
     * @param int $coursemoduleid
     * @return bool
     */
    public static function is_activity_hidden_from_catalogue(int $coursemoduleid): bool {
        return in_array($coursemoduleid, self::get_hidden_activity_ids());
    }

    /**
     * Persist activity catalogue visibility in the dedicated table.
     *
     * @param int $coursemoduleid
     * @param bool $hidden
     * @return void
     */
    public static function set_activity_hidden_from_catalogue(int $coursemoduleid, bool $hidden): void {
        global $DB;

        $existing = $DB->get_record('local_activitylibrary_status', ['coursemoduleid' => $coursemoduleid]);
        if (!$hidden) {
            if ($existing) {
                $DB->delete_records('local_activitylibrary_status', ['id' => $existing->id]);
            }
            return;
        }

        $record = (object)[
            'coursemoduleid' => $coursemoduleid,
            'visibility' => self::ITEM_HIDDEN,
            'timemodified' => time(),
        ];

        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('local_activitylibrary_status', $record);
            return;
        }

        $record->timecreated = time();
        $DB->insert_record('local_activitylibrary_status', $record);
    }

    /**
     * Get Activity library URL and text description for the current page
     *
     * @param null $page
     *
     * @return array an array containing a text and the url to the catalog page
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_catalog_url($page = null) {
        global $CFG, $PAGE;
        if (!$page) {
            $page = $PAGE;
        }
        if ($page->context) {
            $context = $page->context;
        } else {
            $context = \context_system::instance();
        }
        $urltext = static::get_resource_library_menu_text();
        $params = [];
        return [
            $urltext,
            new \moodle_url($CFG->wwwroot . '/local/activitylibrary/index.php', $params), ];
    }

    /**
     * Check if multiselect installed
     *
     * @return bool
     */
    public static function is_multiselect_installed() {
        return class_exists('\\customfield_multiselect\\field_controller');
    }

    /**
     * Full handler component name
     *
     * @param handler $handler
     * @return string
     */
    public static function get_handler_full_component($handler) {
        return $handler->get_component() . '_' . $handler->get_area();
    }

    /**
     * Simple function to get the filter config name for a handler
     *
     * @param handler $handler
     * @return string
     */
    public static function get_hidden_filter_config_name($handler) {
        return 'filter_hidden_' . static::get_handler_full_component($handler);
    }

    /**
     * Get hidden fields
     *
     * @param handler $handler
     * @return array
     * @throws \coding_exception
     */
    public static function get_hidden_fields_filters($handler) {
        if (self::$hiddenfields) {
            return self::$hiddenfields;
        }
        $configname = static::get_hidden_filter_config_name($handler);
        $hiddenfieldslist =
            get_config('local_activitylibrary', $configname);
        if (!$hiddenfieldslist) {
            return [];
        }
        self::$hiddenfields = explode(',', $hiddenfieldslist);
        return self::$hiddenfields;
    }

    /**
     * Check if given field is hidden
     *
     * @param handler $handler
     * @param string $fieldshortname
     * @throws \coding_exception
     */
    public static function is_field_hidden_filters($handler, $fieldshortname) {
        return in_array($fieldshortname, self::get_hidden_fields_filters($handler));
    }

    /**
     * Hide a field from filtering
     *
     * @param handler $handler
     * @param string|array $fieldshortname the field shortname or an array of fields shortnames
     * @throws \dml_exception
     */
    public static function hide_fields_filter($handler, $fieldshortname) {
        $hiddenfieldslist = self::get_hidden_fields_filters($handler);
        if (is_string($fieldshortname)) {
            $hiddenfieldslist[] = $fieldshortname;
        } else {
            if (is_array($fieldshortname)) {
                $hiddenfieldslist = array_merge($hiddenfieldslist, $fieldshortname);
            }
        }
        $hiddenfieldslist = array_unique($hiddenfieldslist); // Remove duplicate values.
        $configname = static::get_hidden_filter_config_name($handler);
        set_config($configname, implode(',', $hiddenfieldslist), 'local_activitylibrary');
        self::$hiddenfields = $hiddenfieldslist;
    }

    /**
     * Show a field from filtering
     *
     * Removes it from the list of hidden fields if it is set.
     *
     * @param handler $handler
     * @param string|array $fieldshortname the field shortname or an array of fields shortnames
     * @throws \dml_exception
     */
    public static function show_fields_filter($handler, $fieldshortname) {
        $hiddenfieldslist = self::get_hidden_fields_filters($handler);
        $fieldstoremove = [];
        if (is_string($fieldshortname)) {
            $fieldstoremove[] = $fieldshortname;
        } else {
            if (is_array($fieldshortname)) {
                $fieldstoremove = $fieldshortname;
            }
        }
        $hiddenfieldslist = array_diff($hiddenfieldslist, $fieldstoremove);
        $configname = static::get_hidden_filter_config_name($handler);
        set_config($configname, implode(',', $hiddenfieldslist), 'local_activitylibrary');
        self::$hiddenfields = $hiddenfieldslist;
    }

    /**
     * Global function to get the activity library link/menu text.

     * This allow to override the menu in other plugin or just by adjusting
     * this setting.
     * The usual language string is returned if the setting is left empty.
     *
     * @param string $coursename
     * @return false|\lang_string|mixed|object|string|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_resource_library_menu_text($coursename = "") {
        $rsmenutext = get_config('local_activitylibrary', 'menutextoverride');
        $generictext = get_string('activitylibrary', 'local_activitylibrary');
        $currentlang = current_language();
        $courseref = "";

        if ($coursename) {
            $courseref = " ({$coursename})";
        }
        if (!trim($rsmenutext)) {
            return $generictext . $courseref;
        }
        try {
            $alllangs = array_map(
                function ($value) {
                    return explode('|', $value);
                },
                explode("\n", $rsmenutext)
            );

            foreach ($alllangs as $lang) {
                if ($lang && !empty($lang[1] && $lang[1] == $currentlang)) {
                    return $lang[0] . $courseref;
                }
            }
            return $generictext . $courseref;
        } catch (Exception $e) {
            return $generictext . $courseref;
        }
    }
}

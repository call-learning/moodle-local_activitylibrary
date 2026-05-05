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
 * Activity library - external function to get filtered list of activities.
 *
 * @package   local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\external;

use core_text;
use context_course;
use context_system;
use core_course\external\course_module_summary_exporter;
use core_course\external\course_summary_exporter;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_activitylibrary\local\customfield_utils;
use local_activitylibrary\local\utils;
use moodle_url;

/**
 * External API for retrieving filtered activities.
 */
class get_filtered_activities extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'),
                    'List of course ids',
                    VALUE_DEFAULT,
                    []
                ),
                'filters' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'type' => new external_value(
                                PARAM_ALPHANUM,
                                'Filter type as per customfield type.'
                            ),
                            'shortname' => new external_value(
                                PARAM_ALPHANUMEXT,
                                'Matching customfield shortname if it is a customfield filter',
                                VALUE_OPTIONAL
                            ),
                            'operator' => new external_value(
                                PARAM_INT,
                                'Filter option as per local_activitylibrary\\local\\filters options.'
                            ),
                            'value' => new external_value(PARAM_RAW, 'The value of the filter to look for.'),
                        ]
                    ),
                    'Filter the results',
                    VALUE_OPTIONAL
                ),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sorting' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'column' => new external_value(PARAM_ALPHANUM, 'Column name for sorting'),
                            'order' => new external_value(
                                PARAM_ALPHA,
                                'ASC for ascending, DESC for descending; ascending by default'
                            ),
                        ]
                    ),
                    'Sort the results',
                    VALUE_OPTIONAL
                ),
            ]
        );
    }

    /**
     * Get activities from one course, several courses, or all courses.
     *
     * @param array $courseids
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param array $sorting
     * @return array
     */
    public static function execute(
        array $courseids = [],
        array $filters = [],
        int $limit = 0,
        int $offset = 0,
        array $sorting = []
    ): array {
        global $DB, $PAGE;

        $inparams = compact(['courseids', 'filters', 'limit', 'offset', 'sorting']);
        $params = self::validate_parameters(self::execute_parameters(), $inparams);
        $context = context_system::instance();
        self::validate_context($context);
        $scopeids = array_values(array_unique(array_filter(array_map('intval', $params['courseids']))));
        foreach ($scopeids as $courseid) {
            $coursecontext = context_course::instance($courseid);
            self::validate_context($coursecontext);
        }
        if (empty($scopeids)) {
            if (is_siteadmin()) {
                // On the home catalogue admins are not necessarily enrolled in courses.
                $scopeids = $DB->get_fieldset_select('course', 'id', 'id <> :siteid', ['siteid' => SITEID]);
            } else {
                // For regular users, keep catalogue scoped to enrolled/accessible courses.
                $mycourses = enrol_get_my_courses();
                $scopeids = array_keys($mycourses);
            }
        }

        if (empty($scopeids)) {
            return self::empty_result();
        }

        $scopeids = array_values(array_diff($scopeids, utils::get_hidden_course_ids()));
        if (empty($scopeids)) {
            return self::empty_result();
        }

        $customfieldfilters = [];
        $selectedcourseids = [];
        $selectedmodules = [];
        $selectedtagids = [];
        $fulltext = '';
        foreach ($params['filters'] as $filter) {
            $type = $filter['type'] ?? '';
            $rawvalue = $filter['value'] ?? '';
            $rawvaluestring = trim((string)$rawvalue);
            if ($rawvaluestring === '') {
                continue;
            }
            if ($type === 'course') {
                $selectedcourseids[] = (int)$rawvaluestring;
                continue;
            }
            if ($type === 'modname') {
                $selectedmodules[] = clean_param($rawvaluestring, PARAM_ALPHANUMEXT);
                continue;
            }
            if ($type === 'tags') {
                $selectedtagids = array_merge($selectedtagids, utils::parse_configured_ids($rawvaluestring));
                continue;
            }
            if ($type === 'fulltext') {
                $fulltext = clean_param($rawvaluestring, PARAM_TEXT);
                continue;
            }
            $customfieldfilters[] = $filter;
        }

        if (!empty($selectedcourseids)) {
            $scopeids = array_values(array_intersect($scopeids, array_unique(array_filter($selectedcourseids))));
            if (empty($scopeids)) {
                return self::empty_result();
            }
        }

        $hiddenactivityids = utils::get_hidden_activity_ids();

        [$insql, $inparams] = $DB->get_in_or_equal($scopeids, SQL_PARAMS_NAMED, 'courseid');
        $sqlparams = $inparams;
        $sqlwhere = "e.course {$insql} AND e.visible = 1 AND m.visible = 1";

        $selectedmodules = array_values(array_unique(array_filter($selectedmodules)));
        if (!empty($selectedmodules)) {
            [$modinsql, $modinparams] = $DB->get_in_or_equal($selectedmodules, SQL_PARAMS_NAMED, 'modname');
            $sqlwhere .= " AND m.name {$modinsql}";
            $sqlparams += $modinparams;
        }

        if (!empty($hiddenactivityids)) {
            [$hiddeninsql, $hiddeninparams] = $DB->get_in_or_equal($hiddenactivityids, SQL_PARAMS_NAMED, 'hiddenactivity', false);
            $sqlwhere .= " AND e.id {$hiddeninsql}";
            $sqlparams += $hiddeninparams;
        }

        $selectedtagids = array_values(array_unique(array_filter($selectedtagids)));
        if (!empty($selectedtagids)) {
            [$taginsql, $taginparams] = $DB->get_in_or_equal($selectedtagids, SQL_PARAMS_NAMED, 'tagid');
            $sqlwhere .= " AND EXISTS (
                    SELECT 1
                      FROM {tag_instance} ti
                     WHERE ti.itemid = e.id
                       AND ti.component = :tagcomponent
                       AND ti.itemtype = :tagitemtype
                       AND ti.tagid {$taginsql}
                )";
            $sqlparams += [
                'tagcomponent' => 'core',
                'tagitemtype' => 'course_modules',
            ] + $taginparams;
        }

        $additionalfields = [
            'modname' => 'm.name AS modname',
            'modinstance' => 'e.instance AS modinstance',
            'parentid' => 'e.course AS parentid',
            'category' => 'c.fullname AS category',
            'categoryname' => 'c.fullname AS categoryname',
            'timecreated' => 'e.added AS timecreated',
            'timemodified' => 'e.added AS timemodified',
        ];

        $sortsql = self::get_sort_options_sql($params['sorting'], array_keys($additionalfields));
        $handler = \local_activitylibrary\customfield\coursemodule_handler::create();
        $records = customfield_utils::get_records_from_handler(
            $handler,
            $customfieldfilters,
            0,
            0,
            [
                'JOIN {modules} m ON m.id = e.module',
                'JOIN {course} c ON c.id = e.course',
            ],
            $additionalfields,
            $sqlwhere,
            $sqlparams,
            $sortsql
        );

        $renderer = $PAGE->get_renderer('core');
        $modulesinfo = [];
        $modinfos = [];
        $courseimages = [];
        $modulemetadata = self::get_module_metadata_map($records);

        foreach ($records as $record) {
            if (empty($modinfos[$record->parentid])) {
                $modinfos[$record->parentid] = get_fast_modinfo($record->parentid);
            }

            $context = context_course::instance($record->parentid, IGNORE_MISSING);
            if (!$context) {
                continue;
            }

            try {
                self::validate_context($context);
            } catch (\Exception $e) {
                continue;
            }

            $coursecms = $modinfos[$record->parentid]->get_cms();
            if (!isset($coursecms[$record->id])) {
                continue;
            }
            $cm = $coursecms[$record->id];
            if (!$cm->uservisible) {
                continue;
            }
            $PAGE->set_context($context);
            $exported = (array)(new course_module_summary_exporter(null, ['cm' => $cm]))->export($renderer);
            $recorddata = (array)$record;
            $recorddata['fullname'] = $cm->name;
            $recorddata['idnumber'] = $cm->idnumber;
            $recorddata['groupmode'] = $cm->groupmode;
            $recorddata['groupingid'] = $cm->groupingid;
            $recorddata['visible'] = (int)$cm->uservisible;

            if ($cm->url) {
                $recorddata['viewurl'] = $cm->url->out_as_local_url();
            } else {
                $recorddata['viewurl'] = (new moodle_url('/course/view.php', ['id' => $record->parentid]))->out(false);
            }

            $timemodified = $modulemetadata[$cm->modname][$cm->instance]['timemodified'] ?? null;
            if (!empty($timemodified)) {
                $recorddata['timemodified'] = (int)$timemodified;
            }

            $recorddata['iconurl'] = $exported['iconurl'] ?? '';
            $recorddata['purpose'] = plugin_supports('mod', $cm->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER);
            $recorddata['modname'] = $cm->modname;
            if (!array_key_exists($record->parentid, $courseimages)) {
                $course = get_course($record->parentid);
                $courseimages[$record->parentid] = course_summary_exporter::get_course_image($course)
                    ?: $renderer->get_generated_url_for_course($context);
            }
            $recorddata['courseimage'] = $courseimages[$record->parentid];

            $intro = (string)($modulemetadata[$cm->modname][$cm->instance]['intro'] ?? '');
            if ($intro !== '') {
                $description = preg_replace('/\s+/', ' ', trim(strip_tags($intro)));
                if (!empty($description)) {
                    $recorddata['description'] = $description;
                }
            }

            if ($fulltext !== '') {
                $needle = core_text::strtolower($fulltext);
                $name = core_text::strtolower($cm->name);
                $description = core_text::strtolower($recorddata['description'] ?? '');
                if (core_text::strpos($name, $needle) === false && core_text::strpos($description, $needle) === false) {
                    continue;
                }
            }

            $modulesinfo[] = $recorddata;
        }

        if (!empty($params['sorting'])) {
            $firstsort = reset($params['sorting']);
            $column = $firstsort['column'];
            $order = strtoupper($firstsort['order']);
            if ($column === 'timemodified') {
                usort($modulesinfo, function ($left, $right) use ($order) {
                    $leftvalue = $left['timemodified'] ?? 0;
                    $rightvalue = $right['timemodified'] ?? 0;
                    return $order === 'DESC' ? $rightvalue <=> $leftvalue : $leftvalue <=> $rightvalue;
                });
            }
            if ($column === 'fullname') {
                usort($modulesinfo, function ($left, $right) use ($order) {
                    $leftvalue = $left['fullname'] ?? '';
                    $rightvalue = $right['fullname'] ?? '';
                    return $order === 'DESC' ? $rightvalue <=> $leftvalue : $leftvalue <=> $rightvalue;
                });
            }
        }

        return [
            'entities' => array_slice($modulesinfo, $params['offset'], $params['limit'] ? $params['limit'] : null),
            'totalcount' => count($modulesinfo),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'entities' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'Activity id'),
                            'parentid' => new external_value(PARAM_INT, 'Parent course id'),
                            'fullname' => new external_value(PARAM_TEXT, 'Activity full name'),
                            'idnumber' => new external_value(PARAM_RAW, 'Id number', VALUE_OPTIONAL),
                            'modname' => new external_value(PARAM_RAW, 'Module name'),
                            'iconurl' => new external_value(PARAM_RAW, 'Module icon URL', VALUE_OPTIONAL),
                            'courseimage' => new external_value(PARAM_RAW, 'Course image URL', VALUE_OPTIONAL),
                            'purpose' => new external_value(PARAM_ALPHANUMEXT, 'Module purpose', VALUE_OPTIONAL),
                            'description' => new external_value(PARAM_TEXT, 'Module description', VALUE_OPTIONAL),
                            'visible' => new external_value(PARAM_INT, '1 available, 0 unavailable', VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode', VALUE_OPTIONAL),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id', VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_INT, 'Creation time', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT, 'Modification time', VALUE_OPTIONAL),
                            'category' => new external_value(PARAM_TEXT, 'Course full name', VALUE_OPTIONAL),
                            'categoryname' => new external_value(PARAM_TEXT, 'Course full name', VALUE_OPTIONAL),
                            'viewurl' => new external_value(PARAM_URL, 'Activity URL'),
                        ],
                        'Activity summary'
                    )
                ),
                'totalcount' => new external_value(PARAM_INT, 'Total number of matching activities'),
            ]
        );
    }

    /**
     * Build SQL order by from sort options.
     *
     * @param array $sortoptions
     * @param array $fields
     * @return string
     */
    protected static function get_sort_options_sql(array $sortoptions, array $fields): string {
        $sortsqls = [];
        foreach ($sortoptions as $sort) {
            $column = $sort['column'] ?? '';
            $order = strtoupper($sort['order'] ?? '');
            if (!in_array($column, $fields) || ($order !== 'ASC' && $order !== 'DESC')) {
                continue;
            }
            $sortsqls[] = "{$column} {$order}";
        }
        return implode(',', $sortsqls);
    }

    /**
     * Preload module metadata needed later in execute() to avoid N+1 queries.
     *
     * @param array $records
     * @return array
     */
    protected static function get_module_metadata_map(array $records): array {
        global $DB;

        $moduleinstances = [];
        foreach ($records as $record) {
            if (empty($record->modname) || empty($record->modinstance)) {
                continue;
            }
            $moduleinstances[$record->modname][] = (int)$record->modinstance;
        }

        $metadata = [];
        $dbman = $DB->get_manager();
        foreach ($moduleinstances as $modname => $instanceids) {
            $instanceids = array_values(array_unique(array_filter($instanceids)));
            if (empty($instanceids)) {
                continue;
            }

            $fields = ['id', 'timemodified'];
            if ($dbman->field_exists($modname, 'intro')) {
                $fields[] = 'intro';
            }

            $recordsbyinstance = $DB->get_records_list(
                $modname,
                'id',
                $instanceids,
                '',
                implode(', ', $fields)
            );

            foreach ($recordsbyinstance as $instanceid => $instancerecord) {
                $metadata[$modname][(int)$instanceid] = [
                    'timemodified' => $instancerecord->timemodified ?? null,
                    'intro' => $instancerecord->intro ?? '',
                ];
            }
        }

        return $metadata;
    }

    /**
     * Build an empty result payload matching execute_returns().
     *
     * @return array
     */
    protected static function empty_result(): array {
        return [
            'entities' => [],
            'totalcount' => 0,
        ];
    }
}

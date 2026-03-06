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

use renderer_base;

/**
 * Class containing data for the activity activitylibrary.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_activitylibrary extends base_activitylibrary {
    /** @var int[] */
    protected $courseids = [];

    /**
     * Main constructor.
     *
     * @param int[] $courseids
     * @param string $sort
     * @param string $view
     * @param int $paging
     */
    public function __construct(
        array $courseids = [],
        $sort = self::SORT_FULLNAME_ASC,
        $view = self::VIEW_CARD,
        $paging = self::PAGING_15
    ) {
        parent::__construct($sort, $view, $paging);
        $this->courseids = array_values(array_unique(array_filter(array_map('intval', $courseids))));
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $handler = \local_activitylibrary\customfield\coursemodule_handler::create();
        $defaultvariables = $this->get_export_defaults($output, $handler);
        $defaultvariables['entitytype'] = 'course';
        $defaultvariables['courseids'] = implode(',', $this->courseids);
        $defaultvariables['categoryid'] = 0;
        return array_merge($defaultvariables, $this->get_preferences());
    }
}

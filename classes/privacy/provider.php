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
 * Course activitylibrary
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activitylibrary\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\user_preference_provider;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for local_activitylibrary.
 *
 * @package    local_activitylibrary
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, user_preference_provider {
    /**
     * Returns meta-data information about the activitylibrary plugin.
     *
     * @param  \core_privacy\local\metadata\collection $collection A collection of meta-data.
     * @return \core_privacy\local\metadata\collection Return the collection of meta-data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            'local_activitylibrary_user_sort_preference',
            'privacy:metadata:activitylibrarysortpreference'
        );
        $collection->add_user_preference(
            'local_activitylibrary_user_view_preference',
            'privacy:metadata:activitylibraryviewpreference'
        );
        $collection->add_user_preference(
            'local_activitylibrary_user_paging_preference',
            'privacy:metadata:activitylibrarypagingpreference'
        );
        return $collection;
    }
    /**
     * Export all user preferences for the myoverview block
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $preference = get_user_preferences('local_activitylibrary_user_sort_preference', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference(
                'local_activitylibrary',
                'local_activitylibrary_user_sort_preference',
                get_string($preference, 'local_activitylibrary'),
                get_string('privacy:metadata:activitylibrarysortpreference', 'local_activitylibrary')
            );
        }

        $preference = get_user_preferences('local_activitylibrary_user_view_preference', null, $userid);
        if (isset($preference)) {
            writer::export_user_preference(
                'local_activitylibrary',
                'local_activitylibrary_user_view_preference',
                get_string($preference, 'local_activitylibrary'),
                get_string('privacy:metadata:activitylibraryviewpreference', 'local_activitylibrary')
            );
        }

        $preference = get_user_preferences('local_activitylibrary_user_paging_preference', null, $userid);
        if (isset($preference)) {
            \core_privacy\local\request\writer::export_user_preference(
                'local_activitylibrary',
                'local_activitylibrary_user_paging_preference',
                $preference,
                get_string('privacy:metadata:activitylibrarypagingpreference', 'local_activitylibrary')
            );
        }
    }
}

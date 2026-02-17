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
 * Repository for Activity Library AJAX calls.
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Repository API.
 */
export default class Repository {
    /**
     * Retrieve a list of activities.
     *
     * @param {object} args The request arguments.
     * @return {Promise} Resolved with an array of activities.
     */
    static getFilteredActivitiesList(args) {
        const request = {
            methodname: 'local_activitylibrary_get_filtered_activities',
            args
        };

        return Ajax.call([request])[0];
    }

    /**
     * Update user preferences.
     *
     * @param {Object} args
     */
    static updateUserPreferences(args) {
        const request = {
            methodname: 'core_user_update_user_preferences',
            args
        };

        Ajax.call([request])[0].fail(Notification.exception);
    }
}

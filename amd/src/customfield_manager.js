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
 * Manage visibility of custom fields in filter forms.
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Custom field manager.
 */
export default class CustomFieldManager {
    /**
     * Attach events for hide/show filter toggles.
     *
     * @param {string} component
     * @param {string} area
     * @param {string} hideFilterLocator
     */
    static init(component, area, hideFilterLocator) {
        $(hideFilterLocator).on('click', function() {
            const checked = $(this).is(':checked');
            const request = {
                methodname: checked
                    ? 'local_activitylibrary_hide_fields_filters'
                    : 'local_activitylibrary_show_fields_filters',
                args: {
                    component,
                    area,
                    fieldshortnames: [$(this).data('field-shortname')]
                }
            };

            Ajax.call([request])[0].fail(Notification.exception);
        });
    }
}

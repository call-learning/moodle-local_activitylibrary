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
 * Display permalink for current filters.
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Templates from 'core/templates';
import Toast from 'core/toast';
import Str from 'core/str';
import Notification from 'core/notification';

let catalogURL = null;

/**
 * Permalink helper.
 */
export default class Permalink {
    /**
     * Setup copy-link button interaction.
     *
     * @param {string} triggerid
     * @param {string} targetid
     */
    static setupCopyLink(triggerid, targetid) {
        document.querySelector('#' + triggerid).addEventListener('click', () => {
            const target = document.getElementById(targetid);
            target.select();
            if (document.execCommand('copy')) {
                Toast.add(Str.get_string('copied', 'local_activitylibrary'), null, 'success');
            }
        });
    }

    /**
     * Initialise permalink updates on filter changes.
     */
    static init() {
        catalogURL = new URL(window.location.href);

        $(document).on('activitylibrary-filters-change', (e, filterArray) => {
            filterArray.forEach((filter) => {
                const fieldname = 'customfield_' + filter.shortname;
                if (filter.value) {
                    catalogURL.searchParams.append(fieldname + '[operator]', filter.operator);
                    catalogURL.searchParams.append(fieldname + '[value]', filter.value);
                    catalogURL.searchParams.append(fieldname + '[type]', filter.type);
                }
            });

            Templates.render('local_activitylibrary/permalink', {url: catalogURL.toString()})
                .then((html, js) => Templates.replaceNodeContents('#activitylibrary-permalink', html, js))
                .catch(Notification.exception);
        });
    }
}

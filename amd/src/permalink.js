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

import Templates from 'core/templates';
import Toast from 'core/toast';
import Str from 'core/str';
import Notification from 'core/notification';

let catalogURL = null;

const removeFilterParams = () => {
    const paramsToRemove = [];
    for (const [key] of catalogURL.searchParams.entries()) {
        if (key.startsWith('customfield_') || key.startsWith('fulltext[') ||
                key.startsWith('course[') || key.startsWith('modname[') ||
                key.startsWith('tags[')) {
            paramsToRemove.push(key);
        }
    }

    paramsToRemove.forEach((param) => catalogURL.searchParams.delete(param));
};

const addFilterParam = (fieldname, filter) => {
    if (!filter.value && filter.value !== 0) {
        return;
    }

    catalogURL.searchParams.append(fieldname + '[operator]', filter.operator);
    catalogURL.searchParams.append(fieldname + '[type]', filter.type);

    if (Array.isArray(filter.value)) {
        filter.value.forEach((value) => {
            catalogURL.searchParams.append(fieldname + '[value][]', value);
        });
        return;
    }

    catalogURL.searchParams.append(fieldname + '[value]', filter.value);
};

const updatePermalink = (filterArray = []) => {
    removeFilterParams();

    filterArray.forEach((filter) => {
        if (filter.shortname) {
            addFilterParam('customfield_' + filter.shortname, filter);
            return;
        }

        if (filter.type === 'fulltext' || filter.type === 'course' || filter.type === 'modname' || filter.type === 'tags') {
            addFilterParam(filter.type, filter);
        }
    });

    Templates.render('local_activitylibrary/permalink', {url: catalogURL.toString()})
        .then((html, js) => Templates.replaceNodeContents('#activitylibrary-permalink', html, js))
        .catch(Notification.exception);
};

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
        const triggerElement = document.querySelector('#' + triggerid);
        if (!triggerElement) {
            return;
        }

        triggerElement.addEventListener('click', async() => {
            const target = document.getElementById(targetid);
            if (!target) {
                return;
            }

            target.select();
            try {
                await navigator.clipboard.writeText(target.value);
                const copiedString = await Str.get_string('copied', 'local_activitylibrary');
                Toast.add(copiedString, null, 'success');
            } catch (error) {
                if (document.execCommand('copy')) {
                    const copiedString = await Str.get_string('copied', 'local_activitylibrary');
                    Toast.add(copiedString, null, 'success');
                }
            }
        });
    }

    /**
     * Initialise permalink updates on filter changes.
     */
    static init() {
        catalogURL = new URL(window.location.href);
        updatePermalink();

        document.addEventListener('activitylibrary-filters-change', (event) => {
            if (event.detail) {
                updatePermalink(event.detail);
            }
        });

        if (window.jQuery) {
            window.jQuery(document).on('activitylibrary-filters-change', (e, filterArray) => {
                updatePermalink(filterArray);
            });
            window.jQuery(document).on('activitylibrary-filters-inited', (e, filterArray) => {
                updatePermalink(filterArray);
            });
        }
    }
}

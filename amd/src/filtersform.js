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
 * Retrieve and process the filter form values.
 *
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Config from 'core/config';

/**
 * Filters form helper.
 */
export default class FiltersForm {
    /**
     * Retrieve filter payload from form fields.
     *
     * @param {String|HTMLElement|JQuery} target
     * @param {Boolean} ignoreSesskey
     * @return {Object[]|Boolean}
     */
    static getFilterData(target, ignoreSesskey) {
        const data = $(target).serializeArray();
        const filterdata = {};

        let sesskeyConfirmed = false;
        data.forEach((entry) => {
            if (entry.name === 'sesskey') {
                sesskeyConfirmed = entry.value === Config.sesskey;
                return;
            }

            const parsename = entry.name.match(/(customfield_)?(\w+)\[(\w+)\]\[?(\w*)\]?/);
            if (!parsename) {
                return;
            }

            let hasCustomShortName = false;
            if (parsename.length >= 4) {
                parsename.shift();
                hasCustomShortName = true;
            }

            const rootname = parsename[1];
            const type = parsename[2];

            if (filterdata[rootname] === undefined) {
                filterdata[rootname] = {};
            }

            if (hasCustomShortName && filterdata[rootname].shortname === undefined) {
                Object.defineProperty(filterdata[rootname], 'shortname', {
                    enumerable: true,
                    value: rootname
                });
            }

            if (entry.value === '_qf__force_multiselect_submission') {
                return;
            }

            if (typeof filterdata[rootname].value === 'undefined') {
                Object.defineProperty(filterdata[rootname], type, {
                    enumerable: true,
                    value: entry.value,
                    writable: true
                });
            } else {
                filterdata[rootname].value += ',' + entry.value;
            }
        });

        const filterDataArray = Object.values(filterdata).filter((value) => {
            if (value.type === 'date' && value.value !== undefined) {
                return value.value.split(',').length > 3;
            }
            return value.value !== undefined || value.value === null;
        });

        if (sesskeyConfirmed || ignoreSesskey) {
            return filterDataArray;
        }

        return false;
    }

    /**
     * Initialise filters form events.
     *
     * @param {String|HTMLElement|JQuery} selector
     */
    static init(selector) {
        const target = $(selector);

        target.on('submit', 'form', (e) => {
            e.preventDefault();
            const filterDataArray = FiltersForm.getFilterData(target.children('form'), false);
            if (filterDataArray) {
                $(document).trigger('activitylibrary-filters-change', [filterDataArray]);
            }
        });

        $('#id_resetbutton').on('click', () => {
            $(target).children('form.activitylibrary-filters-form')[0].reset();
        });

        const filterDataArray = FiltersForm.getFilterData(target.children('form'), true);
        $(document).trigger('activitylibrary-filters-inited', [filterDataArray]);
    }
}

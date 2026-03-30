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
     * Dispatch filter state updates through both DOM and jQuery events.
     *
     * @param {string} eventName
     * @param {Array} filterDataArray
     */
    static dispatchFilterEvent(eventName, filterDataArray) {
        document.dispatchEvent(new CustomEvent(eventName, {
            detail: filterDataArray
        }));

        $(document).trigger(eventName, [filterDataArray]);
    }

    /**
     * Check whether a filter value should be considered active.
     *
     * @param {*} value
     * @return {Boolean}
     */
    static isMeaningfulValue(value) {
        if (Array.isArray(value)) {
            return value.some((item) => FiltersForm.isMeaningfulValue(item));
        }

        if (value === null || value === undefined) {
            return false;
        }

        return String(value).trim() !== '';
    }

    /**
     * Check if at least one filter is active.
     *
     * @param {String|HTMLElement|JQuery} target
     * @return {Boolean}
     */
    static hasActiveFilters(target) {
        const filterDataArray = FiltersForm.getFilterData(target, true);
        return Array.isArray(filterDataArray) && filterDataArray.some((filter) => {
            if (filter.type === 'date') {
                return FiltersForm.isMeaningfulValue(filter.value) && filter.value.split(',').length > 3;
            }

            return FiltersForm.isMeaningfulValue(filter.value);
        });
    }

    /**
     * Update reset button state from current form values.
     *
     * @param {JQuery} target
     */
    static updateResetButtonState(target) {
        const form = target.children('form.activitylibrary-filters-form');
        const resetButton = form.find('[name="resetbutton"]');
        const hasActiveFilters = FiltersForm.hasActiveFilters(form);

        resetButton.prop('disabled', !hasActiveFilters);
        resetButton.toggleClass('activitylibrary-reset-inactive', !hasActiveFilters);
        resetButton.attr('aria-disabled', (!hasActiveFilters).toString());
    }

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

            const parsename = entry.name.match(/^(customfield_)?(\w+)\[(\w+)\](?:\[(\w*)\])?$/);
            if (!parsename) {
                return;
            }

            const hasCustomShortName = Boolean(parsename[1]);
            const rootname = parsename[2];
            const type = parsename[3];

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
        const form = target.children('form.activitylibrary-filters-form');
        const resetButton = form.find('[name="resetbutton"]');

        target.on('submit', 'form', (e) => {
            e.preventDefault();
            const filterDataArray = FiltersForm.getFilterData(form, false);
            if (filterDataArray) {
                FiltersForm.dispatchFilterEvent('activitylibrary-filters-change', filterDataArray);
            }
            FiltersForm.updateResetButtonState(target);
        });

        form.on('input change', ':input', () => {
            FiltersForm.updateResetButtonState(target);
        });

        resetButton.on('click', (e) => {
            if (!FiltersForm.hasActiveFilters(form)) {
                e.preventDefault();
                return;
            }

            e.preventDefault();
            form[0].reset();
            FiltersForm.updateResetButtonState(target);
            FiltersForm.dispatchFilterEvent('activitylibrary-filters-change', []);
        });

        FiltersForm.updateResetButtonState(target);

        const filterDataArray = FiltersForm.getFilterData(form, true);
        FiltersForm.dispatchFilterEvent('activitylibrary-filters-inited', filterDataArray || []);
    }
}

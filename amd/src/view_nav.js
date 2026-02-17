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
 * Manage display and sorting controls for the Activity Library.
 *
 * Inspired from the Course overview block.
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import CustomEvents from 'core/custom_interaction_events';
import Repository from 'local_activitylibrary/repository';
import View from 'local_activitylibrary/view';
import Selectors from 'local_activitylibrary/selectors';

const SELECTORS = {
    MODIFIERS: '[data-region="display-modifiers"]',
    SORT_OPTION: '[data-sort]',
    DISPLAY_OPTION: '[data-display-option]'
};

const PREFERENCE_KEYS = {
    display: 'local_activitylibrary_user_view_preference',
    sort: 'local_activitylibrary_user_sort_preference'
};

const asRoot = (root) => $(root);

const updatePreference = (type, value) => {
    const preferenceKey = PREFERENCE_KEYS[type];
    if (!preferenceKey) {
        return;
    }

    Repository.updateUserPreferences({
        preferences: [{type: preferenceKey, value}]
    });
};

const updateSort = (root, option) => {
    const sortOrder = option.attr('data-sort');
    const sortColumn = option.attr('data-column');
    const viewRegion = root.find(Selectors.entityView.region);

    viewRegion.attr('data-sort-column', sortColumn);
    viewRegion.attr('data-sort-order', sortOrder);

    updatePreference('sort', sortColumn + ',' + sortOrder);
    View.refresh(root);
};

const updateDisplay = (root, option) => {
    const displayMode = option.attr('data-display-option');
    const viewRegion = root.find(Selectors.entityView.region);

    viewRegion.attr('data-display', displayMode);

    updatePreference('display', displayMode);
    View.reset(root);
};

const bindModifierEvents = (root) => {
    const modifiers = root.find(SELECTORS.MODIFIERS);
    const eventNamespace = '.activitylibrarynav_' + (root.attr('id') || 'root');

    CustomEvents.define(modifiers, [CustomEvents.events.activate]);

    modifiers.off(CustomEvents.events.activate + eventNamespace)
        .on(
            CustomEvents.events.activate + eventNamespace,
            SELECTORS.SORT_OPTION,
            (e, data) => {
                const option = $(e.target);
                if (option.hasClass('active')) {
                    return;
                }

                updateSort(root, option);
                data.originalEvent.preventDefault();
            }
        )
        .on(
            CustomEvents.events.activate + eventNamespace,
            SELECTORS.DISPLAY_OPTION,
            (e, data) => {
                const option = $(e.target);
                if (option.hasClass('active')) {
                    return;
                }

                updateDisplay(root, option);
                data.originalEvent.preventDefault();
            }
        );
};

/**
 * Navigation controller for the Activity Library view.
 */
export default class ViewNav {
    /**
     * Initialise navigation control bindings.
     *
     * @param {object} root
     */
    static init(root) {
        bindModifierEvents(asRoot(root));
    }
}

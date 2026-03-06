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

const BINDINGS = new WeakMap();

const asRootElement = (root) => {
    if (root instanceof Element) {
        return root;
    }

    if (root && typeof root.get === 'function') {
        return root.get(0);
    }

    return root;
};

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
    const sortOrder = option.getAttribute('data-sort');
    const sortColumn = option.getAttribute('data-column');
    const viewRegion = root.querySelector(Selectors.entityView.region);
    if (!viewRegion) {
        return;
    }

    viewRegion.setAttribute('data-sort-column', sortColumn);
    viewRegion.setAttribute('data-sort-order', sortOrder);

    updatePreference('sort', sortColumn + ',' + sortOrder);
    View.refresh(root);
};

const updateDisplay = (root, option) => {
    const displayMode = option.getAttribute('data-display-option');
    const viewRegion = root.querySelector(Selectors.entityView.region);
    if (!viewRegion) {
        return;
    }

    viewRegion.setAttribute('data-display', displayMode);

    updatePreference('display', displayMode);
    View.reset(root);
};

const bindModifierEvents = (root) => {
    const modifiers = root.querySelector(SELECTORS.MODIFIERS);
    if (!modifiers) {
        return;
    }

    const previousController = BINDINGS.get(modifiers);
    if (previousController) {
        previousController.abort();
    }

    const controller = new AbortController();
    BINDINGS.set(modifiers, controller);

    const getOption = (event) => event.target.closest(SELECTORS.SORT_OPTION + ',' + SELECTORS.DISPLAY_OPTION);

    const handleActivation = (event, fromKeyboard = false) => {
        const option = getOption(event);
        if (!option || !modifiers.contains(option)) {
            return;
        }

        if (option.classList.contains('active')) {
            return;
        }

        if (option.matches(SELECTORS.SORT_OPTION)) {
            updateSort(root, option);
        } else if (option.matches(SELECTORS.DISPLAY_OPTION)) {
            updateDisplay(root, option);
        }

        if (fromKeyboard || event.type === 'click') {
            event.preventDefault();
        }
    };

    modifiers.addEventListener('click', (event) => {
        handleActivation(event);
    }, {signal: controller.signal});

    modifiers.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
            handleActivation(event, true);
        }
    }, {signal: controller.signal});
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
        const rootElement = asRootElement(root);
        if (!rootElement) {
            return;
        }
        bindModifierEvents(rootElement);
    }
}

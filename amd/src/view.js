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
 * Manage the activities view for the Activity Library.
 *
 * Inspired from the Course overview block.
 * @copyright  2025 CALL Learning - Laurent David laurent@call-learning.fr
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Repository from 'local_activitylibrary/repository';
import PagedContentFactory from 'core/paged_content_factory';
import * as PubSub from 'core/pubsub';
import CustomEvents from 'core/custom_interaction_events';
import Notification from 'core/notification';
import Templates from 'core/templates';
import Selectors from 'local_activitylibrary/selectors';
import PagedContentEvents from 'core/paged_content_events';

const TEMPLATES = {
    CARDS: 'local_activitylibrary/view-cards',
    LIST: 'local_activitylibrary/view-list',
    EMPTY: 'local_activitylibrary/no-entities'
};

const ITEMS_PER_PAGE_OPTIONS = [12, 24, 48];
const FILTER_EVENTS = 'activitylibrary-filters-inited activitylibrary-filters-change';

const DEFAULT_PAGED_CONTENT_CONFIG = {
    ignoreControlWhileLoading: true,
    controlPlacementBottom: true,
    persistentLimitKey: 'local_activitylibrary_user_paging_preference'
};

const ROOT_STATE = new WeakMap();

const asRoot = (root) => $(root);

const createState = () => ({
    loadedPages: [],
    lastPage: 0,
    lastLimit: 0,
    eventNamespace: '',
    filterEventNamespace: '',
    currentFilters: [],
    courseIds: []
});

const getState = (root) => {
    const element = root.get(0);
    if (!ROOT_STATE.has(element)) {
        ROOT_STATE.set(element, createState());
    }
    return ROOT_STATE.get(element);
};

const getDisplayModifiers = (root) => {
    const region = root.find(Selectors.entityView.region);
    return {
        display: region.attr('data-display') || 'cards',
        sort: {
            column: region.attr('data-sort-column') || 'fullname',
            order: region.attr('data-sort-order') || 'ASC'
        },
        displayCategories: region.attr('data-displaycategories') === 'on'
    };
};

const getNoEntitiesImage = (root) => root.find(Selectors.entityView.region).attr('data-noentitiesimg');

const getItemsPerPage = (root) => {
    const pagingLimit = parseInt(root.find(Selectors.entityView.region).attr('data-paging'), 10);
    if (!pagingLimit) {
        return ITEMS_PER_PAGE_OPTIONS;
    }

    return ITEMS_PER_PAGE_OPTIONS.map((value) => ({
        value,
        active: value === pagingLimit
    }));
};

const updateStateContextFromRoot = (root, state) => {
    state.courseIds = (root.attr('data-course-ids') || '')
        .split(',')
        .map((value) => parseInt(value, 10))
        .filter((value) => !isNaN(value) && value > 0);
};

const fetchEntities = (state, modifiers, limit, offset) => Repository.getFilteredActivitiesList({
    courseids: state.courseIds,
    sorting: [{column: modifiers.sort.column, order: modifiers.sort.order}],
    filters: state.currentFilters,
    limit,
    offset
});

const removeHiddenCategoriesIfNeeded = (entities, showCategories) => {
    if (showCategories) {
        return entities;
    }

    return entities.map((entity) => {
        const copy = Object.assign({}, entity);
        delete copy.category;
        return copy;
    });
};

const renderEntities = (root, pageData) => {
    const entities = pageData && pageData.entities ? pageData.entities : [];
    const modifiers = getDisplayModifiers(root);
    const templateName = modifiers.display === 'list' ? TEMPLATES.LIST : TEMPLATES.CARDS;
    const renderedEntities = removeHiddenCategoriesIfNeeded(entities, modifiers.displayCategories);

    if (renderedEntities.length > 0) {
        return Templates.render(templateName, {entities: renderedEntities});
    }

    return Templates.render(TEMPLATES.EMPTY, {noentitiesimg: getNoEntitiesImage(root)});
};

const dispatchCardsRenderedEvent = () => {
    const rootNode = document.querySelector(Selectors.entityView.region + ' .paged-content-page-container');
    if (!rootNode) {
        return;
    }

    const observer = new MutationObserver((mutationsList) => {
        mutationsList.forEach((mutation) => {
            if (mutation.type !== 'childList') {
                return;
            }

            const hasPageContent = document.querySelector(
                Selectors.entityView.region + ' .paged-content-page-container [data-region="paged-content-page"]'
            );
            if (hasPageContent) {
                document.dispatchEvent(new CustomEvent('resource_library_card_rendered', {
                    detail: {rootNode}
                }));
            }
        });
    });

    observer.observe(rootNode, {childList: true, subtree: true});
};

const getPageContainer = (root, index) => root.find('[data-region="paged-content-page"][data-page="' + index + '"]');

const subscribeSetLimit = (root, state) => {
    const event = state.eventNamespace + PagedContentEvents.SET_ITEMS_PER_PAGE_LIMIT;
    PubSub.subscribe(event, (limit) => {
        root.find(Selectors.entityView.region).attr('data-paging', limit);
    });
};

const initializePagedContent = (root, state) => {
    state.eventNamespace = 'local_activitylibrary' + root.attr('id') + '_' + Math.random();

    const itemsPerPage = getItemsPerPage(root);
    const modifiers = getDisplayModifiers(root);

    const config = $.extend({}, DEFAULT_PAGED_CONTENT_CONFIG, {
        eventNamespace: state.eventNamespace
    });

    const loadPages = (pagesData, actions) => pagesData.map((pageData) => {
        const currentPage = pageData.pageNumber;
        const limit = pageData.limit;

        if (state.lastLimit !== limit) {
            state.loadedPages = [];
            state.lastPage = 0;
        }

        if (state.lastPage === currentPage) {
            actions.allItemsLoaded(state.lastPage);
            return renderEntities(root, state.loadedPages[currentPage]);
        }

        state.lastLimit = limit;
        return fetchEntities(state, modifiers, limit, limit * (currentPage - 1))
            .then((entities) => {
                state.loadedPages[currentPage] = {entities};

                if (entities.length < pageData.limit) {
                    state.lastPage = currentPage;
                    actions.allItemsLoaded(currentPage);
                }

                return renderEntities(root, state.loadedPages[currentPage]);
            })
            .catch(Notification.exception);
    });

    PagedContentFactory.createWithLimit(itemsPerPage, loadPages, config)
        .then((html, js) => {
            subscribeSetLimit(root, state);
            return Templates.replaceNodeContents(root.find(Selectors.entityView.region), html, js);
        })
        .done(dispatchCardsRenderedEvent)
        .catch(Notification.exception);
};

const resetStateCache = (state) => {
    state.loadedPages = [];
    state.lastPage = 0;
    state.lastLimit = 0;
};

/**
 * View controller.
 */
export default class View {
    /**
     * Refresh content for the provided root.
     *
     * @param {object} root
     */
    static refresh(root) {
        const rootNode = asRoot(root);
        const state = getState(rootNode);

        updateStateContextFromRoot(rootNode, state);
        resetStateCache(state);
        initializePagedContent(rootNode, state);

        if (!rootNode.attr('data-init')) {
            CustomEvents.define(rootNode, [CustomEvents.events.activate]);
            rootNode.attr('data-init', true);
        }
    }

    /**
     * Initialise view events.
     *
     * @param {object} root
     */
    static init(root) {
        const rootNode = asRoot(root);
        const state = getState(rootNode);

        state.filterEventNamespace = '.activitylibraryview_' + (rootNode.attr('id') || 'root');
        const namespacedEvents = FILTER_EVENTS
            .split(' ')
            .map((event) => event + state.filterEventNamespace)
            .join(' ');

        $(document).off(namespacedEvents).on(namespacedEvents, (e, formData) => {
            state.currentFilters = formData || [];
            View.refresh(rootNode);
        });
    }

    /**
     * Re-render loaded pages, or refresh if no cache exists.
     *
     * @param {object} root
     */
    static reset(root) {
        const rootNode = asRoot(root);
        const state = getState(rootNode);

        if (state.loadedPages.length === 0) {
            View.refresh(rootNode);
            return;
        }

        state.loadedPages.forEach((entityList, index) => {
            const pageContainer = getPageContainer(rootNode, index);
            renderEntities(rootNode, entityList)
                .then((html, js) => Templates.replaceNodeContents(pageContainer, html, js))
                .catch(Notification.exception);
        });
    }
}

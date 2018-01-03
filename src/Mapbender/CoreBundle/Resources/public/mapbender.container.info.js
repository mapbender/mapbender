/**
 * Container info helper.
 * For getting widget element container state (active/inactive).
 *
 * @param widget
 * @param options {onactive: function(), oninactive(): function()
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
function MapbenderContainerInfo(widget, options) {
    'use strict';

    var element = $(widget.element),
        toolBar = element.closest(".toolBar"),
        contentPane = element.closest(".contentPane"),
        sidePane = element.closest(".sidePane"),
        container = null,
        lastState = null;

    if (contentPane.size()) {
        container = contentPane;
    }

    if (toolBar.size()) {
        container = toolBar;
    }

    if (sidePane.size()) {
        container = sidePane;
    }

    this.isSidePane = function () {
        return sidePane.size() > 0;
    };

    this.isContentPane = function () {
        return contentPane.size() > 0;
    };

    this.isToolBar = function () {
        return toolBar.size() > 0;
    };

    this.isOnTop = function () {
        return toolBar.hasClass('top');
    };

    this.isOnBottom = function () {
        return toolBar.hasClass('bottom');
    };

    this.isOnLeft = function () {
        return sidePane.hasClass('left');
    };

    this.isOnRight = function () {
        return sidePane.hasClass('right');
    };

    this.getContainer = function () {
        return container;
    };

    function handleByTab(tab) {
        var tabContent = tab.parent().find("> div")[tab.index() + 1],
            hasWidget = $(tabContent).find(element).length > 0,
            state = hasWidget ? 'active' : 'inactive';

        if (lastState === state) {
            return;
        }

        if (state === "active") {
            if (options.onactive) {
                options.onactive();
            }
        } else {
            if (options.oninactive) {
                options.oninactive();
            }
        }

        lastState = state;
    }

    if (this.isSidePane()) {
        var accordion = $(".accordionContainer", sidePane),
            hasAccordion = accordion.length > 0;

        if (hasAccordion) {
            var tabs = accordion.find('> div.accordion'),
                currentTab = accordion.find('> div.accordion.active');

            tabs.on('click', function (e) {
                var tab = $(e.currentTarget);
                handleByTab(tab);
            });
            handleByTab(currentTab);
        }
    }
}
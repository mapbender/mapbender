/**
 * Container info helper.
 * For getting widget element container state (active/inactive).
 *
 * @param widget
 * @param options {onactive: function(), oninactive(): function()
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @deprecated for lack of separation of concerns
 * @deprecated because its primary function, activating / deactivating elements,
 *    only works in 'accordion'-style containers, but not in 'button'-style containers
 *
 * We can do element widget activation / deactivation in a safer, more uniform way now,
 * for that see element-sidepane.js.
 */
function MapbenderContainerInfo(widget, options) {
    'use strict';
    this.options = options;
    if (widget.element) {
        widget.element.data('MapbenderContainerInfo', this);
    }

    var element = $(widget.element),
        toolBar = element.closest(".toolBar"),
        contentPane = element.closest(".contentPane"),
        sidePane = element.closest(".sidePane"),
        container = null
    ;

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
}
/**
 * Container info helper.
 * For getting widget element container state (active/inactive).
 *
 * @param widget
 * @param options {onactive: function(), oninactive(): function()
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
function MapbenderContainerInfo(widget, options) {
    var element = (typeof widget === 'object') ? $(widget.element) : $('#' + widget);

    var toolBar = element.closest(".toolBar");
    var contentPane = element.closest(".contentPane");
    var sidePane = element.closest(".sidePane");
    var container = null;
    var lastState = null;

    if(contentPane.size()) {
        container = contentPane;
    }

    if(toolBar.size()) {
        container = toolBar;
    }

    if(sidePane.size()) {
        container = sidePane;
    }

    this.isSidePane = function() {
        return sidePane.size() > 0;
    };

    this.isContentPane = function() {
        return contentPane.size() > 0;
    };

    this.isToolBar = function() {
        return toolBar.size() > 0;
    };

    this.isOnTop = function() {
        return toolBar.hasClass('top');
    };

    this.isOnBottom = function() {
        return toolBar.hasClass('bottom');
    };

    this.isOnTop = function() {
        return toolBar.hasClass('top');
    };

    this.isOnLeft = function() {
        return sidePane.hasClass('left');
    };

    this.isOnRight = function() {
        return sidePane.hasClass('right');
    };

    this.getContainer = function() {
        return container;
    };

    if(this.isSidePane()) {
        var accordion = $(".accordionContainer", sidePane);
        var hasAccordion = accordion.length > 0;

        if(hasAccordion) {
            var tabs = accordion.find('> div.accordion');
            var currentTab = accordion.find('> div.accordion.active');

            tabs.on('click', function(e) {
                var tab = $(e.currentTarget);
                handleByTab(tab);
            });
            handleByTab(currentTab);
        }
    }

    function handleByTab(tab) {
        var tabContent = tab.parent().find("> div")[tab.index() + 1];
        var hasWidget = $(tabContent).find(element).length > 0;
        var state = hasWidget ? 'active' : 'inactive';

        if(lastState === state) {
            return;
        }

        if(state === "active") {
            if(options.onactive) {
                options.onactive();
            }
        } else {
            if(options.oninactive) {
                options.oninactive();
            }
        }

        lastState = state;
    }
}
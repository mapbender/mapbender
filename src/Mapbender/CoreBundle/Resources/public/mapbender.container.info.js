/**
 * Container info helper.
 * For getting widget element container state (active/inactive).
 *
 * @param widget
 * @param options {onactive: function(), oninactive(): function()
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
function MapbenderContainerInfo(widget, options) {
    var self = this;
    var element = $(widget.element);
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

    self.isSidePane = function() {
        return sidePane.size() > 0;
    };

    self.isContentPane = function() {
        return contentPane.size() > 0;
    };

    self.isToolBar = function() {
        return toolBar.size() > 0;
    };

    self.isOnTop = function() {
        return toolBar.hasClass('top');
    };

    self.isOnBottom = function() {
        return toolBar.hasClass('bottom');
    };

    self.isOnTop = function() {
        return toolBar.hasClass('top');
    };

    self.isOnLeft = function() {
        return sidePane.hasClass('left');
    };

    self.isOnRight = function() {
        return sidePane.hasClass('right');
    };

    self.getContainer = function() {
        return container;
    };

    if(self.isSidePane()) {
        var accordion = $(".accordionContainer", sidePane);
        var hasAccordion = accordion.length > 0;

        if(hasAccordion) {
            var tabs = accordion.find('> div.accordion');
            var currentTab = accordion.find('> div.accordion.active');

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

            tabs.on('click', function(e) {
                var tab = $(e.currentTarget);
                handleByTab(tab);
            });
            handleByTab(currentTab);
        }
    }

}
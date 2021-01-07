((function($) {
    function notifyElements(scope, state) {
        $('.mb-element[id]', scope).each(function() {
            var promise;
            if (state) {
                // Before we call 'reveal' an element, we want it to be done initializing
                promise = Mapbender.elementRegistry.waitReady(this.id);
            } else {
                // We do not wait for an element to become ready before we call 'hide'
                promise = Mapbender.elementRegistry.waitCreated(this.id);
            }
            promise.then(function(elementWidget) {
                var mci = $(elementWidget.element).data('MapbenderContainerInfo');
                if (mci) {
                    console.warn("Delegating sidepane element mangling to old-style MapbenderContainerInfo", mci, elementWidget);
                    var mciMethod = state ? mci.options.onactive : mci.options.oninactive;
                    if (mciMethod) {
                        (mciMethod)();
                        return;
                    }
                }
                // See mapbender.element.base.js for why those method names are what they are
                var method = state ? elementWidget.reveal : elementWidget.hide;
                if (typeof method === 'function') {
                    method.call(elementWidget);
                }
            });
        });
    }
    function addTabContainerElementEvents() {
        var $panels = $('>.container[id]', this);
        var currentPanel = null;
        function setCurrentTab() {
            var panelId = this.id.replace('tab', 'container');
            var panel = $panels.filter('#' + panelId + ':first').get(0);
            if (panel) {
                if (currentPanel) {
                    notifyElements(currentPanel, false);
                }
                notifyElements(panel, true);
                currentPanel = panel;
            }
        }
        // set initial active tab from .active class
        $('>.tabs >.tab.active:first', this).each(setCurrentTab);
        // follow further click events
        $('>.tabs', this).on('click', '>.tab[id]', setCurrentTab);
    }

    function addAccordionElementEvents() {
        var $panels = $('>.container-accordion', this);
        function panelFromHeader($panels, header) {
            var panelId = header && header.id
                && header.id.replace("accordion", "container");
            return panelId && $panels.filter('#' + panelId + ':first').get(0);
        }

        // set initial active panel from .active class
        var initialHeader = $('>.accordion.active:first', this).get(0);
        if (initialHeader) {
            var initialPanel = panelFromHeader($panels, initialHeader);
            if (initialPanel) {
                notifyElements(initialPanel, true);
            }
        }
        $('>.accordion', this).on('selected', function(e, tabData) {
            var activatedHeader = tabData.current && tabData.current.get(0);
            var deactivatedHeader = tabData.previous && tabData.previous.get(0);
            var activatedPanel = panelFromHeader($panels, activatedHeader);
            var deactivatedPanel = panelFromHeader($panels, deactivatedHeader);
            if (deactivatedPanel) {
                notifyElements(deactivatedPanel, false);
            }
            if (activatedPanel) {
                notifyElements(activatedPanel, true);
            }
        });
    }

    function processSidePane(node) {
        // Generate unified Element signals independent of sidepane organization
        // Accordions already generate events, but they are fired on the panel headers
        // where the contained Elements can't see them
        // .tabContainer and .tabContainerAlt do not produce any events at all
        // @todo: bring the base code for these two different pieces from FOM into
        //        Mapbender so they can be done properly
        var $accordions = $('>.accordionContainer', node);
        var $tabContainers = $('>.tabContainer, >.tabContainerAlt', node);
        if (!$accordions.length && !$tabContainers.length) {
            // try finding an accordion again with a deeper scan, but avoid picking nested accordions
            $accordions = $('.accordionContainer:not(.accordionContainer .accordionContainer)', node);
        }
        if ($tabContainers.length && $accordions.length) {
            console.warn("Found both tab containers and accordions in same sidepane, preferring tab containers",
                $tabContainers, $accordions, node);
        }

        if ($tabContainers.length) {
            $tabContainers.each(addTabContainerElementEvents);
        } else if ($accordions.length) {
            $accordions.each(addAccordionElementEvents);
        }
    }
    function processSidepanes() {
        $('.sideContent:not(.sideContent .sideContent)').each(function() {
            processSidePane(this);
        });
    }
    $(document).on("mapbender.setupfinished", processSidepanes);
})(jQuery));

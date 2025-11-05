((function($) {
    function updateResponsive($buttons) {
        var $activeButton = $buttons.filter('.active').first();
        if ($activeButton.length && !$($activeButton).is(':visible')) {
            var $firstVisibleButton = $buttons.filter(':visible').first();
            if ($firstVisibleButton.length) {
                // NOTE: toggles active classes and implicitly ends up calling notifyElements
                // @see initTabContainer
                $firstVisibleButton.click();
            }
        }
        var wholeSidePaneVisible = false;
        for (var i = 0; i < $buttons.length; ++i) {
            if ($($buttons[i]).css('display') !== 'none') {
                wholeSidePaneVisible = true;
                break;
            }
        }
        $buttons.first().closest('.sidePane').toggleClass('hidden', !wholeSidePaneVisible);
    }

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
                var method = state ? elementWidget.reveal : elementWidget.hide;
                if (typeof method === 'function') {
                    method.call(elementWidget);
                }
                $(document).trigger(state && 'mapbender.elementactivated' || 'mapbender.elementdeactivated', {
                    sender: null,
                    widget: elementWidget,
                    active: state
                });
            });
        });
    }
    function addTabContainerElementEvents() {
        var $panels = $('>.container[id]', this)
        var $buttons = $('>.tabs >.tab', this);
        var $sidePane = $buttons.first().closest('.sidePane');
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
                var activeIndex = $buttons.index(this);
                updateActiveIcon($sidePane, activeIndex);
            }
        }
        // set initial active tab from .active class
        $('>.tabs >.tab.active:first', this).each(setCurrentTab);
        // follow further click events
        $('>.tabs', this).on('click', '>.tab[id]', setCurrentTab);
        window.addEventListener('resize', function() {
            // Switch active "tab" if screen size change caused current active "tab" to visually disappear
            updateResponsive($buttons);
        });
        // Also select a different active "tab" if default active "tab" is already invisible on initialization
        updateResponsive($buttons);
    }

    function addAccordionElementEvents() {
        var $headers = $('>.accordion', this);
        var $panels = $('>.container-accordion', this);
        var $sidePane = $headers.first().closest('.sidePane');
        function panelFromHeader($panels, header) {
            var panelId = header && header.id
                && header.id.replace("accordion", "container");
            return panelId && $panels.filter('#' + panelId + ':first').get(0);
        }

        // set initial active panel from .active class
        var initialHeader = $('>.accordion.active', this).get(0);
        if (initialHeader) {
            var initialPanel = panelFromHeader($panels, initialHeader);
            if (initialPanel) {
                notifyElements(initialPanel, true);
            }
        }
        $headers.attr('tabindex', '0');
        $headers.on('keydown', function(event) {
            if (event.key === 'Enter') {
                $(this).click();
            }
        });
        $headers.on('selected', function(e, tabData) {
            var activatedHeader = tabData.current && tabData.current.get(0);
            var deactivatedHeader = tabData.previous && tabData.previous.get(0);
            var activatedPanel = panelFromHeader($panels, activatedHeader);
            var deactivatedPanel = panelFromHeader($panels, deactivatedHeader);
            if (deactivatedPanel) {
                notifyElements(deactivatedPanel, false);
            }
            if (activatedPanel) {
                notifyElements(activatedPanel, true);
                var activeIndex = $headers.index(activatedHeader);
                updateActiveIcon($sidePane, activeIndex);
            }
        });
        window.addEventListener('resize', function() {
            // Switch active panel if screen size change caused current active panel to visually disappear
            updateResponsive($headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
        updateResponsive($headers);
    }

    function addListGroupEvents() {
        var $headers = $('.list-group-item', this);
        var $panels = $('.container-list-group-item', this.parentElement);

        function panelFromHeader($panels, header) {
            var panelId = header && header.id
                && header.id.replace("list_group_item", "list_group_item_container");
            return panelId && $panels.filter('#' + panelId + ':first').get(0);
        }

        $headers.attr('tabindex', '0');
        $headers.on('keydown', function(event) {
            if (event.key === 'Enter') {
                $(this).click();
            }
        });

        // Listen for the custom 'selected' event that might be triggered by tabcontainer.js
        $headers.on('selected', function(e, tabData) {
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

        // Also listen for direct clicks to handle element notifications
        $headers.on('click', function() {
            var activatedPanel = panelFromHeader($panels, this);
            if (activatedPanel) {
                // Deactivate all other panels first
                $panels.each(function() {
                    if (this !== activatedPanel) {
                        notifyElements(this, false);
                    }
                });
                // Activate the selected panel
                notifyElements(activatedPanel, true);
                // Get the index of the active list item
                var activeIndex = $headers.index(this);
                updateActiveIcon($(this).closest('.sidePane'), activeIndex);
            }
        });

        window.addEventListener('resize', function() {
            // Switch active panel if screen size change caused current active panel to visually disappear
            updateResponsive($headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
        updateResponsive($headers);
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
        var $listGroup = $('>.listContainer', node);

        if (!$accordions.length && !$tabContainers.length && !$listGroup.length) {
            // try finding an accordion again with a deeper scan, but avoid picking nested accordions
            $accordions = $('.accordionContainer:not(.accordionContainer .accordionContainer)', node);
        }
        if ($tabContainers.length + $accordions.length + $listGroup.length > 1) {
            console.warn("Found tab containers, accordions and lists in same sidepane, preferring tab containers",
                $tabContainers, $accordions, $listGroup, node);
        }

        if ($tabContainers.length) {
            $tabContainers.each(addTabContainerElementEvents);
        } else if ($accordions.length) {
            $accordions.each(addAccordionElementEvents);
        } else if ($listGroup.length) {
            $listGroup.each(addListGroupEvents);
        }
    }

    function buildElementIcons($sidePane) {
        // Get all visible elements in the sidepane
        var $elementIcons = $sidePane.find('.toggleSideBar .element-icons');

        // Get all tab/accordion/list-item elements that are visible
        var $tabButtons = $sidePane.find('.tabs > .tab:visible');
        var $accordionButtons = $sidePane.find('.accordionContainer > .accordion:visible');
        var $listItems = $sidePane.find('.list-group-item:visible');

        var $allButtons = $tabButtons.add($accordionButtons).add($listItems);

        // For each visible button, try to find and copy its icon
        $allButtons.each(function() {
            var $button = $(this);
            var $icon = $button.find('.js-mb-icon').first().clone();
            if ($icon.length) {
                $elementIcons.append($icon);
            } else {
                // If no icon found, add a placeholder
                $elementIcons.append('<span class="element-icon-placeholder"></span>');
            }
        });

        // If there are accordions or tabs, set the first one as active by default
        if($accordionButtons.length || $tabButtons.length > 0) {
            updateActiveIcon($('.sidePane'), 0);
        }
    }

    function processSidepanes() {
        $('.sideContent:not(.sideContent .sideContent)').each(function() {
            processSidePane(this);
        });

        // Build element icons dynamically for each sidepane
        $('.sidePane').each(function() {
            var $sidePane = $(this);
            buildElementIcons($sidePane);

            // If sidepane starts closed, add closed class to toggleSideBar
            if ($sidePane.hasClass('closed')) {
                var $btn = $sidePane.find('.toggleSideBar');
                if (!$btn.hasClass('closed')) {
                    $btn.addClass('closed');
                    var $icon = $btn.children('i').first();
                    $icon.removeClass('fa-xmark').addClass('fa-bars');
                    updateToggleButtonIcons($btn);
                }
            }
        });

        // Initialize toggle button icons for any sidepanes that are already closed
        $('.sidePane .toggleSideBar.closed').each(function() {
            var $btn = $(this);
            updateToggleButtonIcons($btn);
        });
    }

    function updateActiveIcon($sidePane, activeIndex) {
        // Get all icons in the element-icons container
        var $allIcons = $sidePane.find('.toggleSideBar .element-icons > *');

        // Remove active class from all icons
        $allIcons.removeClass('active-element-icon');

        // Add active class to the icon at the activeIndex
        if (activeIndex >= 0 && activeIndex < $allIcons.length) {
            $allIcons.eq(activeIndex).addClass('active-element-icon');
        }
    }

    function updateToggleButtonIcons($btn) {
        var $elementIcons = $btn.find('.element-icons');

        // Show element icons when closed, hide when open
        $elementIcons.toggleClass('hidden', !$btn.hasClass('closed'));
    }

    $(document).on('click', '.sidePane .toggleSideBar', function(e) {
        var $btn = $(this);
        var $icon = $btn.children('i').first();

        $btn.toggleClass('closed');
        $icon.toggleClass('fa-bars fa-xmark');

        updateToggleButtonIcons($btn);

        e.stopPropagation();
    });

    // Listen for back button event to deactivate elements
    $(document).on('listgroup:back', '.sideContent', function(e, $container) {
        if ($container && $container.length) {
            notifyElements($container.get(0), false);
        }
    });

    $(document).on("mapbender.setupfinished", processSidepanes);
})(jQuery));

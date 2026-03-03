((function($) {
    var lastFocusedListItem = null; // Global tracking of last focused list item
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
                // Calculate index considering only non-inline buttons
                var $nonInlineButtons = $buttons.not('.inline');
                var activeIndex = $nonInlineButtons.index(this);
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
                // Calculate index considering only non-inline headers
                var $nonInlineHeaders = $headers.not('.inline');
                var activeIndex = $nonInlineHeaders.index(activatedHeader);
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

        function getFocusableElements(container) {
            // Get all potentially focusable elements
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            var $allElements = $(container).find(focusableSelectors).filter(':visible').not('[disabled]').not('[tabindex="-1"]');

            // Filter out non-first radio buttons in groups (only count first radio per name)
            var seenRadioNames = {};
            return $allElements.filter(function() {
                var $el = $(this);
                if ($el.attr('type') === 'radio') {
                    var name = $el.attr('name');
                    if (seenRadioNames[name]) {
                        return false; // Skip non-first radio buttons in this group
                    }
                    seenRadioNames[name] = true;
                }
                return true;
            });
        }

        function isLastFocusableElement(container, currentElement) {
            // Check if currentElement is the last effectively focusable element
            // This accounts for radio button groups where only the first is in the tab order
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            var $allElements = $(container).find(focusableSelectors).filter(':visible').not('[disabled]').not('[tabindex="-1"]');

            if ($allElements.length === 0) return false;

            var $current = $(currentElement);
            var currentIndex = $allElements.index($current);

            if (currentIndex === -1) return false; // Not found in container

            // Check if there are any focusable elements after current one
            var hasElementsAfter = false;
            for (var i = currentIndex + 1; i < $allElements.length; i++) {
                var $el = $allElements.eq(i);
                // Skip elements that are in the same radio group but not the first
                if ($el.attr('type') === 'radio') {
                    var radioName = $el.attr('name');
                    var $firstInGroup = $allElements.filter('[type="radio"][name="' + radioName + '"]').first();
                    if ($firstInGroup[0] === $el[0]) {
                        // This is the first radio in the group, so it counts as a focusable element
                        hasElementsAfter = true;
                        break;
                    }
                } else {
                    // Not a radio button, counts as focusable
                    hasElementsAfter = true;
                    break;
                }
            }

            return !hasElementsAfter; // Return true if there are no focusable elements after current
        }

        function focusFirstFocusableElement(container) {
            if (!container) return;
            var $focusable = getFocusableElements(container).first();
            // Skip if interactive help tour is active
            var isInteractiveHelpActive = $('.popover-interactive-help:visible').length > 0;
            if ($focusable.length && !isInteractiveHelpActive) {
                $focusable.focus();
            }
        }

        function setupFocusTrap(container) {
            if (!container) return;
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            var $toggleSideBar = $(container).closest('.sidePane').find('.toggleSideBar');
            var $toggleIcon = $toggleSideBar.children('i').first();

            // Use event delegation to capture Tab events on any focusable element within the container
            $(container).on('keydown.focustrap', focusableSelectors, function(event) {
                if (event.key !== 'Tab') return;

                // Re-evaluate focusable elements dynamically (in case visibility changed)
                var $focusableElements = getFocusableElements(container);
                if ($focusableElements.length === 0) return;

                var $firstElement = $focusableElements.first();
                var $currentElement = $(document.activeElement);

                if (event.shiftKey) {
                    // Shift+Tab on first element -> focus toggleSideBar icon
                    if ($currentElement[0] === $firstElement[0]) {
                        event.preventDefault();
                        if ($toggleIcon.length) {
                            $toggleIcon.focus();
                        }
                    }
                } else {
                    // Tab on last element -> focus toggleSideBar icon
                    if (isLastFocusableElement(container, $currentElement[0])) {
                        event.preventDefault();
                        if ($toggleIcon.length) {
                            $toggleIcon.focus();
                        } else {
                            $firstElement.focus();
                        }
                    }
                }
            });

            // Add handler to toggleSideBar icon to handle Shift+Tab back to last element
            if ($toggleIcon.length) {
                $toggleIcon.on('keydown.toggletrap', function(event) {
                    if (event.key !== 'Tab' || !event.shiftKey) return;

                    // Shift+Tab on toggleSideBar icon -> focus last element of container
                    var $focusableElements = getFocusableElements(container);
                    event.preventDefault();
                    if ($focusableElements.length) {
                        $focusableElements.last().focus();
                    }
                });
            }
        }

        function removeFocusTrap(container) {
            if (!container) return;
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            $(container).off('keydown.focustrap', focusableSelectors);
            // Also remove the toggletrap handler from toggleSideBar icon
            $(container).closest('.sidePane').find('.toggleSideBar i').off('keydown.toggletrap');
        }

        $headers.attr('tabindex', '0');
        $headers.on('keydown', function(event) {
            if (event.key === 'Enter') {
                // Store the currently focused element before opening the panel
                lastFocusedListItem = this;
                $(this).click();
            } else if (event.key === 'Tab') {
                // Tab on last list item should go to toggleSideBar icon
                var $sidePane = $(this).closest('.sidePane');
                var $toggleSideBar = $sidePane.find('.toggleSideBar');
                var $toggleIcon = $toggleSideBar.children('i').first();
                var $lastHeader = $headers.last();

                if (!event.shiftKey && this === $lastHeader[0] && $toggleIcon.length) {
                    event.preventDefault();
                    $toggleIcon.focus();
                } else if (event.shiftKey && this === $headers[0] && $toggleIcon.length) {
                    // Shift+Tab on first list item should go to toggleSideBar icon (wrap around)
                    event.preventDefault();
                    $toggleIcon.focus();
                }
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
                removeFocusTrap(deactivatedPanel);
            }
            if (activatedPanel) {
                notifyElements(activatedPanel, true);
                focusFirstFocusableElement(activatedPanel);
                setupFocusTrap(activatedPanel);
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
                        removeFocusTrap(this);
                    }
                });
                // Activate the selected panel
                notifyElements(activatedPanel, true);

                // Add active class to trigger CSS animation
                $(activatedPanel).addClass('active');

                // Add list-shifted class to listContainer to shift the list view
                var $listContainer = $(activatedPanel).closest('.sideContent').find('.listContainer');
                $listContainer.addClass('list-shifted');

                // Wait for animation to complete before setting focus
                var transitionHandler = function() {
                    activatedPanel.removeEventListener('transitionend', transitionHandler);
                    focusFirstFocusableElement(activatedPanel);
                    setupFocusTrap(activatedPanel);
                };
                activatedPanel.addEventListener('transitionend', transitionHandler);

                // Fallback in case transitionend doesn't fire (e.g., if transition is disabled)
                setTimeout(function() {
                    focusFirstFocusableElement(activatedPanel);
                    setupFocusTrap(activatedPanel);
                }, 350);

                // Get the index of the active list item (considering only non-inline items)
                var $nonInlineHeaders = $headers.not('.inline');
                var activeIndex = $nonInlineHeaders.index(this);
                updateActiveIcon($(this).closest('.sidePane'), activeIndex);
            }
        });

        // Add Shift+Tab handler to toggleSideBar to return focus to list items
        var $sidePane = $headers.first().closest('.sidePane');
        var $toggleSideBar = $sidePane.find('.toggleSideBar');
        if ($toggleSideBar.length) {
            $toggleSideBar.on('keydown.listgroup-toggle', function(event) {
                if (event.key === 'Tab' && event.shiftKey) {
                    // Shift+Tab on toggleSideBar -> focus last list item
                    event.preventDefault();
                    $headers.last().focus();
                }
            });
        }

        window.addEventListener('resize', function() {
            // Switch active panel if screen size change caused current active panel to visually disappear
            updateResponsive($headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
        updateResponsive($headers);
    }

    // Handle back button clicks and keyboard events
    $(document).on('click keydown', '.list-back-btn', function(event) {
        // Only handle clicks and Enter key
        if (event.type === 'keydown' && event.key !== 'Enter') {
            return;
        }
        if (event.type === 'keydown') {
            event.preventDefault();
        }

        var $backBtn = $(this);
        var $container = $backBtn.closest('.container-list-group-item');
        var containerId = $container.attr('id');

        if (containerId) {
            // Find the corresponding list-group-item by parsing the ID
            var correspondingItemId = containerId.replace('list_group_item_container', 'list_group_item');
            var $correspondingItem = $('body').find('#' + correspondingItemId);

            // Find the listContainer and remove the list-shifted class
            var $listContainer = $container.closest('.sideContent').find('.listContainer');
            $listContainer.removeClass('list-shifted');

            // Remove active class from container to trigger animation back
            $container.removeClass('active');

            // Notify elements that the container is being deactivated
            notifyElements($container.get(0), false);

            // Remove focus trap from the closing container
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            $container.find(focusableSelectors).off('keydown.focustrap');

            // Focus management after transition completes
            var focusAfterTransition = function() {
                $container.get(0).removeEventListener('transitionend', focusAfterTransition);

                // Try to restore lastFocusedListItem, otherwise focus the corresponding item
                if (lastFocusedListItem && $(lastFocusedListItem).closest('.list-group').length) {
                    $(lastFocusedListItem).focus();
                    lastFocusedListItem = null;
                } else if ($correspondingItem.length) {
                    $correspondingItem.focus();
                }
            };

            // Set up transition listener
            if ($container.length) {
                $container.get(0).addEventListener('transitionend', focusAfterTransition);

                // Fallback timeout
                setTimeout(focusAfterTransition, 350);
            }
            updateActiveIcon($container.closest('.sidePane'), -1);
        }
    });

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
        $elementIcons.empty(); // Clear existing icons first

        // Get all tab/accordion/list-item elements that are visible (excluding inline elements)
        var $tabTitles = $sidePane.find('.tabs > .tab:visible:not(.inline)');
        var $accordionTitles = $sidePane.find('.accordionContainer > .accordion:visible:not(.inline)');
        var $listItems = $sidePane.find('.list-group-item:visible:not(.inline)');

        var $allTitles = $tabTitles.add($accordionTitles).add($listItems);

        $allTitles.each(function() {
            const $title = $(this);
            const $icon = $title.find('.js-mb-icon').first().clone();

            const buttonId = $title.attr('id');
            const $iconWrapper = $('<span class="sidePane--collapsed__element-icon"></span>')
                .data('button-id', buttonId)
                .attr('tabindex', '0')
                .attr('role', 'button')
                .attr('title', $title.text().trim());
            if ($icon.length) $iconWrapper.append($icon);
            $elementIcons.append($iconWrapper);
        });

        // Add click handlers to icons to activate corresponding elements
        $elementIcons.on('click.element-icon', '[class*="element-icon"]', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $iconElement = $(this);
            var buttonId = $iconElement.data('button-id');
            var $button = $sidePane.find('#' + buttonId);

            if ($button && $button.length) {
                // Trigger the corresponding button click first (this will activate the panel)
                $button.click();

                // Then open the sidepane if it's closed (after a short delay to let the panel activate)
                var $toggleSideBar = $sidePane.find('.toggleSideBar');
                if ($toggleSideBar.hasClass('closed')) {
                    setTimeout(function() {
                        $toggleSideBar.click();
                    }, 50);
                }
            }
        });

        // Add Enter handler for keyboard activation of icons
        $elementIcons.on('keydown.element-icon', '[class*="element-icon"]', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                $(this).click();
            }
        });

        // Add Tab navigation within the icon list when sidepane is closed
        // (This is now handled by the global keydown handler below)
        // // If there are accordions or tabs, set the first one as active by default
        if($accordionTitles.length || $tabTitles.length > 0) {
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

    $(document).on('keydown', '.sidePane .toggleSideBar i', function(e) {
        if (e.key !== 'Tab') return;

        var $toggleButton = $(this).closest('.toggleSideBar');
        var $sidePane = $toggleButton.closest('.sidePane');
        var $elementIcons = $toggleButton.find('.element-icons');
        var $icons = $elementIcons.find('[class*="element-icon"]');

        if ($toggleButton.hasClass('closed')) {
            // Sidepane is closed - navigate through icons
            if ($icons.length > 0) {
                if (e.shiftKey) {
                    // Shift+Tab on toggle button: go to last icon
                    e.preventDefault();
                    $icons.last().focus();
                } else {
                    // Tab on toggle button: go to first icon
                    e.preventDefault();
                    $icons.first().focus();
                }
            }
        } else {
            // Sidepane is open - Shift+Tab should go to last element in panel
            if (e.shiftKey) {
                e.preventDefault();
                // Find the active panel and get its last focusable element
                var $activeContainer = $sidePane.find('.container-accordion.active, .container-list-group-item.active, .container.active').first();
                if ($activeContainer.length) {
                    var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
                    var $focusable = $activeContainer.find(focusableSelectors).filter(':visible').not('[disabled]').not('[tabindex="-1"]').last();
                    if ($focusable.length) {
                        $focusable.focus();
                    }
                }
            }
        }
    });

    $(document).on('keydown', '.sidePane .toggleSideBar .element-icons [class*="element-icon"]', function(e) {
        if (e.key !== 'Tab') return;

        var $elementIcons = $(this).closest('.element-icons');
        var $icons = $elementIcons.find('[class*="element-icon"]');
        var $toggleButton = $elementIcons.closest('.toggleSideBar');
        var $toggleIcon = $toggleButton.children('i').first();
        var $currentIcon = $(this);
        var currentIndex = $icons.index($currentIcon);

        if (e.shiftKey) {
            // Shift+Tab: go to previous icon or to toggle button (fa-bars/fa-xmark)
            e.preventDefault();
            if (currentIndex > 0) {
                $icons.eq(currentIndex - 1).focus();
            } else {
                // On first icon, go to toggle button
                $toggleIcon.focus();
            }
        } else {
            // Tab: go to next icon or to next element outside sidepane
            if (currentIndex < $icons.length - 1) {
                e.preventDefault();
                $icons.eq(currentIndex + 1).focus();
            }
        }
    });

    $(document).on('click', '.sidePane .toggleSideBar', function(e) {
        var $btn = $(this);
        var $icon = $btn.children('i').first();

        $btn.toggleClass('closed');
        $icon.toggleClass('fa-bars fa-xmark');

        updateToggleButtonIcons($btn);

        // When closing the sidepane, focus the fa-bars icon
        if ($btn.hasClass('closed')) {
            setTimeout(function() {
                $icon.focus();
            }, 50);
        }

        e.stopPropagation();
    });

    $(document).on('keydown', '.sidePane .toggleSideBar', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            $(this).click();
        }
    });

    // Listen for back button event to deactivate elements
    $(document).on('listgroup:back', '.sideContent', function(e, $container) {
        if ($container && $container.length) {
            notifyElements($container.get(0), false);
            updateActiveIcon($container.closest('.sidePane'), -1);
        }
    });

    $(document).on("mapbender.setupfinished", processSidepanes);
})(jQuery));

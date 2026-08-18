window.Mapbender.SidePaneList = class SidepaneList extends Mapbender.SidePaneHandler {

    getType() {
        return 'list';
    }

    setup() {
        const sidePane = this.sidePane;
        var $headers = $('.list-group-item', this.$container);
        var $panels = $('.container-list-group-item', this.$container.parent());

        function panelFromHeader($panels, header) {
            var panelId = header && header.id
                && header.id.replace("list_group_item", "list_group_item_container");
            return panelId && $panels.filter('#' + panelId).first().get(0);
        }

        function getFocusableElements(container) {
            // Get all potentially focusable elements
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            var $allElements = $(container).find(focusableSelectors).filter(':visible').not('[disabled]').not('[tabindex="-1"]');

            // Filter out non-first radio buttons in groups (only count first radio per name)
            var seenRadioNames = {};
            return $allElements.filter(function () {
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
                $focusable.trigger('focus');
            }
        }

        function setupFocusTrap(container) {
            if (!container) return;
            var focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
            var $toggleSideBar = $(container).closest('.sidePane').find('.toggleSideBar');
            var $toggleIcon = $toggleSideBar.children('i').first();

            // Use event delegation to capture Tab events on any focusable element within the container
            $(container).on('keydown.focustrap', focusableSelectors, function (event) {
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
                            $toggleIcon.trigger('focus');
                        }
                    }
                } else {
                    // Tab on last element -> focus toggleSideBar icon
                    if (isLastFocusableElement(container, $currentElement[0])) {
                        event.preventDefault();
                        if ($toggleIcon.length) {
                            $toggleIcon.trigger('focus');
                        } else {
                            $firstElement.trigger('focus');
                        }
                    }
                }
            });

            // Add handler to toggleSideBar icon to handle Shift+Tab back to last element
            if ($toggleIcon.length) {
                $toggleIcon.on('keydown.toggletrap', function (event) {
                    if (event.key !== 'Tab' || !event.shiftKey) return;

                    // Shift+Tab on toggleSideBar icon -> focus last element of container
                    var $focusableElements = getFocusableElements(container);
                    event.preventDefault();
                    if ($focusableElements.length) {
                        $focusableElements.last().trigger('focus');
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
        $headers.on('keydown', function (event) {
            if (event.key === 'Enter') {
                // Store the currently focused element before opening the panel
                sidePane.lastFocusedListItem = this;
                $(this).trigger('click');
            } else if (event.key === 'Tab') {
                // Tab on last list item should go to toggleSideBar icon
                var $sidePane = $(this).closest('.sidePane');
                var $toggleSideBar = $sidePane.find('.toggleSideBar');
                var $toggleIcon = $toggleSideBar.children('i').first();
                var $lastHeader = $headers.last();

                if (!event.shiftKey && this === $lastHeader[0] && $toggleIcon.length) {
                    event.preventDefault();
                    $toggleIcon.trigger('focus');
                } else if (event.shiftKey && this === $headers[0] && $toggleIcon.length) {
                    // Shift+Tab on first list item should go to toggleSideBar icon (wrap around)
                    event.preventDefault();
                    $toggleIcon.trigger('focus');
                }
            }
        });

        // Listen for the custom 'selected' event that might be triggered by tabcontainer.js
        $headers.on('selected', function (e, tabData) {
            var activatedHeader = tabData.current && tabData.current.get(0);
            var deactivatedHeader = tabData.previous && tabData.previous.get(0);
            var activatedPanel = panelFromHeader($panels, activatedHeader);
            var deactivatedPanel = panelFromHeader($panels, deactivatedHeader);

            if (deactivatedPanel) {
                sidePane.notifyElements(deactivatedPanel, false);
                removeFocusTrap(deactivatedPanel);
            }
            if (activatedPanel) {
                sidePane.notifyElements(activatedPanel, true);
                focusFirstFocusableElement(activatedPanel);
                setupFocusTrap(activatedPanel);
            }
        });

        // Also listen for direct clicks to handle element notifications
        $headers.on('click', function () {
            var activatedPanel = panelFromHeader($panels, this);
            if (activatedPanel) {
                // Deactivate all other panels first
                $panels.each(function () {
                    if (this !== activatedPanel) {
                        sidePane.notifyElements(this, false);
                        removeFocusTrap(this);
                    }
                });
                // Activate the selected panel
                sidePane.notifyElements(activatedPanel, true);

                // Add active class to trigger CSS animation
                $(activatedPanel).addClass('active');

                // Add list-shifted class to listContainer to shift the list view
                var $listContainer = $(activatedPanel).closest('.sideContent').find('.listContainer');
                $listContainer.addClass('list-shifted');

                // Wait for animation to complete before setting focus
                var transitionHandler = function () {
                    activatedPanel.removeEventListener('transitionend', transitionHandler);
                    focusFirstFocusableElement(activatedPanel);
                    setupFocusTrap(activatedPanel);
                };
                activatedPanel.addEventListener('transitionend', transitionHandler);

                // Fallback in case transitionend doesn't fire (e.g., if transition is disabled)
                setTimeout(function () {
                    focusFirstFocusableElement(activatedPanel);
                    setupFocusTrap(activatedPanel);
                }, 350);

                // Get the index of the active list item (considering only non-inline items)
                var $nonInlineHeaders = $headers.not('.inline');
                var activeIndex = $nonInlineHeaders.index(this);
                sidePane.updateActiveIcon(activeIndex);
            }
        });

        // Add Shift+Tab handler to toggleSideBar to return focus to list items
        var $sidePane = $headers.first().closest('.sidePane');
        var $toggleSideBar = $sidePane.find('.toggleSideBar');
        if ($toggleSideBar.length) {
            $toggleSideBar.on('keydown.listgroup-toggle', function (event) {
                if (event.key === 'Tab' && event.shiftKey) {
                    // Shift+Tab on toggleSideBar -> focus last list item
                    event.preventDefault();
                    $headers.last().trigger('focus');
                }
            });
        }

        window.addEventListener('resize', function () {
            // Switch active panel if screen size change caused current active panel to visually disappear
            sidePane.updateResponsive($headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
        sidePane.updateResponsive($headers);
    }

    getElementTitleContainers() {
        return this.$container.find('.list-group-item:not(.inline)');
    }
}

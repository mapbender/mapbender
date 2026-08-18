/* global $, jQuery */
window.Mapbender = Mapbender || {};

/**
 * Abstract class that encapsulates functionality for different sidepane types (tabs, accordion, list, unformatted)
 */
window.Mapbender.SidePaneHandler = class SidePaneHandler {

    constructor(sidePane, container) {
        /** @type {SidePane} */
        this.sidePane = sidePane;
        /** @type {jQuery} */
        this.$container = container;
    }

    /**
     * @return {string}
     */
    getType() {
    }

    setup() {

    }

    /**
     * @return {jQuery}
     */
    getElementTitleContainers() {
        return $([]);
    }


}

/**
 * Class that encapsulates all functionality regarding the fullscreen template's sidepane
 * Emits the 'sidepane-resize' event on the .sidePane element when resized.
 */
window.Mapbender.SidePane = class SidePane {
    constructor(element) {
        /** @type {jQuery} **/
        this.$element = $(element);
        /** @type {jQuery} the button to toggle the sidepane and also the container for the element icons **/
        this.$switchButton = this.$element.find(".toggleSideBar");
        /** @type {jQuery} **/
        this.$switchIcon = this.$switchButton.children('i').first();
        /** @type {HTMLDivElement} **/
        this.element = this.$element[0];
        this.pointerPosition = 0;
        this.isLeft = this.$element.hasClass('left');
        // initial state overridden in _setupInitialState
        this.isOpen = true;
        this.isAnimating = false;
        // stores resolve methods of promises that will be resolved when animation finishes
        this.pendingResolves = [];

        this.BORDER_SIZE = 'ontouchstart' in document ? 12 : 6;
        // if you want to customize the max/min size use custom CSS (min-width/max-width on .sidePane.resizable),
        this.MAX_SIZE_WINDOW_PERCENTAGE = 0.95;
        this.MIN_SIZE_PX = 120;

        this.lastFocusedListItem = null; // Global tracking of last focused list item

        /** @type {Mapbender.SidePaneHandler} */
        this.handler = this._initSidePaneHandler();

        this.boundResizeSidepane = this._resizeSidepane.bind(this);
        this._setupInitialState();
        this._setupEvents();
        this._setupKeyEvents();
    }

    /**
     * toggles the current sidepane opened state.
     * Returns a promise that will resolve once the animation finished.
     * @return {Promise<void>}
     */
    async toggle() {
        return await this.setOpen(!this.isOpen);
    }

    /**
     * sets the sidepane state to open or closed. Returns a promise that
     * will resolve once the animation finished (or instantly if the sidepane
     * already is at the desired state).
     * @return {Promise<void>}
     */
    setOpen(open) {
        if (open === this.isOpen && !this.isAnimating) {
            return Promise.resolve();
        }
        if (open === this.isOpen && this.isAnimating) {
            return new Promise((resolve) => {
                this.pendingResolves.push(resolve);
            });
        }

        // stop current animations if applicable
        this.$element.stop(true);

        return new Promise((resolve) => {
            const wasOpen = this.isOpen;
            this.isOpen = open;
            this.isAnimating = true;
            this.pendingResolves.push(resolve);

            const animation = {};
            const align = this.isLeft ? 'left' : 'right';
            if (wasOpen) {
                animation[align] = "-" + this.$element.outerWidth(true) + "px";
            } else {
                animation[align] = "0px";
            }

            this._setToggleButtonState(open);
            this._updateToggleButtonIcons();

            // When closing the sidepane, focus the fa-bars icon
            if (this.$switchButton.hasClass('closed')) {
                setTimeout(() => {
                    this.$switchIcon.focus();
                }, 50);
            }

            this.$element.addClass('animating');
            this.$element.animate(animation, {
                duration: 300,
                complete: () => {
                    this.$element.removeClass('animating').toggleClass('closed', wasOpen);
                    for (const _resolve of this.pendingResolves) {
                        _resolve();
                    }
                    this.pendingResolves = [];
                    this.isAnimating = false;
                }
            });
        });
    };


    /**
     * @return {"tabs" | "accordion" | "list" | "unformatted" }
     */
    sidePaneType() {
        return this.handler.getType();
    }

    _initSidePaneHandler() {
        const $container = this.$element.find('.sideContent > :first-child');
        if ($container.hasClass('tabContainerAlt')) {
            return new Mapbender.SidePaneTabs(this, $container);
        }
        if ($container.hasClass('accordionContainer')) {
            return new Mapbender.SidePaneAccordion(this, $container);
        }
        if ($container.hasClass('listContainer')) {
            return new Mapbender.SidePaneList(this, $container);
        }
        return new Mapbender.SidePaneUnformatted(this, this.$element);
    }

    _setToggleButtonState(open) {
        this.$switchButton.toggleClass('closed', !open);
        this.$switchIcon.toggleClass('fa-bars', !open);
        this.$switchIcon.toggleClass('fa-xmark', open);
    }

    _updateToggleButtonIcons() {
        // Show element icons when closed, hide when open
        const $elementIcons = this.$switchButton.find('.element-icons');
        $elementIcons.toggleClass('hidden', this.isOpen);
    }

    _setupInitialState() {
        this.isOpen = this._getResponsiveDefaultOpen();

        if (this.isOpen) {
            this.$element.css('left', 'initial').css('right', this.isLeft ? 'initial' : '0px');
            this.$element.removeClass('closed');
        } else {
            this.$element.addClass('closed');
            if (this.isLeft) {
                this.$element.css({left: (this.$element.outerWidth(true) * -1) + "px"});
            } else {
                this.$element.css({right: (this.$element.outerWidth(true) * -1) + "px"});
            }
        }
        this._setToggleButtonState(this.isOpen);
    }

    _getResponsiveDefaultOpen() {
        if (this.$element.hasClass('-js-closed-no')) return true;
        if (this.$element.hasClass('-js-closed-yes')) return false;

        const isMobile = window.innerWidth < Mapbender.responsiveMenu.desktopBreakpoint;
        return (
            (this.$element.hasClass('-js-closed-only_desktop') && isMobile) ||
            (this.$element.hasClass('-js-closed-only_mobile') && !isMobile)
        );
    }

    _updateResponsiveSidepaneVisibility($elements) {
        let wholeSidePaneVisible = false;
        for (let i = 0; i < $elements.length; ++i) {
            if ($($elements[i]).css('display') !== 'none') {
                wholeSidePaneVisible = true;
                break;
            }
        }
        this.$element.toggleClass('hidden', !wholeSidePaneVisible);
    }

    _setupEvents() {
        this.$switchButton.on('click', (e) => {
            e.stopPropagation();
            // noinspection JSIgnoredPromiseFromCall
            this.toggle();
        });

        $(document).on('pointerdown', '.sidePane.resizable', (e) => {
            const paneRect = e.target.getBoundingClientRect();
            const offsetX = e.clientX - paneRect.left;

            if ((this.isLeft && this.sidePaneWidth() - offsetX < this.BORDER_SIZE) || (!this.isLeft && offsetX < this.BORDER_SIZE)) {
                this.pointerPosition = e.x;
                $("body").addClass("prevent-selection");
                document.addEventListener("pointermove", this.boundResizeSidepane);
            }

            $(document).one('pointercancel pointerup', () => {
                document.removeEventListener("pointermove", this.boundResizeSidepane);
                $("body").removeClass("prevent-selection");
            });
        });

        // Add click and enter keydown handlers to element icons to activate corresponding elements
        const self = this;
        this.$switchButton.on('click keydown', '.element-icon', async function (e) {
            if (e.type === 'keydown' && e.key !== 'Enter') return;

            e.preventDefault();
            e.stopPropagation();

            const buttonId = $(this).data('button-id');
            const $button = self.$element.find('#' + buttonId);

            if ($button && $button.length) {
                // Trigger the corresponding button click first (this will activate the panel)
                $button.trigger('click');
                await self.setOpen(true);
                $button.focus();
            }
        });

        $(document).one("mapbender.setupfinished", this._onMapbenderSetupFinished.bind(this));

        // make sure sidebar is resizable even when making the window smaller
        window.addEventListener("resize", this.constrainSize.bind(this), false);
    }

    _setupKeyEvents() {
        const self = this;
        // Handle back button clicks and keyboard events
        $(document).on('click keydown', '.list-back-btn', function (event) {
            // Only handle clicks and Enter key
            if (event.type === 'keydown' && event.key !== 'Enter') {
                return;
            }
            if (event.type === 'keydown') {
                event.preventDefault();
            }

            const $backBtn = $(this);
            const $container = $backBtn.closest('.container-list-group-item');
            const containerId = $container.attr('id');

            if (containerId) {
                // Find the corresponding list-group-item by parsing the ID
                const correspondingItemId = containerId.replace('list_group_item_container', 'list_group_item');
                const $correspondingItem = $('body').find('#' + correspondingItemId);

                // Find the listContainer and remove the list-shifted class
                const $listContainer = $container.closest('.sideContent').find('.listContainer');
                $listContainer.removeClass('list-shifted');

                // Remove active class from container to trigger animation back
                $container.removeClass('active');

                // Notify elements that the container is being deactivated
                self.notifyElements($container.get(0), false);

                // Remove focus trap from the closing container
                const focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
                $container.find(focusableSelectors).off('keydown.focustrap');

                // Focus management after transition completes
                const focusAfterTransition = function () {
                    $container.get(0).removeEventListener('transitionend', focusAfterTransition);

                    // Try to restore lastFocusedListItem, otherwise focus the corresponding item
                    if (self.lastFocusedListItem && $(self.lastFocusedListItem).closest('.list-group').length) {
                        $(self.lastFocusedListItem).trigger('focus');
                        self.lastFocusedListItem = null;
                    } else if ($correspondingItem.length) {
                        $correspondingItem.trigger('focus');
                    }
                };

                // Set up transition listener
                if ($container.length) {
                    $container.get(0).addEventListener('transitionend', focusAfterTransition);

                    // Fallback timeout
                    setTimeout(focusAfterTransition, 350);
                }
                self.updateActiveIcon(-1);
            }
        });

        $(document).on('keydown', '.sidePane .toggleSideBar i', function (e) {
            if (e.key !== 'Tab') return;

            const $toggleButton = $(this).closest('.toggleSideBar');
            const $sidePane = $toggleButton.closest('.sidePane');
            const $elementIcons = $toggleButton.find('.element-icons');
            const $icons = $elementIcons.find('[class*="element-icon"]');

            if ($toggleButton.hasClass('closed')) {
                // Sidepane is closed - navigate through icons
                if ($icons.length > 0) {
                    if (e.shiftKey) {
                        // Shift+Tab on toggle button: go to last icon
                        e.preventDefault();
                        $icons.last().trigger('focus');
                    } else {
                        // Tab on toggle button: go to first icon
                        e.preventDefault();
                        $icons.first().trigger('focus');
                    }
                }
            } else {
                // Sidepane is open - Shift+Tab should go to last element in panel
                if (e.shiftKey) {
                    e.preventDefault();
                    // Find the active panel and get its last focusable element
                    const $activeContainer = $sidePane.find('.container-accordion.active, .container-list-group-item.active, .container.active').first();
                    if ($activeContainer.length) {
                        const focusableSelectors = 'a, button, input, select, textarea, .clickable, [tabindex]:not([tabindex="-1"])';
                        const $focusable = $activeContainer.find(focusableSelectors).filter(':visible').not('[disabled]').not('[tabindex="-1"]').last();
                        if ($focusable.length) {
                            $focusable.trigger('focus');
                        }
                    }
                }
            }
        });

        $(document).on('keydown', '.sidePane .toggleSideBar .element-icon', function (e) {
            if (e.key !== 'Tab') return;

            const $elementIcons = $(this).closest('.element-icons');
            const $icons = $elementIcons.find('[class*="element-icon"]');
            const $toggleButton = $elementIcons.closest('.toggleSideBar');
            const $toggleIcon = $toggleButton.children('i').first();
            const $currentIcon = $(this);
            const currentIndex = $icons.index($currentIcon);

            if (e.shiftKey) {
                // Shift+Tab: go to previous icon or to toggle button (fa-bars/fa-xmark)
                e.preventDefault();
                if (currentIndex > 0) {
                    $icons.eq(currentIndex - 1).trigger('focus');
                } else {
                    // On first icon, go to toggle button
                    $toggleIcon.trigger('focus');
                }
            } else {
                // Tab: go to next icon or to next element outside sidepane
                if (currentIndex < $icons.length - 1) {
                    e.preventDefault();
                    $icons.eq(currentIndex + 1).trigger('focus');
                }
            }
        });

        $(document).on('keydown', '.sidePane .toggleSideBar', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                $(this).trigger('click');
            }
        });

        // Listen for back button event to deactivate elements
        $(document).on('listgroup:back', '.sideContent', function (e, $container) {
            if ($container && $container.length) {
                self.notifyElements($container.get(0), false);
                self.updateActiveIcon($container.closest('.sidePane'), -1);
            }
        });
    }

    _onMapbenderSetupFinished() {
        this._buildElementIcons();
        this.handler.setup();
        this._updateToggleButtonIcons()
    }

    updateResponsive($buttons) {
        const $activeButton = $buttons.filter('.active').first();
        if ($activeButton.length && !$($activeButton).is(':visible')) {
            const $firstVisibleButton = $buttons.filter(':visible').first();
            if ($firstVisibleButton.length) {
                // NOTE: toggles active classes and implicitly ends up calling notifyElements
                // @see initTabContainer
                $firstVisibleButton.trigger('click');
            }
        }
        this._updateResponsiveSidepaneVisibility($buttons);
    }

    notifyElements(scope, state) {
        $('.mb-element[id]', scope).each(function () {
            const promise = state
                // Before we call 'reveal' an element, we want it to be done initializing
                ? Mapbender.elementRegistry.waitReady(this.id)
                // We do not wait for an element to become ready before we call 'hide'
                : Mapbender.elementRegistry.waitCreated(this.id)
            ;
            promise.then(function (elementWidget) {
                const method = state ? elementWidget.reveal : elementWidget.hide;
                if (typeof method === 'function') {
                    method.call(elementWidget);
                }
                $(document).trigger(state ? 'mapbender.elementactivated' : 'mapbender.elementdeactivated', {
                    sender: null,
                    widget: elementWidget,
                    active: state
                });
            });
        });
    }

    updateActiveIcon(activeIndex) {
        this.$elementIcons.removeClass('active');

        if (activeIndex >= 0 && activeIndex < this.$elementIcons.length) {
            this.$elementIcons.eq(activeIndex).addClass('active');
        }
    }

    _buildElementIcons() {
        // Get all visible elements in the sidepane
        const $elementIcons = this.$element.find('.toggleSideBar .element-icons');
        $elementIcons.empty(); // Clear existing icons first

        const $titles = this.handler.getElementTitleContainers();
        $titles.each(function () {
            const $title = $(this);
            const $icon = $title.find('.js-mb-icon').first().clone();

            const buttonId = $title.attr('id');
            const $iconWrapper = $('<span class="element-icon"></span>')
                .data('button-id', buttonId)
                .attr('tabindex', '0')
                .attr('role', 'button')
                .attr('title', $title.text().trim());
            if ($icon.length) $iconWrapper.append($icon);
            $elementIcons.append($iconWrapper);
        });

        this.$elementIcons = $elementIcons.find('.element-icon');
    }

    /**
     * gets the current sidepane width in pixels
     * @return {number}
     */
    sidePaneWidth() {
        return parseInt(getComputedStyle(this.element, '').width);
    }

    _resizeSidepane(e) {
        if (e.buttons === 0) {
            // catch pointer released outside the window
            document.removeEventListener("pointermove", this.boundResizeSidepane, false);
            return;
        }

        // some touch devices do not expose e.x in pointerdown, so use the first pointermove event as reference
        if (this.pointerPosition === undefined) {
            this.pointerPosition = e.x;
            return;
        }

        const dx = this.pointerPosition - e.x;
        this.pointerPosition = e.x;
        let calculatedWidth = this.sidePaneWidth() + (this.isLeft ? -1 : 1) * dx;

        // make sure sidepane does not become unreasonably big or small
        if (calculatedWidth > Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE)) {
            const overflow = calculatedWidth - Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE);
            calculatedWidth -= overflow;
            this.pointerPosition -= overflow;
        }

        if (calculatedWidth < this.MIN_SIZE_PX) {
            const underflow = this.MIN_SIZE_PX - calculatedWidth;
            calculatedWidth += underflow;
            this.pointerPosition += underflow;
        }

        this.element.style.width = calculatedWidth + "px";
        this.$element.trigger('sidepane-resize');
    }

    /**
     * makes sure that after resize the sidepane size is within the allowed bounds
     * (calculated using the property MAX_SIZE_WINDOW_PERCENTAGE)
     */
    constrainSize() {
        if (!this.element) return;
        const allowedWidth = Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE);
        if (this.sidePaneWidth() > allowedWidth) {
            this.element.style.width = allowedWidth + "px";
            const orientationString = this.isLeft ? 'left' : 'right';
            if (!this.isOpen && this.element.style[orientationString] && this.element.style[orientationString] !== "0px") {
                this.element.style[orientationString] = "-" + allowedWidth + "px";
            }
        }
    }

    /**
     * opens the mapbender element by the supplied element id (for modes list, tabs and accordion).
     * Also opens the sidepane if it's closed
     * @param {number | string} id the element id to open
     * @return {Promise<any>} resolves after animations finished
     */
    openElementById(id) {
        const sidePaneType = this.sidePaneType();
        switch (sidePaneType) {
            case 'list':
                const $groupItem = this.$element.find('#' + id).closest('.container-list-group-item');
                const listId = $groupItem.attr('id').replace('list_group_item_container', '');
                $('.sidePane #list_group_item' + listId).trigger('click');
                return Promise.allSettled([
                    this._waitForRunningTransitions($groupItem[0]),
                    this.setOpen(true),
                ]);
            case 'tabs':
                const tabId = this.$element.find('#' + id).closest('.container').attr('id').replace('container', '');
                $('.sidePane #tab' + tabId).trigger('click');
                this.$element.find('#tab' + tabId).trigger('click');
                // no animation, so we're good to just break through to awaiting setOpen
                break;
            case 'accordion':
                this.$element.find('#' + id).closest('.container-accordion').prev().trigger('click');
                // no animation, so we're good to just break through to awaiting setOpen
                break;
            // for unformatted, nothing needs to be highlighted
        }

        return this.setOpen(true);
    }

    async _waitForRunningTransitions(targetElement) {
        // check for API support
        if (typeof targetElement.getAnimations !== 'function') return;

        const elementsToCheck = [];
        let current = targetElement;
        // Parent transitions can move children, so include the ancestor chain.
        while (current && current !== document.body) {
            elementsToCheck.push(current);
            current = current.parentElement;
        }

        const runningAnimations = elementsToCheck.flatMap((element) => {
            return element.getAnimations().filter((animation) => {
                if (animation.playState !== 'running' && animation.playState !== 'pending') {
                    return false;
                }
                // Ignore infinite / non-terminating animations (e.g. spinners) which would block forever.
                const effect = animation.effect;
                if (!effect || typeof effect.getComputedTiming !== 'function') {
                    return false;
                }
                const {endTime} = effect.getComputedTiming();
                return Number.isFinite(endTime);
            });
        });

        if (!runningAnimations.length) {
            return;
        }

        await Promise.race([
            Promise.allSettled(runningAnimations.map((animation) => animation.finished)),
            new Promise((resolve) => setTimeout(resolve, 1000))
        ]);
    }
}

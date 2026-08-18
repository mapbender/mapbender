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

    /**
     * opens the mapbender element by the supplied element id (for modes list, tabs and accordion).
     * Also opens the sidepane if it's closed
     * @param {number | string} id the element id to open
     * @return {Promise<any>} resolves after animations finished
     */
    openElementById(id) {
        return this.sidePane.setOpen(true);
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
        /** @type {HTMLDivElement} **/
        this.sideContent = this.$element.find('.sideContent')[0];
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
            // disables tab interaction when the sidepane is closed
            this.sideContent.inert = !open;

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
            this.sideContent.inert = true;
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

    /**
     *
     * @param {jQuery} $elements
     */
    updateResponsiveSidepaneVisibility($elements) {
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
                // @see SidePaneTabs.setup()
                $firstVisibleButton.trigger('click');
            }
        }
        this.updateResponsiveSidepaneVisibility($buttons);
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
        const $elementIconWrapper = this.$switchButton.find('.element-icons');
        $elementIconWrapper.empty(); // Clear existing icons first

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
            $elementIconWrapper.append($iconWrapper);
        });

        this.$elementIcons = $elementIconWrapper.find('.element-icon');
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
        return this.handler.openElementById(id);
    }
}

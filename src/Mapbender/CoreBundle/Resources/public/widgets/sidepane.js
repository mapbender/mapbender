$(function () {
    /**
     * Class that encapsulates all functionality regarding the fullscreen template's sidepane
     * Emits the 'sidepane-resize' event on the .sidePane element when resized.
     */
    class SidePane {
        constructor(element) {
            /** {jQuery} **/
            this.$element = $(element);
            /** {jQuery} **/
            this.$switchButton = this.$element.find(".toggleSideBar");
            /** {jQuery} **/
            this.$switchIcon = this.$switchButton.children('i').first();
            /** {HTMLDivElement} **/
            this.element = this.$element[0];
            this.pointerPosition = 0;
            this.isLeft = this.$element.hasClass('left');
            // initial state overridden in _setupInitialState
            this.isOpen = true;
            this.isAnimating = false;
            // stores resolve methods of promises that will be resolved when animation finishes
            this.pendingResolves = [];

            this.BORDER_SIZE = 'ontouchstart' in document ? 12 : 6;
            // if you want to customize the max/min size use custom css (min-width/max-width on .sidePane.resizable),
            this.MAX_SIZE_WINDOW_PERCENTAGE = 0.95;
            this.MIN_SIZE_PX = 120;

            this._lastFocusedListItem = null; // Global tracking of last focused list item

            this.boundResizeSidepane = this._resizeSidepane.bind(this);
            this._setupInitialState();
            this._setupEvents();
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
                return new Promise((resolve, reject) => {
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

                var animation = {};
                var align = this.isLeft ? 'left' : 'right';
                if (wasOpen) {
                    animation[align] = "-" + this.$element.outerWidth(true) + "px";
                } else {
                    animation[align] = "0px";
                }

                this.$switchButton.toggleClass('closed', !open);
                this.$switchIcon.toggleClass('fa-bars', !open);
                this.$switchIcon.toggleClass('fa-xmark', open);

                this._updateToggleButtonIcons(this.$switchButton);

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
            const $sidePane = $('.sidePane .sideContent > :first-child');
            if ($sidePane.hasClass('tabContainerAlt')) {
                return 'tabs';
            } else if ($sidePane.hasClass('accordionContainer')) {
                return 'accordion';
            } else if ($sidePane.hasClass('listContainer')) {
                return 'list';
            } else {
                return 'unformatted';
            }
        }

        _updateToggleButtonIcons($btn) {
            var $elementIcons = $btn.find('.element-icons');

            // Show element icons when closed, hide when open
            $elementIcons.toggleClass('hidden', !$btn.hasClass('closed'));
        }

        _setupInitialState() {
            if (this.$element.hasClass('closed')) {
                this.isOpen = false;
                if (this.$element.hasClass("right")) {
                    this.$element.css({right: (this.$element.outerWidth(true) * -1) + "px"});
                } else {
                    this.$element.css({left: (this.$element.outerWidth(true) * -1) + "px"});
                }
            }
        }

        _setupEvents() {
            this.$switchButton.on('click', (e) => {
                e.stopPropagation();
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

            // make sure sidebar is resizable even when making the window smaller
            window.addEventListener("resize", this.constrainSize.bind(this), false);
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
                if (this.element.style.left) {
                    this.element.style.left = "-" + allowedWidth + "px";
                }
            }
        }

        /**
         * opens the mapbender element by the supplied element id (for modes list, tabs and accordion).
         * Also openes the sidepane if it's closed
         * @param {number | string} id the element id to open
         * @return {Promise<void>} resolves after animations finished
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

    window.Mapbender.sidePane = new SidePane($('.sidePane'));

});

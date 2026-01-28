(function() {
    class MbInteractiveHelp extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.currentChapter = 0;
            this.prevChapter = false;
            this.tourLength = 0;
            this.elementId = this.$element.attr('id');
            this.popover = $($('#interactiveHelpPopover-' + this.elementId).html());
            this.localStorageId = 'dismiss-permanently-help-' + Mapbender.configuration.application.slug + '-' + this.elementId;
            this.popoverClass = '.popover-interactive-help-' + this.elementId;

            Mapbender.elementRegistry.waitReady('.mb-element-map').then(() => {
                this._setup();
            }, () => {
                Mapbender.checkTarget('mbInteractiveHelp');
            });
        }

        _setup() {
            this.completeTourChapterConfiguration();
            this.initEventHandlers();
            const dismissPermanently = !!localStorage.getItem(this.localStorageId);
            if (this.options.autoOpen && dismissPermanently !== true) {
                this.activateByButton();
            }
        }

        activateByButton(callback, mbButton) {
            let dissmissCheckbox = this.$element.find('.dismiss-permanently');
            dissmissCheckbox.addClass('d-none');
            if (!callback && !mbButton) {
                dissmissCheckbox.removeClass('d-none');
            }
            super.activateByButton(callback, mbButton);
            this.popup.open();
            this.notifyWidgetActivated();
        }

        closeByButton() {
            super.closeByButton();
            this.notifyWidgetDeactivated();
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                modal: true,
                detachOnClose: false,
                closeOnOutsideClick: true,
                content: this.$element,
                width: this.options.popupWidth || 350,
                height: this.options.popupHeight || null,
                buttons: []
            };
        }

        initEventHandlers() {
            // Mouse / touch
            $(document).on('click', '#' + this.elementId + ' .startTourBtn', () => {
                this.runShow();
            });
            // Mouse / touch
            $(document).on('click', this.popoverClass + ' .runShowBtn', (e) => {
                // Keyboard-generated clicks often have detail === 0; those are handled in keydown below
                if (typeof e.detail === 'number' && e.detail === 0) {
                    return;
                }
                this.runShow();
            });
            // Keyboard (avoid keyup->focus-change->second activation)
            $(document).on('keydown', this.popoverClass + ' .runShowBtn', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                e.stopImmediatePropagation();
                this.runShow();
            });

            $(document).on('click', this.popoverClass + ' .stepBackBtn', (e) => {
                e.preventDefault();
                this.oneStepBack();
            });
            $(document).on('click', this.popoverClass + ' .closeShowBtn', () => {
                this.stopShow();
            });
            $('.dismiss-permanently input').on('change', (e) => {
                if (e.target.checked) {
                    localStorage.setItem(this.localStorageId, true);
                } else {
                    localStorage.removeItem(this.localStorageId);
                }
            });
        }

        completeTourChapterConfiguration() {
            const allElements = Mapbender.configuration.elements;
            let chapters = this.options.tour.chapters;
            // filter out all elements that are not configured in the application or hidden for mobile/desktop:
            chapters = chapters.filter(chapter => {
                const $el = $('.' + chapter.selector);
                if ($el.length > 0) {
                    const hideOnMobile = ($el.hasClass('hide-screentype-mobile') || $el.closest('.element-wrapper').hasClass('hide-screentype-mobile')) && this.isMobile();
                    const hideOnDesktop = ($el.hasClass('hide-screentype-desktop') || $el.closest('.element-wrapper').hasClass('hide-screentype-desktop')) && !this.isMobile();
                    return !hideOnMobile && !hideOnDesktop;
                }
                return false;
            });
            // add id, region, element for each element of the tour:
            chapters.forEach((chapter) => {
                const element = $('.' + chapter.selector).data(chapter.class);
                chapter.element = element;
                chapter.id = element.$element.attr('id');
                if (element.checkDialogMode(element.$element)) {
                    for (let id in allElements) {
                        if (allElements[id].init === 'MbControlButton' && allElements[id].configuration.target == chapter.id) {
                            chapter.id = id;
                            chapter.region = 'toolbar';
                            break;
                        }
                    }
                }
            });
            this.options.tour.chapters = chapters;
            this.tourLength = this.options.tour.chapters.length;
        }

        runShow() {
            if (this.tourLength === this.currentChapter) {
                this.stopShow();
                return;
            }
            if (this.popup && this.popup.$element) {
                this.popup.close();
            }
            if (this.prevChapter !== false && this.prevChapter.region === 'toolbar') {
                this.prevChapter.element.closeByButton();
            }
            const currentChapter = this.options.tour.chapters[this.currentChapter];
            this.prevChapter = currentChapter;
            const $currentElement = $('#' + currentChapter.id);
            let id = '';
            if (currentChapter.region === 'sidepane') {
                const sidePaneType = this.sidePaneType();
                switch (sidePaneType) {
                    case 'tabs':
                        id = $currentElement.closest('.container').attr('id').replace('container', '');
                        $('.sidePane #tab' + id).click();
                        break;
                    case 'list':
                        id = $currentElement.closest('.container-list-group-item').attr('id').replace('list_group_item_container', '');
                        $('.sidePane #list_group_item' + id).click();
                        break;
                    default:
                        $currentElement.closest('.container-accordion').prev().click();
                }
            }
            if (currentChapter.region === 'toolbar') {
                $currentElement.click();
            }
            this.updatePopover(currentChapter);
            this.currentChapter++;
        }

        oneStepBack() {
            const chapters = this.options.tour.chapters;
            if (chapters[this.currentChapter - 1].region === 'toolbar') {
                chapters[this.currentChapter - 1].element.closeByButton();
            }
            this.currentChapter -= 2;
            this.prevChapter = chapters[this.currentChapter];
            this.runShow();
        }

        async updatePopover(currentChapter) {
            if (!document.body.contains(this.popover[0])) {
                $('body').append(this.popover);
            }
            this.popover.removeClass('popover-bottom popover-top popover-left popover-right');
            this.popover.find('.help-popover-title').text(Mapbender.trans(currentChapter.title));
            this.popover.find('.help-popover-text').text(Mapbender.trans(currentChapter.description));
            this.focusPopover();
            // rename next button when last chapter is reached:
            if ((this.tourLength - 1) === this.currentChapter) {
                $('.runShowBtn').text(Mapbender.trans('mb.interactivehelp.element.end'));
            } else {
                $('.runShowBtn').text(Mapbender.trans('mb.interactivehelp.element.next'));
            }
            // don't show step-back link on first chapter:
            if (this.currentChapter === 0) {
                $('.stepBackBtn').addClass('d-none');
            } else {
                $('.stepBackBtn').removeClass('d-none');
            }
            // handle sidepane, toolbar and content configurations:
            if (currentChapter.region === 'toolbar' && this.isDropdownMenu() && this.dropdownMenuIsClosed()) {
                this.openDropdownMenu();
            }
            if (currentChapter.region === 'sidepane' && this.sidePaneIsClosed()) {
                await this.openSidePane();
            }
            const anchor = currentChapter.element.options.anchor;
            currentChapter.region = (currentChapter.region === 'content' && anchor.startsWith('left')) ? currentChapter.region + '-left' : currentChapter.region;
            currentChapter.region = (currentChapter.region === 'sidepane' && this.sidePanePosition() === 'right') ? currentChapter.region + '-right' : currentChapter.region;
            currentChapter.region = (currentChapter.region === 'sidepane' && this.isMobile()) ? currentChapter.region + '-mobile' : currentChapter.region;
            // calculate popover position:
            const rect = document.getElementById(currentChapter.id).getBoundingClientRect();
            let top, left, position;
            switch (currentChapter.region) {
                case 'footer':
                    position = 'top';
                    top = rect.top - this.popover.height() - 30;
                    left = rect.left - this.popover.width() + (rect.width / 2) + 10;
                    break;
                case 'toolbar':
                    position = 'bottom';
                    top = rect.bottom + 8;
                    left = rect.left - this.popover.width() + 20;
                    break;
                case 'content':
                case 'sidepane-right':
                    position = 'left';
                    top = rect.top + (rect.height - this.popover.height() - 20) / 2;
                    left = rect.left - this.popover.width() - 30;
                    break;
                case 'sidepane':
                case 'content-left':
                    position = 'right';
                    top = rect.top + (rect.height - this.popover.height()) / 2;
                    left = rect.right + 20;
                    break;
                case 'sidepane-mobile':
                    position = 'bottom';
                    top = rect.bottom + 8;
                    left = rect.left + 20;
                    break;
            }
            this.popover.addClass('popover-' + position);
            this.popover.css({
                'top': top + 'px',
                'left': left + 'px'
            });
        }

        stopShow() {
            const chapters = this.options.tour.chapters;
            if (chapters[this.currentChapter - 1].region === 'toolbar') {
                chapters[this.currentChapter - 1].element.closeByButton();
            }
            this.currentChapter = 0;
            this.popover.detach();
        }

        sidePanePosition() {
            return ($('.sidePane').hasClass('right')) ? 'right' : 'left';
        }

        sidePaneIsClosed() {
            return $('.sidePane').hasClass('closed');
        }

        sidePaneType() {
            const $sidePane = $('.sidePane .sideContent > :first-child');
            if ($sidePane.hasClass('tabContainerAlt')) {
                return 'tabs';
            } else if ($sidePane.hasClass('accordionContainer')) {
                return 'accordion';
            } else if ($sidePane.hasClass('listContainer')) {
                return 'list';
            } else { // unformatted sidepane
                return 'list';
            }
        }

        openSidePane() {
            return new Promise((resolve) => {
                const sidePane = $('.sidePane')[0];
                if (!this.sidePaneIsClosed()) {
                    resolve();
                    return;
                }
                const onTransitionEnd = () => {
                    sidePane.removeEventListener('transitionend', onTransitionEnd);
                    resolve();
                };
                sidePane.addEventListener('transitionend', onTransitionEnd);
                $('.toggleSideBar').click();
            });
        }

        isDropdownMenu() {
            return $('.toolBar > :first-child').hasClass('dropdown');
        }

        dropdownMenuIsClosed() {
            return !$('.toolBar > .dropdown').hasClass('open');
        }

        openDropdownMenu() {
            $('.toolBar > .dropdown').addClass('open');
        }

        isMobile() {
            return window.innerWidth < 1200;
        }

        focusPopover() {
            const $p = this.popover;
            $p.attr({ tabindex: '-1', role: 'dialog' });
            const getFocusables = () => $p
                .find('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')
                .filter(':visible');
            // If focus is already inside, don't move it
            if ($p[0].contains(document.activeElement)) {
                return;
            }
            // Defer focus so Enter-activation on .runShowBtn won't "transfer" to the close button
            window.requestAnimationFrame(() => {
                const $preferred = $p.find('.runShowBtn:visible').first();
                const $first = getFocusables().first();
                ($preferred.length ? $preferred : ($first.length ? $first : $p)).trigger('focus');
            });
            // Trap Tab navigation inside the popover (bind once)
            if (!$p.data('mbFocusTrapBound')) {
                $p.data('mbFocusTrapBound', true);
                $p.on('keydown.mbInteractiveHelp', (e) => {
                    if (e.key !== 'Tab') return;
                    const $f = getFocusables();
                    if (!$f.length) {
                        e.preventDefault();
                        $p.trigger('focus');
                        return;
                    }
                    const first = $f[0];
                    const last = $f[$f.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                });
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbInteractiveHelp = MbInteractiveHelp;
})();

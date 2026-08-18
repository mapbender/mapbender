window.Mapbender.SidePaneList = class SidepaneList extends Mapbender.SidePaneHandler {

    getType() {
        return 'list';
    }


    getElementTitleContainers() {
        return this.$container.find('.list-group-item:not(.inline)');
    }

    openElementById(id) {
        const $groupItem = this.sidePane.$element.find('#' + id).closest('.container-list-group-item');
        const listId = $groupItem.attr('id').replace('list_group_item_container', '');
        $('.sidePane #list_group_item' + listId).trigger('click');
        return Promise.allSettled([
            this._waitForRunningTransitions($groupItem[0]),
            super.openElementById(id),
        ]);
    }

    setup() {
        this.$headers = this.$container.find('.list-group-item');
        this.$headers.attr('tabindex', '0');
        this.$activeHeader = null;
        this.$activePanel = null;
        this.$panels = this.$container.parent().find('.container-list-group-item');
        this.$backButtons = this.$panels.find('.list-back-btn');

        const sidePane = this.sidePane;
        const self = this;

        this.$headers.on('click', function () {
            self.onHeaderClick($(this));
        });

        this.$backButtons.on('click', (e) => {
            e.preventDefault();
            this.onBackButtonClick();
        });

        window.addEventListener('resize', function () {
            // Switch active panel if screen size change caused current active panel to visually disappear
            sidePane.updateResponsive(self.$headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
        sidePane.updateResponsive(self.$headers);
    }

    onHeaderClick($header) {
        if ($header.hasClass('active')) {
            return;
        }

        this._disableActiveElement();

        this.$activeHeader = $header;
        this.$activeHeader.addClass('active');

        const containerId = $header.attr("id").replace("list_group_item", "list_group_item_container");
        this.$activePanel = $("#" + containerId, this.sidePane.sideContent);

        this.$activePanel.addClass('active');
        this.$container.addClass('list-shifted');
        this.sidePane.notifyElements(this.$activePanel, true);

        // Get the index of the active list item (considering only non-inline items)
        const $nonInlineHeaders = this.$headers.not('.inline');
        const activeIndex = $nonInlineHeaders.index(this.$activeHeader);
        this.sidePane.updateActiveIcon(activeIndex);
    }

    onBackButtonClick() {
        this._disableActiveElement();
        this.sidePane.updateActiveIcon(-1);

        // Slide back to original position
        this.$container.removeClass('list-shifted');
    }


    _disableActiveElement() {
        if (this.$activePanel) {
            this.sidePane.notifyElements(this.$activePanel, false);
            this.$activePanel.removeClass('active');
            this.$activePanel = null;
        }
        if (this.$activeHeader) {
            this.$activeHeader?.removeClass('active');
            this.$activeHeader = null;
        }
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

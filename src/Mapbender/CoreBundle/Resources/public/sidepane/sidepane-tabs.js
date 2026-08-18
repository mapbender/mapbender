window.Mapbender.SidePaneTabs = class SidepaneTabs extends Mapbender.SidePaneHandler {

    getType() {
        return 'tabs';
    }

    setup() {
        const sidePane = this.sidePane;
        const $panels = $('>.container[id]', this.$container);
        const $buttons = $('>.tabs >.tab[id]', this.$container);
        $buttons.attr('tabindex', 0);

        let currentPanel = null;

        function setCurrentTab() {
            const panelId = this.id.replace('tab', 'container');
            const panel = $panels.filter('#' + panelId).first().get(0);
            if (panel) {
                if (currentPanel) {
                    sidePane.notifyElements(currentPanel, false);
                }

                sidePane.notifyElements(panel, true);
                currentPanel = panel;

                // Calculate index considering only non-inline buttons
                const $nonInlineButtons = $buttons.not('.inline');
                const activeIndex = $nonInlineButtons.index(this);
                sidePane.updateActiveIcon(activeIndex);
            }
        }

        // set initial active tab from .active class
        $buttons.filter('.active').first().each(setCurrentTab);
        $buttons.on('click', setCurrentTab);

        window.addEventListener('resize', function() {
            // Switch active "tab" if screen size change caused current active "tab" to visually disappear
            this.sidePane.updateResponsive($buttons);
        });
        // Also select a different active "tab" if default active "tab" is already invisible on initialization
        sidePane.updateResponsive($buttons);
    }

    getElementTitleContainers() {
        return this.$container.find('.tab:not(.inline)');
    }

    openElementById(id) {
        const tabId = this.sidePane.$element.find('#' + id).closest('.container').attr('id').replace('container', '');
        $('.sidePane #tab' + tabId).trigger('click');
        this.sidePane.$element.find('#tab' + tabId).trigger('click');
        return super.openElementById(id);
    }

}

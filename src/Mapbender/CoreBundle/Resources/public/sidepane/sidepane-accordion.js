window.Mapbender.SidePaneAccordion = class SidepaneAccordion extends Mapbender.SidePaneHandler {

    getType() {
        return 'accordion';
    }

    panelFromHeader($panels, header) {
            const panelId = header && header.id
                && header.id.replace("accordion", "container");
            return panelId && $panels.filter('#' + panelId).first().get(0);
        }

    setup() {
        const sidePane = this.sidePane;
        const $headers = this.$container.find('>.accordion');
        const $panels = this.$container.find('>.container-accordion');
        $headers.attr('tabindex', '0');

        // set initial active panel from .active class
        const initialHeader = $('>.accordion.active', this.$container).get(0);
        if (initialHeader) {
            const initialPanel = this.panelFromHeader($panels, initialHeader);
            if (initialPanel) {
                sidePane.notifyElements(initialPanel, true);
            }
        }

        $headers.on('selected', (e, tabData) => {
            const activatedHeader = tabData.current && tabData.current.get(0);
            const deactivatedHeader = tabData.previous && tabData.previous.get(0);
            const activatedPanel = this.panelFromHeader($panels, activatedHeader);
            const deactivatedPanel = this.panelFromHeader($panels, deactivatedHeader);
            if (deactivatedPanel) {
                sidePane.notifyElements(deactivatedPanel, false);
            }
            if (activatedPanel) {
                sidePane.notifyElements(activatedPanel, true);
                // Calculate index considering only non-inline headers
                const $nonInlineHeaders = $headers.not('.inline');
                const activeIndex = $nonInlineHeaders.index(activatedHeader);
                sidePane.updateActiveIcon(activeIndex);
            }
        });

        window.addEventListener('resize', function() {
            // Switch active panel if screen size change caused current active panel to visually disappear
            sidePane.updateResponsive($headers);
        });

        sidePane.updateResponsive($headers);
    }

    getElementTitleContainers() {
        return this.$container.find('.accordion:not(.inline)');
    }

    openElementById(id) {
        this.sidePane.$element.find('#' + id).closest('.container-accordion').prev().trigger('click');
        return super.openElementById(id);
    }

}

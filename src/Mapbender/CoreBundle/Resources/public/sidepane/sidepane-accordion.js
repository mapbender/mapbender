window.Mapbender.SidePaneAccordion = class SidepaneAccordion extends Mapbender.SidePaneHandler {

    getType() {
        return 'accordion';
    }

    setup() {
        const sidePane = this.sidePane;
        var $headers = $('>.accordion', this.$container);
        var $panels = $('>.container-accordion', this.$container);
        var $sidePane = $headers.first().closest('.sidePane');
        function panelFromHeader($panels, header) {
            var panelId = header && header.id
                && header.id.replace("accordion", "container");
            return panelId && $panels.filter('#' + panelId).first().get(0);
        }

        // set initial active panel from .active class
        var initialHeader = $('>.accordion.active', this.$container).get(0);
        if (initialHeader) {
            var initialPanel = panelFromHeader($panels, initialHeader);
            if (initialPanel) {
                sidePane.notifyElements(initialPanel, true);
            }
        }
        $headers.attr('tabindex', '0');
        $headers.on('keydown', function(event) {
            if (event.key === 'Enter') {
                $(this).trigger('click');
            }
        });
        $headers.on('selected', function(e, tabData) {
            var activatedHeader = tabData.current && tabData.current.get(0);
            var deactivatedHeader = tabData.previous && tabData.previous.get(0);
            var activatedPanel = panelFromHeader($panels, activatedHeader);
            var deactivatedPanel = panelFromHeader($panels, deactivatedHeader);
            if (deactivatedPanel) {
                sidePane.notifyElements(deactivatedPanel, false);
            }
            if (activatedPanel) {
                sidePane.notifyElements(activatedPanel, true);
                // Calculate index considering only non-inline headers
                var $nonInlineHeaders = $headers.not('.inline');
                var activeIndex = $nonInlineHeaders.index(activatedHeader);
                sidePane.updateActiveIcon(activeIndex);
            }
        });
        window.addEventListener('resize', function() {
            // Switch active panel if screen size change caused current active panel to visually disappear
            sidePane.updateResponsive($headers);
        });
        // Also select a different active panel if default active panel is already invisible on initialization
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

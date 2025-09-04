(function() {
    class MbResetView extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            });
        }

        _setup(mbMap) {
            this.mbMap = mbMap;
            this.initial = mbMap.getModel().getConfiguredSettings();
            this.$element.on('click', () => {
                this.run();
            });
        }

        run() {
            const viewManager = $('.mb-element-viewmanager');
            if (viewManager.length > 0) {
                let settings = window.localStorage.getItem('viewManagerSettings');
                settings = JSON.parse(settings);
                settings = {
                    layersets: JSON.parse(settings.layersetStates),
                    sources: JSON.parse(settings.sourcesStates),
                    viewParams: this.mbMap.getModel().decodeViewParams(settings.viewParams)
                };
                viewManager.data('mapbenderMbViewManager')._apply(settings);
            }
            if (this.options.resetDynamicSources) {
                this.resetDynamicSources();
            }
            if (viewManager.length === 0) {
                this.mbMap.getModel().applySettings(this.initial);
            }
        }

        resetDynamicSources() {
            var model = this.mbMap.getModel();
            model.sourceTree.forEach(function(source) {
                if (source.isDynamicSource) {
                    model.removeSource(source);
                }
            });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbResetView = MbResetView;
})();

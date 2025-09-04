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
            if (this.options.resetDynamicSources) {
                this.resetDynamicSources();
            }
            this.mbMap.getModel().applySettings(this.initial);
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

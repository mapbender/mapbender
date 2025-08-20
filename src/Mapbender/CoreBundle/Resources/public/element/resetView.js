(function ($) {
    'use strict';
    $.widget('mapbender.resetView', $.mapbender.mbBaseButton, {
        options: {
            resetDynamicSources: true
        },
        mbMap: null,
        initial: null,

        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.setup(mbMap);
            });
        },
        setup: function(mbMap) {
            var self = this;
            this.mbMap = mbMap;
            this.initial = mbMap.getModel().getConfiguredSettings();
            this.element.on('click', function() {
                self.run();
            });
        },

        run: function() {
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
        },
        resetDynamicSources: function() {
            var model = this.mbMap.getModel();
            model.sourceTree.forEach(function(source) {
                if (source.isDynamicSource) {
                    model.removeSource(source);
                }
            });
        }
    });
}(jQuery));

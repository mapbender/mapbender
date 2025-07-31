(function ($) {
    'use strict';
    $.widget('mapbender.resetView', $.mapbender.mbBaseButton, {
        options: {
            resetDynamicSources: true
        },
        mbMap: null,

        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.setup(mbMap);
            });
        },
        setup: function(mbMap) {
            var self = this;
            this.mbMap = mbMap;
            this.element.on('click', function() {
                self.run();
            });
        },

        run: function() {
            let settings = window.localStorage.getItem('viewManagerSettings');
            settings = JSON.parse(settings);
            settings = {
                layersets: JSON.parse(settings.layersetStates),
                sources: JSON.parse(settings.sourcesStates),
                viewParams: this.mbMap.getModel().decodeViewParams(settings.viewParams)
            };
            $('.mb-element-viewmanager').data('mapbenderMbViewManager')._apply(settings);
            if (this.options.resetDynamicSources) {
                this.resetDynamicSources();
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

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
            if (this.options.resetDynamicSources) {
                this.resetDynamicSources();
            }
            this.mbMap.getModel().applySettings(this.initial);
        },
        resetDynamicSources: function() {
            var model = this.mbMap.getModel();
            model.sourceTree.forEach(function(source) {
                if (source.wmsloader) {
                    model.removeSource(source);
                }
            });
        }
    });
}(jQuery));

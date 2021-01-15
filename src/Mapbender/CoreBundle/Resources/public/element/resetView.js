(function ($) {
    'use strict';
    $.widget('mapbender.resetView', $.mapbender.mbBaseButton, {
        options: {
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
            this.initial = mbMap.getModel().getInitialSettings();
            this.element.on('click', function() {
                self.run();
            });
        },

        run: function() {
            this.mbMap.getModel().applySettings(this.initial);
        }
    });
}(jQuery));

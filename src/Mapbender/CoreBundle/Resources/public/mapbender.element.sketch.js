(function($){
    'use strict';
    $.widget('mapbender.mbSketch', {
        options: {
            target: null,
            autoOpen: false
        },

        activated: false,

        _create: function() {
            if(!Mapbender.checkTarget('mbSketch', this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(
                this.options.target,
                $.proxy(this._setup, this)
            );
        },

        _setup: function() {
            this.model = Mapbender.elementRegistry.listWidgets().mapbenderMbMap.model;
            this._trigger('ready');
        },

        /**
         * Default action for a mapbender element
         */
        defaultAction: function() {
            this.activated = !this.activated;

            var styleOptions = {
                fill : {
                    color : '#ffcc33'
                },
                stroke: {
                    color: 'orange',
                    width: 2
                }
            };

            if (this.activated) {
                this.model.createDrawControl('Circle', this.id, this.model.createStyle(styleOptions));
            } else {
                this.model.removeInteractions();
            }
        },

        deactivate: function() {
            if (this.activated) {
                this.model.removeInteractions();
                this.activated = false;
            }
        }
    });
})(jQuery);

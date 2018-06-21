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
            this._ready();
        },

        /**
         * Default action for a mapbender element
         */
        defaultAction: function() {
            this.activated = !this.activated;

            var style = this.model.createStyle();

            if (this.activated) {
                this.model.createDrawControl('Circle', this.id, style, {});
            } else {
                this.model.removeInteractions();
            }
        },

        deactivate: function() {
            if (this.activated) {
                this.model.removeInteractions();
                this.activated = false;
            }
        },

        ready: function(callback) {
            if(this.readyState === true){
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },

        _ready: function() {
            this.readyState = true;
        }
    });
})(jQuery);

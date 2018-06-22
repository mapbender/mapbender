(function($) {

    $.widget('mapbender.mbScalebar', {
        options: {
        },
        scalebar: null,

        /**
         * Creates the scale bar
         */
        _create: function() {
            if(!Mapbender.checkTarget('mbScalebar', this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the scale bar
         */
        _setup: function() {
            var map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            var model = map.model;
            var projection = model.getCurrentProjectionObject();

            $(this.element).addClass(this.options.anchor);
            var scalebarOptions = {
                div: $(this.element).get(0),
                maxWidth: this.options.maxWidth,
                geodesic: projection.units = 'degrees' ? true : false,
                topOutUnits: "km",
                topInUnits: "m",
                bottomOutUnits: "mi",
                bottomInUnits: "ft"
            };

            var scaleLineOptions = {
                'className': 'ol-scale-line',
                'minWidth': '64',
                'units': 'metric',
                'target': document.getElementById($(this.element).get(0).id)
            };
            this.scalebar = new ol.control.ScaleLine(scaleLineOptions);
            model.map.addControl(this.scalebar);

            if($.inArray("km", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineTop').css({display: 'none'});
            }

            if($.inArray("ml", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineBottom').css({display: 'none'});
            }

            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));

            this._trigger('ready');
            this._ready();
        },

        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs){
            this.scalebar.geodesic = srs.projection.units = 'degrees' ? true : false;
            this.scalebar.update();
        },

        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },

        /**
         *
         */
        _ready: function() {
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }

    });

})(jQuery);

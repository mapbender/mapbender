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
            this.mapModel_ = map.model;

            $(this.element).addClass(this.options.anchor);

            var scaleLineOptions = {
                'className': 'ol-scale-line',
                'minWidth': '64',
                'units': 'metric',
                'target': document.getElementById($(this.element).get(0).id)
            };

            this.scalebar = new ol.control.ScaleLine(scaleLineOptions);
            this.mapModel_.map.addControl(this.scalebar);

            if($.inArray("km", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineTop').css({display: 'none'});
            }

            if($.inArray("ml", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineBottom').css({display: 'none'});
            }

            this.scalebar.setMap(this.mapModel_.map);

            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));

            this._trigger('ready');
            this._ready();
        },

        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event) {
            console.log("SRs change event", event, this.scalebar, this.scalebar.viewState_);
            return;
            if (!(this.scalebar && this.scalebar.viewState_)) {
                return;
            }
            console.log("Patching scalebar viewState_.projection!");
            this.scalebar.viewState_.projection = event.projection;
            this.scalebar.setUnits('nautical');
            this.scalebar.setUnits('metric');
//            this.scalebar.render();

                // render with an empty framestate to clear viewState property
                // see https://github.com/openlayers/openlayers/blob/v4.6.5/src/ol/control/scaleline.js#L125
//                this.scalebar.render({frameState: null});
                // this.scalebar.updateElement_();
//                this.scalebar.setMap(null);
//                this.scalebar.setMap(this.mapModel_.map);
//                this.mapModel_.map.changed();
//            } else {
//            }
            // OL2 control:
            // this.scalebar.geodesic = (units === 'degrees');
            // this.scalebar.update();
            // OL2 control end
        },

        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            }
        },

        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        }

    });

})(jQuery);

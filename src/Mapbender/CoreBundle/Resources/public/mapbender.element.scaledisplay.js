(function($) {

    $.widget("mapbender.mbScaledisplay", {
        options: {
//            unitPrefix: false
        },
        scaledisplay: null,

        /**
         * Creates the scale display
         */
        _create: function() {
            if(!Mapbender.checkTarget("mbScaledisplay", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        
        /**
         * Initializes the scale display
         */
        _setup: function() {
            if(typeof this.options.unitPrefix === 'undefined')
                this.options.unitPrefix = false;
            var mbMap = $('#' + this.options.target).data('mbMap');
            
            var projection = mbMap.map.olMap.getProjectionObject();
            var options = {
                geodesic: projection.units === 'degrees' ? true : false
            };
            if(!this.options.unitPrefix){
                options["updateScale"] =  $.proxy(this._updateScale, this);
            }
            this.scaledisplay = new OpenLayers.Control.Scale($(this.element).find("span").get(0), options);
            
            mbMap.map.olMap.addControl(this.scaledisplay);
            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
        },
                
        _updateScale: function(){
            var scale;
            if(this.scaledisplay.geodesic === true) {
                var units = this.scaledisplay.map.getUnits();
                if(!units) {
                    return;
                }
                var inches = OpenLayers.INCHES_PER_UNIT;
                scale = (this.scaledisplay.map.getGeodesicPixelSize().w || 0.000001) *
                        inches["km"] * OpenLayers.DOTS_PER_INCH;
            } else {
                scale = this.scaledisplay.map.getScale();
            }
            if (!scale) {
                return;
            }
            this.scaledisplay.element.innerHTML = OpenLayers.i18n("1 : ${scaleDenom}", {'scaleDenom':scale = Math.round(scale)});
        },
        
        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs){
            this.scaledisplay.geodesic = srs.projection.units = 'degrees' ? true : false;
            this.scaledisplay.updateScale();
        }
        
    });

})(jQuery);
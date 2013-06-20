(function($) {

    $.widget("mapbender.mbScaledisplay", {
        options: {
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
            var mbMap = $('#' + this.options.target).data('mbMap');
            
            var projection = mbMap.map.olMap.getProjectionObject();
            var options = {
                geodesic: projection.units === 'degrees' ? true : false
            };
            this.scaledisplay = new OpenLayers.Control.Scale($(this.element).get(0), options);
            
            mbMap.map.olMap.addControl(this.scaledisplay);
            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
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
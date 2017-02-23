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
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            
            var projection = mbMap.map.olMap.getProjectionObject();
            var options = {
                geodesic: projection.units === 'degrees' ? true : false
            };
            options["updateScale"] =  $.proxy(this._updateScale, this);
            this.scaledisplay = new OpenLayers.Control.Scale($(this.element).find("span").get(0), options);
            
            mbMap.map.olMap.addControl(this.scaledisplay);
            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
            this._trigger('ready');
            this._ready();
        },
        _updateScale: function(){
            var scale = this.scaledisplay.map.getScale();
            if (!scale) {
                return;
            }
            if(this.options.unitPrefix){
                if (scale >= 9500 && scale <= 950000) {
                    scale = Math.round(scale / 1000) + "K";
                } else if (scale >= 950000) {
                    scale = Math.round(scale / 1000000) + "M";
                } else {
                    scale = Math.round(scale);
                }    
            } else{
                scale = Math.round(scale);
            }
            this.scaledisplay.element.innerHTML = OpenLayers.i18n(" 1 : ${scaleDenom}", {'scaleDenom':scale});
        },
        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs){
            this.scaledisplay.updateScale();
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
        },
        
    });

})(jQuery);
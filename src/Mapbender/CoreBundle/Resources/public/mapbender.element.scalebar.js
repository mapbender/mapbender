(function($) {

    $.widget("mapbender.mbScalebar", {
        options: {
        },
        scalebar: null,

        /**
         * Creates the scale bar
         */
        _create: function() {
            if(!Mapbender.checkTarget("mbScalebar", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the scale bar
         */
        _setup: function() {
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');

            $(this.element).addClass(this.options.anchor);

            var scaleLineOptions = {
                'className': 'ol-scale-line',
                'minWidth': '64',
                geodesic: true,
                'units': 'metric'
            };

            this.scalebar = new ol.control.ScaleLine(scaleLineOptions);
            mbMap.getModel().addControl(this.scalebar);

            if($.inArray("km", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineTop').css({display: 'none'});
            }
            if($.inArray("ml", this.options.units) === -1){
                $(this.element).find('div.olControlScaleLineBottom').css({display: 'none'});
            }
            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
            this._trigger('ready');
        },
        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs) {
            this.scalebar.update();
        }
    });

})(jQuery);
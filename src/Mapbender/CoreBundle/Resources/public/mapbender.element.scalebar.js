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
            var scalebarOptions = {
                div: $(this.element).get(0),
                maxWidth: this.options.maxWidth,
                geodesic: true,
                topOutUnits: "km",
                topInUnits: "m",
                bottomOutUnits: "mi",
                bottomInUnits: "ft"
            };
            this.scalebar = new OpenLayers.Control.ScaleLine(scalebarOptions);

            mbMap.map.olMap.addControl(this.scalebar);
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
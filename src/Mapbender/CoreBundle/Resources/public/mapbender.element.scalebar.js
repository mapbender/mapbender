(function($) {

    $.widget("mapbender.mbScalebar", {
        options: {
        },
        scalebar: null,

        /**
         * Creates the scale bar
         */
        _create: function() {
            if(this.options.target === null
                || this.options.target.replace(/^\s+|\s+$/g, '') === ""
                || !$('#' + this.options.target)){
                alert('The target element "map" is not defined for a scale bar.');
                return;
            }
            var self = this;
            $(document).one('mapbender.setupfinished', function() {
                $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
            });
        },
        
        /**
         * Initializes the scale bar
         */
        _setup: function() {
            var mbMap = $('#' + this.options.target).data('mbMap');
            if(this.options.anchor === "left-top"){
                $(this.element).css({
                    left: this.options.position[0],
                    top: this.options.position[1]
                });
            } else if(this.options.anchor === "right-top"){
                $(this.element).css({
                    right: this.options.position[0],
                    top: this.options.position[1]
                });
            } else if(this.options.anchor === "left-bottom"){
                $(this.element).css({
                    left: this.options.position[0],
                    bottom: this.options.position[1]
                });
            } else if(this.options.anchor === "right-bottom"){
                $(this.element).css({
                    right: this.options.position[0],
                    bottom: this.options.position[1]
                });
            }
            
            var projection = mbMap.map.olMap.getProjectionObject();
            var scalebarOptions = {
                div: $(this.element).get(0),
                maxWidth: this.options.maxWidth,
                geodesic: projection.units = 'degrees' ? true : false,
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
        },
        
        
        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs){
            this.scalebar.geodesic = srs.projection.units = 'degrees' ? true : false;
            this.scalebar.update();
        }
        
    });

})(jQuery);
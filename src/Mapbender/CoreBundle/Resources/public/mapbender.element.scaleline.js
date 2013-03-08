(function($) {

    $.widget("mapbender.mbScaleline", {
        options: {
        },

        /**
         * Creates the scale line
         */
        _create: function() {
            if(this.options.target === null
                || this.options.target.replace(/^\s+|\s+$/g, '') === ""
                || !$('#' + this.options.target)){
                alert('The target element "map" is not defined for a scale line.');
                return;
            }
            var self = this;
            $(document).one('mapbender.setupfinished', function() {
                $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
            });
        },
        
        /**
         * Initializes the scale line
         */
        _setup: function() {
            var self = this;
            var mbMap = $('#' + this.options.target).data('mbMap');
//            $(this.element).addClass(this.options.anchor);
            if(this.options.anchor === "left-top"){
                $(this.element).css({
                    left: this.options.position[0] + "px",
                    top: this.options.position[1] + "px"
                });
            } else if(this.options.anchor === "right-top"){
                $(this.element).css({
                    right: this.options.position[0] + "px",
                    top: this.options.position[1] + "px"
                });
            } else if(this.options.anchor === "left-bottom"){
                $(this.element).css({
                    left: this.options.position[0] + "px",
                    bottom: this.options.position[1] + "px"
                });
            } else if(this.options.anchor === "right-bottom"){
                $(this.element).css({
                    right: this.options.position[0] + "px",
                    bottom: this.options.position[1] + "px"
                });
            }
//            $(this.element).css({ width: this.options.maxWidth });
            var projection = mbMap.map.olMap.getProjectionObject();
            var scalelineOptions = {
                div: $(this.element).get(0),
                maxWidth: this.options.maxWidth,
                geodesic: projection.projCode === "EPSG:900913" ? true : false,
                topOutUnits: "km",
                topInUnits: "m",
                bottomOutUnits: "mi",
                bottomInUnits: "ft"
//                ,
//                div: $(this.element).get(0)
            };
            this.scaleline = new OpenLayers.Control.ScaleLine(scalelineOptions);

            mbMap.map.olMap.addControl(this.scaleline);
            $(document).bind('mbsrsselectorsrschanged', $.proxy(this._changeSrs, this));
        },
        
        
        /**
         * Cahnges the scale line srs
         */
        _changeSrs: function(event, srs){
            if(srs.projection.projCode === "EPSG:900913"){
                this.scaleline.geodesic = true;
            } else {
                this.scaleline.geodesic = false;
            }
            this.scaleline.setMap($('#' + this.options.target).data('mbMap').map.olMap);
            this.scaleline.update();
        }
        
    });

})(jQuery);
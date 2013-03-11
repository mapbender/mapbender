(function($) {

$.widget("mapbender.mbCoordinatesDisplay", {
    options: {
        target: null,
        empty: 'x= -<br>y= -',
        prefix: 'x= ',
        separator: '<br/>y= ',
        suffix: ''
    },

    _create: function() {
        if(this.options.target === null
            || this.options.target.replace(/^\s+|\s+$/g, '') === ""
            || !$('#' + this.options.target)){
            alert('The target element "map" is not defined for a coordinatesDisplay.');
            return;
        }
        var self = this;
        $(document).one('mapbender.setupfinished', function() {
            $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
        });
    },

    _setup: function(){
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        var layers = mbMap.map.layers();
        for(var i = 0; i < layers.length; ++i) {
            var layer = layers[i];
            if(layer.options.isBaseLayer){
                layer.olLayer.events.register('loadend', layer.olLayer, function(e){
                     self.reset();
                });
            }
        }
        $(document).bind('mbmapsrschanged', $.proxy(self._reset, self));
        this._reset();
    },

    _reset: function(event, srs){
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        if(!srs){
            srs = { projection: mbMap.map.olMap.getProjectionObject()};
        }
        if(this.crs != null && this.crs == srs.projection.projCode)
            return;
        if(typeof(self.options.formatoutput) !== 'undefined'){
            var isdeg = mbMap.map.olMap.units === 'degrees';
            mbMap.map.olMap.addControl(new OpenLayers.Control.MousePosition({
                id: $(self.element).attr('id'),
                element: $(self.element)[0],
                emptyString: self.options.empty,
                formatOutput: function(pos) {
                    var out = self.options.displaystring.replace("$lon$",pos.lon.toFixed(isdeg ? 5 : 0));
                    return out.replace("$lat$", pos.lat.toFixed(isdeg ? 5 : 0));
                }
            }));
            this.crs = srs.projection.projCode;
        } else {
            var mouseContr = mbMap.map.olMap.getControl($(self.element).attr('id'));
            if(mouseContr != null)
                mbMap.map.olMap.removeControl(mouseContr);
            var options = {
                id: $(self.element).attr('id'),
                div: $($(self.element)[0]).find('div#coordinatesdisplay')[0],
                emptyString: self.options.empty,
                prefix: self.options.prefix,
                separator: self.options.separator,
                suffix: self.options.suffix,
                displayProjection: srs.projection };
            mbMap.map.olMap.addControl(new OpenLayers.Control.MousePosition(options));
            this.crs = srs.projection.projCode;
        }
    },

    showHidde: function() {
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        var list = mbMap.map.olMap.getControlsByClass('OpenLayers.Control.MousePosition');
        $.each(list, function(idx, val) {
            var div_id = '#'+$(self.element).attr('id')+'-div';
            if(val.active) {
               val.deactivate();
               $(div_id).css('display', 'none');
            } else {
               $(div_id).css('display', 'inline');
               val.activate();
            }
        });
    },

    _destroy: $.noop
});

})(jQuery);


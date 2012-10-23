(function($) {

$.widget("mapbender.mbCoordinatesDisplay", {
    options: {
        empty: 'x= -<br>y= -',
        prefix: 'x= ',
        separator: '<br/>y= ',
        suffix: ''
    },

    elementUrl: null,

    crs: null,

    _create: function() {
        var self = this;
        var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
        $(document).one('mapbender.setupfinished', $.proxy(this._mapbenderSetupFinished, this));
    },

    _mapbenderSetupFinished: function() {
      this._init();
      this._reset();
    },

    _init: function(){
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        var layers = mbMap.map.layers();
        for(var i = 0; i < layers.length; ++i) {
            var layer = layers[i];
            if(layer.options.isBaseLayer){
                layer.olLayer.events.register('loadend', layer.olLayer, function(e){
                     self._reset();
                });
            }
        }
    },

    _reset: function(){
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        var list = mbMap.map.olMap.getControlsByClass('OpenLayers.Control.MousePosition');

        if(this.crs != null && this.crs == mbMap.map.olMap.getProjectionObject().projCode)
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
            this.crs = mbMap.map.olMap.getProjectionObject().projCode;
        } else {
            var mouseContr = mbMap.map.olMap.getControl($(self.element).attr('id'));
            if(mouseContr != null)
                mbMap.map.olMap.removeControl(mouseContr);
            var options = {
                id: $(self.element).attr('id'),
                element: $(self.element)[0],
                emptyString: self.options.empty,
                prefix: self.options.prefix,
                separator: self.options.separator,
                suffix: self.options.suffix,
                displayProjection: mbMap.map.olMap.getProjectionObject()};
            mbMap.map.olMap.addControl(new OpenLayers.Control.MousePosition(options));
            this.crs = mbMap.map.olMap.getProjectionObject().projCode;
        }
    },

    reset: function(){
        this._reset();
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


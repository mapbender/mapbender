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
        if(!Mapbender.checkTarget("mbCoordinatesDisplay", this.options.target)){
            return;
        }
        var self = this;
        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
    },

    _setup: function(){
        var self = this;
        self.options.empty = self.options.empty ? self.options.empty : '';
        self.options.prefix = self.options.prefix ? self.options.prefix : '';
        self.options.separator = self.options.separator ? self.options.separator: ' ';
        var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
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
        self.options.numDigits = isNaN(parseInt(self.options.numDigits)) ? 0 : parseInt(self.options.numDigits);
        self.options.numDigits = self.options.numDigits < 0 ? 0 : self.options.numDigits;
        this._reset();
        this._trigger('ready');
        this._ready();
    },

    _reset: function(event, srs){
        var self = this;
        var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
        srs = { projection: mbMap.map.olMap.getProjectionObject()};
        if(this.crs != null && this.crs == srs.projection.projCode){
            return;
        }
        var isdeg = mbMap.map.olMap.units === 'degrees';
        if(typeof(self.options.formatoutput) !== 'undefined'){
            mbMap.map.olMap.addControl(new OpenLayers.Control.MousePosition({
                id: $(self.element).attr('id'),
                element: $(self.element)[0],
                emptyString: self.options.empty,
                numDigits: isdeg ? 5 + self.options.numDigits : self.options.numDigits,
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
                div: $($(self.element)[0]).find('#coordinatesdisplay')[0],
                emptyString: self.options.empty ? self.options.empty : '',
                prefix: self.options.prefix ? self.options.prefix : '',
                separator: self.options.separator ? self.options.separator: ' ',
                suffix: self.options.suffix,
                numDigits: isdeg ? 5 + self.options.numDigits : self.options.numDigits,
                displayProjection: srs.projection };
            mbMap.map.olMap.addControl(new OpenLayers.Control.MousePosition(options));
            this.crs = srs.projection.projCode;
        }
    },

    showHidde: function() {
        var self = this;
        var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
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
    _destroy: $.noop
});

})(jQuery);


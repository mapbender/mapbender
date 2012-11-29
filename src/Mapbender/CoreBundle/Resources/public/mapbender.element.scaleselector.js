(function($) {

$.widget("mapbender.mbScaleSelector", {
    options: {
        target: map
    },

    elementUrl: null,

    _create: function() {
        var self = this;
        var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
        $(document).one('mapbender.setupfinished', $.proxy(this._mapbenderSetupFinished, this));
    },
    
    _init: function(){
        var mbMap = $('#' + this.options.target).data('mbMap');
        var scale = mbMap.map.olMap.getScale();
        var scales = mbMap.scales();
        var html = '';
        $.each(scales, function(idx, val) {
            val = Math.round(val);
            html += '<option value="' + val + '">' + val + '</option>';
        });
        $(this.element).html(html);
        $(this.element).change($.proxy(this._zoomToScale, this));
        $(this.element).val(scale);
        mbMap.map.olMap.events.register('zoomend', this, $.proxy(this._updateScale, this));
    },
    
    _zoomToScale: function(){
        var scale = $(this.element).val();
        var map = $('#' + this.options.target).data('mbMap');
        map.zoomToScale(scale);
    },
    
    _updateScale: function(){
        var map = $('#' + this.options.target).data('mbMap');
        var scale = Math.round(map.map.olMap.getScale());
        $(this.element).val(Math.round(scale));
    },
    
    _mapbenderSetupFinished: function() {
      this._init(); 
    },

    _destroy: $.noop
});

})(jQuery);


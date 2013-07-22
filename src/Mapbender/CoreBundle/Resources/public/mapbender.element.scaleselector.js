(function($) {

$.widget("mapbender.mbScaleSelector", {
    options: {
        target: null
    },

    elementUrl: null,

    _create: function() {
        if(!Mapbender.checkTarget("mbScaleSelector", this.options.target)){
            return;
        }
        var self = this;
        var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';

        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));

    },

    _setup: function(){
        var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
        var scale = mbMap.map.olMap.getScale();
        var scales = mbMap.scales();
        var html = '';
        $.each(scales, function(idx, val) {
            val = Math.round(val);
            html += '<option value="' + val + '">' + val + '</option>';
        });
        $("#"+$(this.element).attr('id')+" select").html(html);
        $("#"+$(this.element).attr('id')+" select").change($.proxy(this._zoomToScale, this));
        $("#"+$(this.element).attr('id')+" select").val(scale);
        mbMap.map.olMap.events.register('zoomend', this, $.proxy(this._updateScale, this));
    },

    _zoomToScale: function(){
        var scale = $("#"+$(this.element).attr('id')+" select").val();
        var map = $('#' + this.options.target).data('mapbenderMbMap');
        map.zoomToScale(scale);
    },

    _updateScale: function(){
        var map = $('#' + this.options.target).data('mapbenderMbMap');
        var scale = Math.round(map.map.olMap.getScale());
        var val = Math.round(scale);
        var select = $("#"+$(this.element).attr('id')+" select");
        select.val(val).siblings(".dropdownValue").text(val);
    },

    _destroy: $.noop
});

})(jQuery);


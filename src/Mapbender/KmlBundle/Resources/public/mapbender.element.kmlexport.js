(function($) {

$.widget("mapbender.mbKmlExport", {
    options: {},

    elementUrl: null,

    _create: function() {
        var self = this;
        var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
    },

    _destroy: $.noop,

    exportMap: function(targetId) {
        var map = $('#' + targetId).data('mbMap').map,
            mbLayers = map.layers(),
            form = $(this.element);

        form.empty();

        $.each(mbLayers, function(k, v) {
            if(v.options.type !== 'wms') {
                return;
            }

            $('<input></input>')
                .attr('type', 'hidden')
                .attr('name', 'layers[' + v.label + ']')
                .val($.param({
                    params: v.olLayer.params,
                    options: {
                        layers: v.olLayer.options.layers,
                        format: v.olLayer.options.format,
                        url: v.olLayer.options.url,
                        visibility: v.olLayer.visible
                    }
                }))
                .appendTo(form);
        });

        var extent = map.olMap.getExtent();
        $('<input></input>')
            .attr('type', 'hidden')
            .attr('name', 'extent')
            .val(extent.toBBOX())
            .appendTo(form);

        $('<input></input>')
            .attr('type', 'hidden')
            .attr('name', 'srs')
            .val(map.olMap.getProjection())
            .appendTo(form);

        form.submit();
    }
});

})(jQuery);


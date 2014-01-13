(function($){

    $.widget("mapbender.mbKmlExport", {
        options: {},
        elementUrl: null,
        form: null,
        _create: function(){
            var self = this;
            this.form = $(this.element);
            this.elementUrl = Mapbender.configuration.elementPath +
                this.form.attr('id') + '/';
        },
        _destroy: $.noop,
        _layer2form: function(layer){
            if(layer.options.type !== 'wms'){
                return;
            }

            $('<input></input>')
                .attr('type', 'hidden')
                .attr('name', 'layers[' + layer.label + ']')
                .val($.param({
                params: layer.olLayer.params,
                options: {
                    layers: layer.olLayer.options.layers,
                    format: layer.olLayer.options.format,
                    url: layer.olLayer.options.url,
                    visibility: layer.olLayer.visible
                }
            }))
                .appendTo(this.form);
        },
        _map2form: function(targetId){
            var map = $('#' + targetId).data('mapbenderMbMap').map;

            var extent = map.olMap.getExtent();
            $('<input></input>')
                .attr('type', 'hidden')
                .attr('name', 'extent')
                .val(extent.toBBOX())
                .appendTo(this.form);

            $('<input></input>')
                .attr('type', 'hidden')
                .attr('name', 'srs')
                .val(map.olMap.getProjection())
                .appendTo(this.form);
        },
        exportMap: function(targetId){
            var map = $('#' + targetId).data('mapbenderMbMap').map,
                mbLayers = map.layers(),
                self = this;

            this.form.empty();

            $.each(mbLayers, function(k, v){
                self._layer2form(v);
            });

            this._map2form(targetId);

            this.form.submit();
        },
        exportLayer: function(layer){
            var mapId = layer.map.element.attr('id');
            this.form.empty();

            this._layer2form(layer);

            this._map2form(mapId);

            this.form.submit();
        }
    });

})(jQuery);

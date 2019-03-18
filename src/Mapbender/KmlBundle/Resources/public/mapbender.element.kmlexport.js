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
        _sourceToForm: function(model, source) {
            if (source.type !== 'wms') {
                return;
            }
            var olLayer = model.getNativeLayer(source);

            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'layers[' + olLayer.name + ']')
                .val($.param({
                params: olLayer.params,
                options: {
                    format: source.configuration.options.format,
                    url: olLayer.url,
                    visibility: olLayer.getVisibility()
                }
            }))
                .appendTo(this.form);
        },
        _map2form: function(targetId){
            var map = $('#' + targetId).data('mapbenderMbMap').map;

            var extent = map.olMap.getExtent();
            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'extent')
                .val(extent.toBBOX())
                .appendTo(this.form);

            $('<input>')
                .attr('type', 'hidden')
                .attr('name', 'srs')
                .val(map.olMap.getProjection())
                .appendTo(this.form);
        },
        exportMap: function(targetId) {
            var mbMap =$('#' + targetId).data('mapbenderMbMap'),
                model = mbMap.getModel(),
                sources = model.getSources(),
                self = this;

            this.form.empty();
            sources.map(function(source) {
                self._sourceToForm(model, source);
            });

            this._map2form(targetId);

            this.form.submit();
        }
    });

})(jQuery);

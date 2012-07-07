(function($) {

$.widget("mapbender.mbFeatureInfo", $.mapbender.mbButton, {
    options: {
        layers: undefined,
        target: undefined
    },

    map: null,
    mapClickHandler: null,
    dlg: null,

    _create: function() {
        this._super('_create');
    },

    _setOption: function(key, value) {
        switch(key) {
            case "layers":
                this.options.layers = value;
                break;
            default:
                throw "Unknown or unhandled option " + key + " for " + this.namespace + "." + this.widgetName;
        }
    },

    activate: function() {
        var self = this;
        this.map = $('#' + this.options.target).data('mapQuery');
        this.mapClickHandler = function(e) {
            self._triggerFeatureInfo.call(self, e);
        }
        this.map.element.bind('click', self.mapClickHandler);
    },

    deactivate: function() {
        this._super('deactivate');
        if(this.map)
            this.map.element.unbind('click', this.mapClickHandler);
    },

    /**
     * Trigger the Feature Info call for each layer.
     * Also set up feature info dialog if needed.
     */
    _triggerFeatureInfo: function(e) {
        var self = this,
            x = e.pageX - $(this.map.element).offset().left,
            y = e.pageY - $(this.map.element).offset().top;

        if(!this.dlg) {
            this.dlg = $('<div></div>')
                .addClass('mb-element')
                .attr('id', 'featureinfo-dialog')
        }

        this.dlg.empty();
        var tabs = $('<div></div>')
            .attr('id', 'featureinfo-tabs')
            .appendTo(this.dlg);
        var header = $('<ol></ol>')
            .appendTo(tabs);

        // Go over all layers
        $.each(this.map.layers(), function(idx, layer){
            if(layer.options.queryLayers.length == 0) {
                return;
            }
            // Prepare result tab list
            header.append($('<li></li>')
                .append($('<a></a>')
                    .attr('href', '#' + layer.id)
                    .html(layer.label)));
            tabs.append($('<div></div>')
                .attr('id', layer.id));

            switch(layer.options.type) {
                case 'wms':
                    Mapbender.layer.wms.featureInfo(layer, x, y, $.proxy(self._featureInfoCallback, self));
                    break;
            }
        });

        tabs.tabs();
        if(this.dlg.data('dialog')) {
            this.dlg.dialog('open');
        } else {
            this.dlg.dialog({
                title: 'Feature info',
                width: 600,
                height: 400
            });
        }
    },

    /**
     * Once data is coming back from each layer's FeatureInfo call,
     * insert it into the corresponding tab.
     */
    _featureInfoCallback: function(data) {
        //TODO: Needs some escaping love
        this.dlg.find('#' + data.layerId).html(data.response);
    }
});

})(jQuery);

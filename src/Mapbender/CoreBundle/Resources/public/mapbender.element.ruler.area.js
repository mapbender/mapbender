(function($) {

$.widget("mapbender.mbAreaRuler", $.mapbender.mbCommonRuler, {
    options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined,
        immediate: true,
        persist: true,
        title: 'Area'
    },

    _create: function() {
        var sm = $.extend(true, {}, OpenLayers.Feature.Vector.style, {
                'default': this.options.style
            });
        var styleMap = new OpenLayers.StyleMap(sm);

        var control = new OpenLayers.Control.Measure(OpenLayers.Handler.Polygon, {
            handlerOptions: {
                layerOptions: {
                    styleMap: styleMap
                }
            }
        });
        this._super('_create', control);
    }
});

})(jQuery);


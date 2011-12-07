var Mapbender = Mapbender || {};
$.extend(true, Mapbender, { layer: {
    'wmts': {
        create: function(layerDef) {
            var origin = null;
            if(layerDef.configuration.origin) {
                origin = new OpenLayers.LonLat(
                    layerDef.configuration.origin[0],
                    layerDef.configuration.origin[1]);
            }
            mqLayerDef = {
                type:        'wmts',
                label:       layerDef.configuration.title,
                url:         layerDef.configuration.url,

                layer:       layerDef.configuration.layer,
                style:       layerDef.configuration.style,
                matrixSet:   layerDef.configuration.matrixSet,
                format:      layerDef.configuration.format,
                tileOrigin:  origin,

                isBaseLayer: layerDef.configuration.baselayer,
                opacity:     layerDef.configuration.opacity,
                visible:     layerDef.configuration.visible
            };
            return mqLayerDef;
        }
    }
}});


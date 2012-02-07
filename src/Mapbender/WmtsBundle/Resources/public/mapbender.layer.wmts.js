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

            tileSize = null;
            if(layerDef.configuration.tileSize) {
                tileSize = new OpenLayers.Size(
                    layerDef.configuration.tileSize[0],
                    layerDef.configuration.tileSize[1]);
            }

            tileFullExtent = null;
            if(layerDef.configuration.tileFullExtent) {
                tileFullExtent = OpenLayers.Bounds.fromArray(
                    layerDef.configuration.tileFullExtent);
            }

            mqLayerDef = {
                type:        'wmts',
                label:       layerDef.configuration.title,
                url:         layerDef.configuration.url,

                layer:       layerDef.configuration.layer,
                style:       layerDef.configuration.style,
                matrixSet:   layerDef.configuration.matrixSet,
                matrixIds:   layerDef.configuration.matrixIds,
                format:      layerDef.configuration.format,
                tileOrigin:  origin,
                tileSize:    tileSize,
                tileFullExtent: tileFullExtent,

                isBaseLayer: layerDef.configuration.baselayer,
                opacity:     layerDef.configuration.opacity,
                visible:     layerDef.configuration.visible,

                attribution: layerDef.configuration.attribution
            };
            return mqLayerDef;
        }
    }
}});


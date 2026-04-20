window.Mapbender = Mapbender || {};

(function () {
    Mapbender.XyzSourceLayer = class XyzSourceLayer extends Mapbender.SourceLayer {

        constructor(definition, source, parent) {
            super(definition, source, parent);
        }

        hasBounds() {
            return false;
        }

        supportsProjection(srsName) {
            // OpenLayers handles reprojection for XYZ tiles
            return true;
        }
    };

    Mapbender.SourceLayer.typeMap['xyz'] = Mapbender.XyzSourceLayer;
})();

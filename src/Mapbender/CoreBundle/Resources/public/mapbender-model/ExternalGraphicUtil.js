window.Mapbender = Mapbender || {};
window.Mapbender.Util = Mapbender.Util || {};

/**
 * Utilities for handling external graphics in OpenLayers.
 * In most cases, just getIconStyle needs to be called externally
 */
window.Mapbender.Util.ExternalGraphicUtil = class {

    /**
     * Creates an ol style (or iconStyle) that displays an external graphic
     * @param {{externalGraphic: string, graphicWidth: ?int, graphicHeight: ?int}} styleConfig
     * @param {boolean} [iconStyleOnly=false] if true, only the IconStyle instead of the full ol style is returned
     * @return {ol.Style|ol.style.Icon}
     */
    static getIconStyle(styleConfig, iconStyleOnly) {
        if (!styleConfig.externalGraphic) return null;

        const iconStyle = new ol.style.Icon({
            src: styleConfig.externalGraphic
        });
        if (styleConfig.graphicWidth || styleConfig.graphicHeight) {
            const onload = Mapbender.Util.ExternalGraphicUtil.getIconScaleHandler(iconStyle, styleConfig);
            // see https://github.com/openlayers/openlayers/blob/main/src/ol/ImageState.js
            if (iconStyle.getImageState() === 2) {
                // already loaded
                onload();
            } else {
                iconStyle.listenImageChange(onload);
            }
        }
        return iconStyleOnly === true ? iconStyle : Mapbender.Util.ExternalGraphicUtil.expandIconStyle(iconStyle);
    }

    static getIconScaleHandler(iconStyle, styleConfig) {
        return (function (styleConfig) {
            return function () {
                /** @this ol.style.Image */
                if (this.getImageState() === 2) {
                    // Now loaded
                    // see https://github.com/openlayers/openlayers/blob/main/src/ol/ImageState.js
                    const naturalSize = this.getImageSize();
                    let scale;
                    if (!styleConfig.graphicHeight) {
                        scale = styleConfig.graphicWidth / naturalSize[0];
                    } else if (!styleConfig.graphicWidth) {
                        scale = styleConfig.graphicHeight / naturalSize[1];
                    } else {
                        scale = [styleConfig.graphicWidth / naturalSize[0], styleConfig.graphicHeight / naturalSize[1]];
                    }
                    this.setScale(scale);
                }
            }.bind(iconStyle);
        }(styleConfig));
    }

    /**
     * @param {ol.style.Image} iconStyle
     * @return {ol.style.Style}
     * @private
     */
    static expandIconStyle(iconStyle) {
        return new ol.style.Style({
            // Icons are only rendered on point geometries.
            // => We must use a geometry function to make points out of
            // polygons and lines.
            // @see https://gis.stackexchange.com/questions/361817/openlayers-displaying-polygon-with-icon-style
            geometry: Mapbender.Util.ExternalGraphicUtil.iconStyleGeometryFunction,
            image: iconStyle
        });
    }

    static iconStyleGeometryFunction(feature) {
        const geometry = feature.getGeometry();
        switch (geometry && geometry.getType()) {
            case 'Polygon':
                return geometry.getInteriorPoint();
            case 'MultiPolygon':
                return geometry.getInteriorPoints();
            case 'LineString':
                return new ol.geom.Point(geometry.getFlatMidpoint(), geometry.getLayout());
            case 'MultiLineString':
                return new ol.geom.MultiPoint(geometry.getFlatMidpoints(), geometry.getLayout());
            default:
                return geometry;
        }
    }

};

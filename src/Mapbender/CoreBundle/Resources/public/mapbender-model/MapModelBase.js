window.Mapbender = Mapbender || {};
window.Mapbender.MapModelBase = (function() {
    function MapModelBase(mbMap) {
        this.mbMap = mbMap;
        this.sourceBaseId_ = 1;
    }

    MapModelBase.prototype = {
        constructor: MapModelBase,
        mbMap: null,
        sourceBaseId_: null,
        /**
         * @return {number}
         * engine-agnostic
         */
        getCurrentScale: function() {
            return (this._getScales())[this.getCurrentZoomLevel()];
        },
        /**
         * engine-agnostic
         */
        pickZoomForScale: function(targetScale, pickHigh) {
            // @todo: fractional zoom: use exact targetScale (TBD: method should not be called?)
            var scales = this._getScales();
            var scale = this._pickScale(scales, targetScale, pickHigh);
            return scales.indexOf(scale);
        },
        /**
         * @return {Array<Object>}
         * engine-agnostic
         */
        getZoomLevels: function() {
            return this._getScales().map(function(scale, index) {
                return {
                    scale: scale,
                    level: index
                };
            });
        },
        /**
         * @param {Source} source
         * @param {number} opacity float in [0;1]
         * engine-agnostic
         */
        setOpacity: function(source, opacity) {
            // unchecked findSource in layertree may pass undefined for source
            if (source) {
                var opacity_ = parseFloat(opacity);
                if (isNaN(opacity_)) {
                    opacity_ = 1.0;
                }
                opacity_ = Math.max(0.0, Math.min(1.0, opacity_));
                if (opacity_ !== opacity) {
                    console.warn("Invalid-ish opacity, clipped to " + opacity_.toString(), opacity);
                }
                source.setOpacity(opacity_);
            }
        },
        /**
         * Updates the source identified by given id with a new layer order.
         * This will pull styles and "state" (such as visibility) from values
         * currently stored in the "geosource".
         *
         * @param {string} sourceId
         * @param {string[]} newLayerIdOrder
         * engine-agnostic
         */
        setSourceLayerOrder: function(sourceId, newLayerIdOrder) {
            var sourceObj = this.getSourceById(sourceId);
            var geoSource = Mapbender.source[sourceObj.type];

            geoSource.setLayerOrder(sourceObj, newLayerIdOrder);

            this.mbMap.fireModelEvent({
                name: 'sourceMoved',
                // no receiver uses the bizarre "changeOptions" return value
                // on this event
                value: null
            });
            this._checkSource(sourceObj, true, false);
        },
        /**
         * Gets a mapping of all defined extents for a layer, keyed on SRS
         * @param {Object} options
         * @property {String} options.sourceId
         * @property {String} options.layerId
         * @return {Object<String, Array<Number>>}
         * engine-agnostic
         */
        getLayerExtents: function(options) {
            var source = this.getSourceById(options.sourceId);
            if (source) {
                return source.getLayerExtentConfigMap(options.layerId, true, true);
            } else {
                console.warn("Source not found", options);
                return null;
            }
        },
        generateSourceId: function() {
            var id = 'auto-src-' + (this.sourceBaseId_ + 1);
            ++this.sourceBaseId_;
            return id;
        },
        /**
         *
         * @param scales
         * @param targetScale
         * @param pickHigh
         * @return {*}
         * @private
         * engine-agnostic
         */
        _pickScale: function(scales, targetScale, pickHigh) {
            if (targetScale >= scales[0]) {
                return scales[0];
            }
            for (var i = 0, nScales = scales.length; i < nScales - 1; ++i) {
                var scaleHigh = scales[i];
                var scaleLow = scales[i + 1];
                if (targetScale <= scaleHigh && targetScale >= scaleLow) {
                    if (targetScale > scaleLow && pickHigh) {
                        return scaleHigh;
                    } else {
                        return scaleLow;
                    }
                }
            }
            return scales[nScales - 1];
        },
        /**
         * engine-agnostic
         */
        _getMaxZoomLevel: function() {
            // @todo: fractional zoom: no discrete scale steps
            return this._getScales().length - 1;
        },
        /**
         * engine-agnostic
         */
        _clampZoomLevel: function(zoomIn) {
            return Math.max(0, Math.min(zoomIn, this._getMaxZoomLevel()));
        }
    };
    return MapModelBase;
}());

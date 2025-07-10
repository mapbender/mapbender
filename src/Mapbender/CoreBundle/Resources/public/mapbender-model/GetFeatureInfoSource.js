(function () {
    /**
     * A source that supports GetFeatureInfo requests, base class for WMS and WMTS.
     * @abstract
     */
    Mapbender.GetFeatureInfoSource = class GetFeatureInfoSource extends Mapbender.Source {
        /**
         * Returns all layers that support feature info
         * @return {Array<Mapbender.SourceLayer>}
         */
        getFeatureInfoLayers() {
            return [];
        }

        featureInfoEnabled() {
            return this.getFeatureInfoLayers().length > 0;
        }

        /**
         * Loads the feature info for the given coordinates.
         * in the GetFeatureInfo implementation the URL as returned
         * by Mapbender.Model.getPointFeatureInfoUrl is requested and the response is returned.
         * @param mapModel Mapbender.Model
         * @param x
         * @param y
         * @param options {maxCount: number}
         * @returns [string, {Promise<{iframe: boolean, content: string}>}]
         */
        loadFeatureInfo(mapModel, x, y, options) {
            const url = this.getPointFeatureInfoUrl(this, x, y, options.maxCount);
            if (!url) return [false, Promise.reject()];

            let fetchOptions = {};
            let fetchUrl = url;

            // also use proxy on different host / scheme to avoid CORB
            const useProxy = this.options.proxy || !Mapbender.Util.isSameSchemeAndHost(url, window.location.href);

            if (useProxy && !this.options.tunnel) {
                fetchUrl = Mapbender.configuration.application.urls.proxy;
                fetchOptions = {
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: new URLSearchParams({ url: url })
                };
            }

            return fetch(fetchUrl, fetchOptions)
                .then(response => {
                    const mimetype = (response.headers.get('Content-Type') || '').toLowerCase().split(';')[0];
                    return response.text().then(data => ({ data: data.trim(), mimetype }));
                })
                .then(response => {
                    const mimetype = (response.headers.get('Content-Type') || '').toLowerCase().split(';')[0];
                    return response.text().then(data => {
                        data = data.trim();
                        if (data.length && (!options.onlyValid || this._isFeatureInfoResponseDataValid(data, mimetype))) {
                            return this._formatFeatureInfoResponse(source, data, mimetype);
                        }
                    });
                })
                .catch(error => {
                    Mapbender.error(source.getTitle() + ' GetFeatureInfo: ' + error);
                });
        }

        getPointFeatureInfoUrl (mapModel, x, y, maxCount) {
            const layerNames = this.getFeatureInfoLayers().map(function(layer) {
                return layer.options.name;
            });
            const engine = Mapbender.mapEngine;
            const olLayer = this.getNativeLayer(0);
            if (!(layerNames.length && olLayer && engine.getLayerVisibility(olLayer))) {
                return false;
            }
            var params = $.extend({}, this.customParams || {}, {
                QUERY_LAYERS: layerNames.join(','),
                STYLES: (Array(layerNames.length)).join(','),
                INFO_FORMAT: this.options.info_format || 'text/html',
                EXCEPTIONS: this.options.exception_format,
                FEATURE_COUNT: maxCount || 100
            });
            params.LAYERS = params.QUERY_LAYERS;
            const olMap = mapModel.olMap;

            /** @var {ol.source.ImageWMS|ol.source.TileWMS} nativeSource */
            var nativeSource = olLayer.getSource();
            if (!nativeSource.getFeatureInfoUrl) {
                return null;
            }
            const res = olMap.getView().getResolution();
            const proj = olMap.getView().getProjection().getCode();
            const coord = olMap.getCoordinateFromPixel([x, y]);
            return Mapbender.Util.removeProxy(nativeSource.getFeatureInfoUrl(coord, res, proj, params));
        }

        _isFeatureInfoResponseDataValid(data, mimetype) {
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    return !!("" + data).match(/<[/][a-z]+>/gi);
                case 'text/plain':
                    return !!("" + data).match(/[^\s]/g);
                default:
                    return true;
            }
        }
        _formatFeatureInfoResponse(source, data, mimetype) {
            if (mimetype.toLowerCase() === 'text/html') {
                const script = this._getInjectionScript(source.id);
                const $iframe = $('<iframe sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox allow-downloads" class="iframe--responsive">');
                $iframe.attr("srcdoc", [script, data].join(''));
                return $iframe.get();
            } else {
                return $(document.createElement('pre')).text(data).get();
            }
        }
    }

    Mapbender.Source.typeMap = {};
}());

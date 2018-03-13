/*jslint browser: true, nomen: true*/
/*globals initDropdown, Mapbender, OpenLayers, Proj4js, _, jQuery*/

(function ($) {
    'use strict';
    $.widget("mapbender.mbUtfGridInfo", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            debug: false
        },
        readyCallbacks: [],
        layerState: [],
        _create: function () {
            if (!Mapbender.checkTarget("mbUtfGridInfo", this.options.target)) {
                return;
            }
            this.popupId = "mbUtfGridInfo" + this.element.attr('id');
            this.popupClass = OpenLayers.Popup.FramedCloud
            Mapbender.elementRegistry.onElementReady(this.options.target, this._setup.bind(this));
        },
        _setup: function() {
            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            this.layerState = this.findMonitoredLayers();
            this.initializeControls(this.layerState);

            this._trigger('ready');
            this._ready();
        },
        findMonitoredLayers: function() {
            // This doesn't integrate well at all with mbMap.model etc because
            // 1) We do not have enough information to use mbMap.model.findSource, because it doesn't support scanning
            //    for attribute presence, only for attribute equality. We don't know the value of "gridlayer" yet...
            // 2) We cannot use mbMap.model.findLayer because it will land flat on its face when looking for an option
            //    value which is not always present in its glorious config tree, such as "gridlayer", effectively calling
            //    undefined.toString(); plus, literally none of the info it returns is interesting to us

            // ... so we go directly to the source tree
            // Filter down to only sources containing a non-empty "gridlayer" option
            var sourceTree = _.filter(this.mbMap.getSourceTree(), function(s) {
                return s.type == 'wms'
                    && s.configuration && s.configuration.options
                    && s.configuration.options.gridlayer;
            });
            var layerState = [];
            _.forEach(sourceTree, function(sourceDef) {
                // make a base url for UTFGrid requests by stripping "FORMAT=..." query parameter, case-insensitive
                var originalUrl = sourceDef.configuration.options.url;
                var baseUrl = originalUrl.replace(/([\&\?])(format=[^\&]*\&?)/i, function(matches, p1) {
                    return p1;
                });

                // now use mbMap.model.findLayer to get a reference to the current layer config
                var sourceOptions = {origId: sourceDef.origId};
                var layerOptions = {name: sourceDef.configuration.options.gridlayer};
                var layerFind = this.mbMap.model.findLayer(sourceOptions, layerOptions);
                if (layerFind) {
                    layerState.push({
                        layer: null,
                        control: null,
                        state: null,
                        olName: "mbUtfGridInfo" + layerFind.layer.options.origId,
                        config: layerFind.layer,
                        sourceConfig: sourceDef,
                        sourceOptions: sourceDef.configuration.options,
                        parentConfig: layerFind.parent,
                        layerTreeId: layerFind.layer.options.id,
                        origId: layerFind.layer.options.origId,
                        visibleLayerName: layerFind.layer.options.name,
                        baseUrl: baseUrl
                    });
                }
            }.bind(this));
            return layerState;
        },
        isActive: function() {
            // HACK for testing
            return true;
        },
        controlOnHover: function(data, lonLat, position) {
            if (data) {
                // console.log("Receving data", data, lonLat, position);
                // Data is keyed on internal OpenLayers layer index, which isn't particularly useful.
                // "Recalculate" the full layerstate.
                var perLayerData = _.map(Object.keys(data), function (layerIndex) {
                    if (data[layerIndex] && data[layerIndex].data) {
                        var olLayer = this.mbMap.map.olMap.layers[layerIndex];
                        var layerState = _.findWhere(this.layerState, {olName: olLayer.name});
                        if (layerState) {
                            return {
                                layerState: layerState,
                                featureData: data[layerIndex].data
                            };
                        }
                    }
                }.bind(this));
                // throw away emptyish (undefined) entries
                perLayerData = _.filter(perLayerData, Boolean);
                // console.log("Filtered data", perLayerData);
                if (perLayerData.length) {
                    this.displayData(perLayerData, lonLat, position);
                } else {
                    return;
                    // mockup
                    var mockupData = {
                        layerState: _.first(this.layerState),
                        featureData: {
                            'name': "Der gute Name",
                            'disposition': "Unverfänglich"
                        }
                    };
                    this.displayData([mockupData], lonLat, position);
                }
            }
        },
        displayData: function(perLayerData, lonLat, position) {
            var layerGroups = _.map(perLayerData, function (layerData) {
                var layerTitle = layerData.layerState.config.options.title;
                // translate labels
                // @todo: enforce attribute order?
                var attributes = _.map(layerData.featureData, function (value, key) {
                    var label = this.options.labelFormats && this.options.labelFormats[key] || key;
                    return {
                        label: label,
                        value: value
                    };
                }.bind(this));
                return {
                    layerTitle: layerTitle,
                    attributes: attributes
                };
            }.bind(this));
            // console.log("Transformed display data", layerGroups, perLayerData);
            var $html = $('<ul>');
            _.forEach(layerGroups, function (lg) {
                var $header = $('<li>').text(lg.layerTitle);
                $html.append($header);
                _.forEach(lg.attributes, function (o) {
                    $html.append($('<li>').text(o.label + ": " + o.value));
                });
            });
            var popupHtml = $html.html();

            if (this.popup && !this.popup.lonlat.equals(lonLat)) {
                this.popup.destroy();
            }
            this.popup = new this.popupClass(this.popupId, lonLat, null, popupHtml, null, true, this.onPopupClose.bind(this));
            this.mbMap.map.olMap.addPopup(this.popup);
        },
        initializeControls: function(states) {
            var self = this;
            _.forEach(states || this.layerState, function(state) {
                if (!state.layer) {
                    var params = {
                        layers: [state.visibleLayerName],
                        url: state.baseUrl,
                        format: "application/json"
                    };
                    var mergedOptions = {
                        utfgridResolution: 4,
                        // avoid POST requests at all costs (CORS; POST may produce an unsupported OPTIONS preflight)
                        maxGetUrlLength: null,
                        singleTile: true,
                        tiled: false,
                        ratio: state.sourceOptions.ratio || 1.0,
                        buffer: state.sourceOptions.buffer || 0
                    };
                    state.layer = new OpenLayers.Layer.UTFGridWMS(state.olName, state.baseUrl, params, mergedOptions);
                    self.mbMap.map.olMap.addLayer(state.layer);
                }
                if (!state.control) {
                    // @todo: reuse single control for multiple layers
                    state.control = new OpenLayers.Control.UTFGridWMS({
                        callback: self.controlOnHover.bind(self),
                        layers: [state.layer],
                        handlerMode: "hover"
                    });
                    self.mbMap.map.olMap.addControl(state.control);
                }
            });
        },
        /**
         * @todo: handle 'mbmapsourcechanged' event
         */
        handleSourceChanged: function() {
            console.log("Source changed event", arguments);
        },
        onPopupClose: function() {
           if (this.popup) {
               this.popup.destroy();
               this.popup = null;
           }
        }
    });
})(jQuery);

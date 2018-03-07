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
        watchedLayers: [],
        _create: function () {
            var self = this;
            if (!Mapbender.checkTarget("mbUtfGridInfo", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            var self = this;
            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            this.layersetId = "" +  _.first(this.mbMap.options.layersets);
            this.model = this.mbMap.getModel();
            this.sourceTree = _.assign.apply({}, Mapbender.configuration.layersets[this.layersetId]);
            this.monitoredInstances = _.filter(this.sourceTree, function (o) {
                return !!o.configuration && !!o.configuration.gridlayer && !!o.configuration.children;
            });
            _.each(this.monitoredInstances, function(o) {
                self.watchedLayers.push({
                    name: o.configuration.gridlayer
                });
            });

            this.state = _.assign.apply({}, _.map(this.monitoredInstances, this._buildInstanceState.bind(this)));
            this._initializeControls();
            console.log(this.state, this.model.sourceTree, this.mbMap, this.monitoredInstances, this.sourceTree);
            this._trigger('ready');
            this._ready();
        },
        _isActive: function() {
            // HACK for testing
            return true;
        },
        _renderData: function(data, lonLat, position) {
            if (data) {
                console.log("mbUtfGridInfo received", data, lonLat, position);
            }
        },
        _initializeControls: function() {
            var self = this;
            var matches = [];
            _.each(this.watchedLayers, function (watchedLayerDef) {
                _.each(self.state, function (o, layersetId) {
                    Array.prototype.push.apply(matches, _.where(o.layers, {name: watchedLayerDef.name}));
                });
            });
            _.each(matches, function(matchState) {
                if (!matchState.layer) {
                    console.log("Initializing layer for", matchState);
                    var olLayerName = "mbUtfGridInfo" + matchState.origId;
                    var params = {
                        layers: [matchState.name],
                        url: matchState.baseUrl,
                        format: "application/json"
                    };
                    var mergedOptions = {
                        utfgridResolution: 4,
                        singleTile: true
                    };
                    matchState.layer = new OpenLayers.Layer.UTFGridWMS(olLayerName, matchState.baseUrl, params, mergedOptions);
                    console.log("New layer", matchState.layer);
                    self.mbMap.map.olMap.addLayer(matchState.layer);
                }
                if (!matchState.control) {
                    console.log("Initializing control for", matchState);
                    matchState.control = new OpenLayers.Control.UTFGridWMS({
                        callback: self._renderData.bind(self),
                        layers: [matchState.layer],
                        handlerMode: "hover"
                    });
                    console.log("New control", matchState.control);
                    self.mbMap.map.olMap.addControl(matchState.control);
                }
            });
        },
        _buildLayerState: function(layerDef, baseUrl) {
            var buildLayerStateRecursive = function(layerDef) {
                var oOut = {};
                if (!layerDef || !layerDef.options) {
                    console.log("Hmmm?", layerDef);
                }
                oOut[layerDef.options.origId] = {
                    layer: null,
                    control: null,
                    state: null,
                    config: layerDef,
                    layerTreeId: layerDef.options.id,
                    origId: layerDef.options.origId,
                    name: layerDef.options.name,
                    baseUrl: baseUrl
                };
                var oOut = _.assign.apply(null, [oOut].concat(_.map(layerDef.children || [], buildLayerStateRecursive)));

                return oOut;
            };
            return buildLayerStateRecursive(layerDef);
        },
        _buildInstanceState: function(inst) {
            var instanceState = {};
            console.log("Building state for instance", inst);
            instanceState[inst.id] = {
                id: inst.id,
                // strip "FORMAT=..." query parameter, case-insensitive
                baseUrl: inst.configuration.options.url.replace(/([\&\?])(format=[^\&]*\&?)/i, function(matches, p1) {
                    return p1;
                }),
                config: inst
            };
            var rootLayerLayers = (inst.configuration.children || [[]])[0];
            instanceState[inst.id].layers = this._buildLayerState(rootLayerLayers, instanceState[inst.id].baseUrl);
            return instanceState;
        },
    });
})(jQuery);

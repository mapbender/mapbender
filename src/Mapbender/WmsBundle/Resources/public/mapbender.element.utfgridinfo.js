/*jslint browser: true, nomen: true*/
/*globals initDropdown, Mapbender, OpenLayers, Proj4js, _, jQuery*/

(function ($) {
    'use strict';
    $.widget("mapbender.mbUtfGridInfo", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            debug: false,
            // null for everything, otherwise whitelist (array of strings = keys in data object)
            displayableAttributes: null,
            // data key => displayable label mapping
            labelFormats: {},
            usePopup: true,
            showInline: false,
            showLayerTitle: false,
            showDataLabels: false
        },
        readyCallbacks: [],
        layerState: [],
        _create: function () {
            if (!Mapbender.checkTarget("mbUtfGridInfo", this.options.target)) {
                return;
            }
            this.popupId = "mbUtfGridInfo" + this.element.attr('id');
            this.popupClass = OpenLayers.Popup.FramedCloudModern
            Mapbender.elementRegistry.onElementReady(this.options.target, this._setup.bind(this));
        },
        _setup: function() {
            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            this.layerState = this.findMonitoredLayers();
            this.initializeControls(this.layerState);

            this.renderers = [];
            var popupRenderer = new rendererPopup(this.mbMap.map.olMap, this.popupClass, this.options);
            popupRenderer.formatAttribute = this.formatAttribute.bind(this);
            this.renderers.push(popupRenderer);
            if (this.options.showInline && $(this.element).closest('.toolBarItem').length) {
                var inlineRenderer = new rendererToolBarInline(this.element, this.options);
                inlineRenderer.formatAttribute = this.formatAttribute.bind(this);
                this.renderers.push(inlineRenderer);
            }
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
        preFilterLayerData: function(layerState, rawData) {
            var displayables = this.options.displayableAttributes;
            var dataObj;
            var dataList;
            var _unfold;
            if (displayables !== null) {
                dataObj = _.pick.apply(null, [rawData].concat(displayables));
            } else {
                dataObj = rawData;
            }
            // unfold data object into list, add label information (if any)
            // if displayables is available, also enforce ordering
            var _unfoldSingle = function(d, key) {
                return {
                    key: key,
                    value: d[key],
                    label: this.options.labelFormats && this.options.labelFormats[key] || key
                };
            }.bind(this, dataObj);
            if (displayables !== null) {
                dataList = _.map(displayables, _unfoldSingle);
            } else {
                dataList = _.map(Object.keys(dataObj), unfoldSingle);
            }
            if (dataList.length) {
                return {
                    layerState: layerState,
                    layerTitle: layerState.config.options.title,
                    featureAttributes: dataList
                };
            } else {
                return undefined;
            }
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
                            return this.preFilterLayerData.call(this, layerState, data[layerIndex].data);
                        }
                    }
                }.bind(this));
                // throw away emptyish (undefined) entries
                perLayerData = _.filter(perLayerData, Boolean);
                if (false && !perLayerData.length) {
                    // mockup
                    var mockupData = {
                        layerState: _.first(this.layerState),
                        featureAttributes: [{
                            label: "Name-Label",
                            value: "Der gute Name"
                        }, {
                            label: "Disposition",
                            value: "Unverfänglich"
                        }]
                    };
                    perLayerData = [mockupData];
                }
                if (perLayerData.length) {
                    this.displayData(perLayerData, lonLat, position);
                }
            }
        },
        formatAttribute: function (attributeObject) {
            if (this.options.showDataLabels) {
                return attributeObject.label + ": " + attributeObject.value;
            } else {
                return attributeObject.value;
            }
        },
        displayData: function(layerGroups, lonLat, position) {
            _.forEach(this.renderers, function(renderer) {
                renderer.render(layerGroups, lonLat);
            });
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

    function rendererPopup(olMap, popupClass, options) {
        this.olMap = olMap;
        this.popupClass = popupClass;
        this.options = $.extend({}, options, {
            cssClass: "mb-popup-utfgridinfo",
        });
        this.popup = null;
    }
    rendererPopup.prototype.render = function(valueMap, lonLat) {
        var $html = $('<ul/>');
        _.forEach(valueMap, function (lg) {
            var $layerData = $('<li>').addClass('layer-data');
            if (this.options.showLayerTitle) {
                $layerData.append($('<label>').text(lg.layerTitle));
            }
            var $attributeList = $('<ul>');
            _.forEach(lg.featureAttributes, function (o) {
                $attributeList.append($('<li>').text(this.formatAttribute(o)));
            }.bind(this));
            $layerData.append($attributeList);
            $html.append($layerData);
        }.bind(this));
        // .html gives CONTENTS, so wrap it in a transient div
        var popupHtml = $('<div>').append($html).html();

        if (this.popup && !this.popup.lonlat.equals(lonLat)) {
            this.popup.destroy();
        }
        this.popup = new this.popupClass(this.popupId, lonLat, null, popupHtml, null, true, this.destroy.bind(this));
        this.popup.div.className = (this.popup.div.className || '') + ' ' + this.options.cssClass;
        this.olMap.addPopup(this.popup);
    };
    rendererPopup.prototype.destroy = function() {
        if (this.popup) {
            this.popup.destroy();
            this.popup = null;
        }
    };

    function rendererToolBarInline(element, options) {
        this.$element = $(element);
        this.options = options;
    }
    rendererToolBarInline.prototype.render = function(valueMap) {
        // only display one layer
        var layerData = _.first(valueMap);
        var $html = $('<label>').attr('title', 'Vom Layer ' + layerData.layerTitle);
        // only display one attribute
        var attribData = _.first(layerData.featureAttributes);
        $html.text(this.formatAttribute(attribData));
        // .html gives CONTENTS, so wrap it in a transient div
        var htmlBody = $('<div>').append($html).html();
        this.$element.html(htmlBody);
    };

})(jQuery);

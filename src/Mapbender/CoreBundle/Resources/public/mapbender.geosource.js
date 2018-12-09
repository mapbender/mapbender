var Mapbender = Mapbender || {};
/**
 * Simple event dispatcher
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 11.08.2014 by WhereGroup GmbH & Co. KG
 */
Mapbender.Event = {};
Mapbender.Event.Dispatcher = Class({
}, {
    'private object listeners': {},
    on: function(name, callback) {
        if (!this.listeners[name]) {
            this.listeners[name] = [];
        }
        this.listeners[name].push(callback);
        return this;
    },
    off: function(name, callback) {
        if (!this.listeners[name]) {
            return;
        }
        if (callback) {
            var listeners = this.listeners[name];
            for (var i in listeners) {
                if (callback == listeners[i]) {
                    listeners.splice(i, 1);
                    return;
                }
            }
        } else {
            delete this.listeners[name];
        }

        return this;
    },
    dispatch: function(name, data) {
        if (!this.listeners[name]) {
            return;
        }

        var listeners = this.listeners[name];
        for (var i in listeners) {
            listeners[i](data);
        }
        return this;
    }
});
/**
 * Abstract Geo Source Handler
 * @author Paul Schmidt
 */
Mapbender.Geo = {
    'layerOrderMap': {}
};
Mapbender.Geo.SourceHandler = Class({
    'extends': Mapbender.Event.Dispatcher
}, {
    _layerOrderMap: {},
    'private string layerNameIdent': 'name',
    'private object defaultOptions': {},
    'abstract public function create': function(options) {
    },
    'abstract public function featureInfoUrl': function(layer, x, y) {
    },
    'abstract public function getPrintConfig': function(layer, bounds, isProxy) {
    },
    'abstract public function createSourceDefinitions': function(xml, options) {
    }, // to remove
    'public function changeDefaultOptions': function(defaultOptions) {
        $.extend(this.defaultOptions, defaultOptions);
    },
    'public function fire': function(eventName) {

    },
    'public function on': function(eventName) {

    },
    'public function postCreate': function(olLayer) {

    },
//    _addProxy: function(url){
//        return OpenLayers.ProxyHost + encodeURIComponent(url);
//    },
//    _removeProxy: function(url){
//        if(url.indexOf(OpenLayers.ProxyHost) === 0) {
//            return decodeURIComponent(url.substring(OpenLayers.ProxyHost.length));
//        }
//        return url;
//    },
    'public function removeSignature': function(url){
        var pos = -1;
        pos = url.indexOf("_signature");
        if(pos !== -1) {
            var url_new = url.substring(0, pos);
            if(url_new.lastIndexOf('&') === url_new.length - 1) {
                url_new = url_new.substring(0, url_new.lastIndexOf('&'));
            }
            if(url_new.lastIndexOf('?') === url_new.length - 1) {
                url_new = url_new.substring(0, url_new.lastIndexOf('?'));
            }
            return url_new;
        }
        return url;
    },
    'public function changeProjection': function(source, projection) {
    },
    'public function onLoadStart': function(source) {

    },
    'public function onLoadError': function(imgEl, sourceId, projection, callback) {
        var loadError = {
            sourceId: sourceId,
            details: ''
        };
        var url = Mapbender.configuration.application.urls.proxy + "?url="
            + encodeURIComponent(Mapbender.Util.removeProxy(imgEl.attr('src')));
        $.ajax({
            type: "GET",
            async: false,
            url: url,
            success: function(message, text, response) {
                if (typeof (response.responseText) === "string") {
                    var details = Mapbender.trans("mb.geosource.image_error.datails");
                    var layerTree;
                    try {
                        layerTree = new OpenLayers.Format.WMSCapabilities().read(response.responseText);
                    } catch (e) {
                        layerTree = null;
                        details += ".\n" + Mapbender.trans("mb.geosource.image_error.exception", {
                            'exception': e.toString()
                        });
                    }
                    if (layerTree && layerTree.error) {
                        if (layerTree.error.exceptionReport && layerTree.error.exceptionReport.exceptions) {
                            var excs = layerTree.error.exceptionReport.exceptions;
                            details += ":";
                            for (var m = 0; m < excs.length; m++) {
                                var exc = excs[m].code;
                                details += "\n" + exc;
                                if (excs[m].code == "InvalidSRS") {
                                    details += " (" + projection.projCode + ")";
                                }
                            }
                        }
                    }
                }
                loadError.details = details;
                callback(loadError);
            },
            error: function(err) {
                var details = Mapbender.trans("mb.geosource.image_error.datails");
                if (err.status == 200) {
                    var capabilities;
                    try {
                        capabilities = new OpenLayers.Format.WMSCapabilities().read(err.responseText);
                    } catch (e) {
                        capabilities = null;
                        details += ".\n" + Mapbender.trans("mb.geosource.image_error.exception", {
                            'exception': e.toString()
                        });
                    }
                    if (capabilities && capabilities.error) {
                        if (capabilities.error.exceptionReport && capabilities.error.exceptionReport.exceptions) {
                            var excs = capabilities.error.exceptionReport.exceptions;
                            details += ":";
                            for (var m = 0; m < excs.length; m++) {
                                var exc = excs[m].code;
                                details += "\n" + exc;
                                if (excs[m].code == "InvalidSRS") {
                                    details += " (" + projection.projCode + ")";
                                }
                                if (exc != excs[m].code) {

                                } else if (excs[m].text) {
                                    details += "\n" + excs[m].text;
                                }
                            }
                        }
                    }
                } else {
                    details += ".\n" + Mapbender.trans(
                        "mb.geosource.image_error.statuscode") + ": " + err.status + " - " + err.statusText;
                }
                loadError.details = details;
                callback(loadError);
            }
        });
    },
    'public function getLayersList': function(source, offsetLayer, includeOffset) {
        var rootLayer,
            _source;
        _source = $.extend(true, {}, source);//.configuration.children[0];
        rootLayer = _source.configuration.children[0];
        var options = {
            layers: [
            ],
            found: false,
            includeOffset: includeOffset
        };
        if (rootLayer.options.id.toString() === offsetLayer.options.id.toString()) {
            options.found = true;
        }
        options = _findLayers(rootLayer, offsetLayer, options);
        return {
            source: _source,
            layers: options.layers
        };

        function _findLayers(layer, offsetLayer, options) {
            if (layer.children) {
                var i = 0;
                for (; i < layer.children.length; i++) {
                    if (layer.children[i].options.id.toString() === offsetLayer.options.id.toString()) {
                        options.found = true;
                    }
                    if (options.found) {
                        var matchOffset = i;
                        if (!options.includeOffset) {
                            matchOffset += 1;
                        }
                        var matchLength = layer.children.length - matchOffset;
                        // splice modifies the original Array => work with a shallow copy
                        var layersCopy = layer.children.slice();
                        var matchedLayers = layersCopy.splice(matchOffset, matchLength);
                        options.layers = options.layers.concat(matchedLayers);

                        break;
                    }
                    options = _findLayers(layer.children[i], offsetLayer, options);
                }
            }
            return options;
        }
    },
    'public function addLayer': function(source, layerToAdd, parentLayerToAdd, position) {
        var rootLayer = source.configuration.children[0];
        var options = {
            layer: null
        };
        options = _addLayer(rootLayer, layerToAdd, parentLayerToAdd, position, options);
        return options.layer;

        function _addLayer(layer, layerToAdd, parentLayerToAdd, position, options) {
            if (layer.options.id.toString() === parentLayerToAdd.options.id.toString()) {
                if (layer.children) {
                    layer.children.splice(position, 0, layerToAdd);
                    options.layer = layer.children[position];
                } else {
                    // ignore position
                    layer.children = [];
                    layer.children.push($.extend(true, layerToAdd));
                    options.layer = layer.children[0];
                }
                return options;
            }
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    options = _addLayer(layer.children[i], layerToAdd, parentLayerToAdd, position, options);
                }
            }
            return options;
        }
    },
    'public function removeLayer': function(source, layerToRemove) {
        var rootLayer = source.configuration.children[0];
        if (layerToRemove.options.id.toString() === rootLayer.options.id.toString()) {
            source.configuration.children = [];
            return {
                layer: rootLayer
            };
        }
        var options = {
            layer: null,
            layerToRemove: null
        };//, listToRemove: {}, addToList: false }
        options = _removeLayer(rootLayer, layerToRemove, options);
        return {
            layer: options.layerToRemove
        };

        function _removeLayer(layer, layerToRemove, options) {
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    options = _removeLayer(layer.children[i], layerToRemove, options);
                    if (options.layer) {
                        if (options.layer.options.id.toString() === layerToRemove.options.id.toString()) {
                            var layerToRemArr = layer.children.splice(i, 1);
                            if (layerToRemArr[0]) {
                                options.layerToRemove = $.extend({}, layerToRemArr[0]);
                            }
                        }
                    }
                }
            }
            if (layer.options.id.toString() === layerToRemove.options.id.toString()) {
                options.layer = layer;
                options.layerToRemove = layer;
                return options;
            } else {
                options.layer = null;
                return options;
            }
        }
    },
    'public function findLayer': function(source, optionToFind) {
        var options = {
            level: 0,
            idx: 0,
            layer: null,
            parent: null
        };
        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, i, parents) {
            for (var key in optionToFind) {
                if (layer.options[key].toString() === optionToFind[key].toString()) {
                    options.idx = i;
                    options.parent = parents[0] || null;
                    options.level = parents.length;
                    options.layer = layer;
                    // abort iteration
                    return false;
                }
            }
        });
        return options;
    },
    'public function checkInfoLayers': function(source, scale, tochange, result) {
        if (!result)
            result = {
                infolayers: [
                ],
                changed: {
                    sourceIdx: {
                        id: source.id
                    },
                    children: {}
                }
            };


        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer) {
            if (typeof layer.options.treeOptions.info === 'undefined') {
                layer.options.treeOptions.info = false;
            }
            var layerChanged = tochange.options.children[layer.options.id];
            if (layerChanged && layer.options.name) {
                if (layerChanged.options.treeOptions.info !== layer.options.treeOptions.info) {
                    layer.options.treeOptions.info = layerChanged.options.treeOptions.info;
                    result.changed.children[layer.options.id] = layerChanged;
                }
            }
            if (layer.options.treeOptions.info && layer.state.visibility) {
                var layerName = layer.options.name;
                // layer names can be emptyish, most commonly on root layers
                // we will avoid appending an empty string to the list of queryable layers
                if (layerName && layerName.length) {
                    result.infolayers.push(layerName);
                }
                result.infolayers.push(layer.options.name);
            }
        });

        return result;
    },
    /**
     * Returns object's changes : { layers: [], infolayers: [], changed: changed };
     */
    'public function changeOptions': function(source, scale, toChangeOpts, result) {
        var optLength = 0;
        var self = this;
        if (toChangeOpts.options) {
            for (attr in toChangeOpts.options)
                optLength++;
        }
        if (optLength > 0) {/* change source options -> set */
            if (toChangeOpts.options.configuration) {
                var configuration = toChangeOpts.options.configuration;
                if (configuration.options) {
                    var rootId = source.configuration.children[0].options.id;
                    if (!toChangeOpts.options.children)
                        toChangeOpts.options['children'] = {};
                    if (!toChangeOpts.options.children[rootId])
                        toChangeOpts.options.children[rootId] = {
                            options: {}
                        };
                    if (typeof configuration.options.visibility !== 'undefined')
                        $.extend(true, toChangeOpts.options.children[rootId], {
                            options: {
                                treeOptions: {
                                    selected: configuration.options.visibility
                                }
                            }
                        });
                    if (typeof configuration.options.info !== 'undefined')
                        $.extend(true, toChangeOpts.options.children[rootId], {
                            options: {
                                treeOptions: {
                                    info: configuration.options.info
                                }
                            }
                        });
                    if (typeof configuration.options.toggle !== 'undefined')
                        $.extend(true, toChangeOpts.options.children[rootId], {
                            options: {
                                treeOptions: {
                                    toggle: configuration.options.toggle
                                }
                            }
                        });
                }
            }
        }
        if (!result)
            result = {
                layers: [
                ],
                infolayers: [
                ],
                styles: [
                ],
                changed: {
                    sourceIdx: {
                        id: source.id
                    },
                    children: {}
                }
            };
        var rootLayer = source.configuration.children[0];
        _changeOptions(rootLayer, scale, {
            state: {
                visibility: true
            }
        },
        toChangeOpts,
            result);
        return result;
        function _copyState(layer) {
            return {
                outOfScale: layer.state.outOfScale,
                outOfBounds: layer.state.outOfBounds,
                visibility: layer.state.visibility
            };
        }
        function _changeOptions(layer, scale, parentState, toChangeOpts, result) {
            var layerChanges,
                elchanged = false;
            layerChanges = toChangeOpts.options.children[layer.options.id];
            if (layerChanges) {
                layerChanges.state = _copyState(layer);
                var treeChanges = layerChanges.options.treeOptions;
                var treeOptions = layer.options.treeOptions;
                if (typeof treeChanges !== 'undefined') {
                    var optionsTasks = [['selected', true], ['info', true], ['toggle', false]];
                    for (var oti = 0; oti < optionsTasks.length; ++oti) {
                        var optionName = optionsTasks[oti][0];
                        var checkAllow = optionsTasks[oti][1];
                        if (typeof treeChanges[optionName] !== undefined && (!checkAllow || treeOptions.allow[optionName])) {
                            if (treeOptions[optionName] === treeChanges[optionName]) {
                                delete(treeChanges[optionName]);
                            } else {
                                treeOptions[optionName] = treeChanges[optionName];
                                elchanged = true;
                            }
                        }
                    }
                }
            } else {
                layerChanges = {state: _copyState(layer)};
            }
            layer.state.outOfScale = !Mapbender.Util.isInScale(scale, layer.options.minScale,
                layer.options.maxScale);
            /* @TODO outOfBounds for layers ?  */
            var newVisibilityState = parentState.state.visibility
                    && layer.options.treeOptions.selected
                    && !layer.state.outOfScale
                    && !layer.state.outOfBounds;
            if (layer.children) {
                // Store preliminary visibility for the recursive check, where current layer state will be "parentState"
                layer.state.visibility = !!newVisibilityState;
                var atLeastOneChildVisible = false;
                for (var j = 0; j < layer.children.length; j++) {
                    var child = _changeOptions(layer.children[j], scale, layer, toChangeOpts, result);
                    if (child.state.visibility) {
                        atLeastOneChildVisible = true;
                    }
                }
                // NOTE: Children can only be visible if this layer was already set visible before we called the
                //       recursive check. Thus if you think this should be a &&, you're not wrong, but the result is
                //       the same anyway.
                layer.state.visibility = atLeastOneChildVisible;
            } else {
                layer.state.visibility = !!(newVisibilityState && layer.options[self.layerNameIdent].length);
                if (layer.state.visibility) {
                    result.layers.push(layer.options[self.layerNameIdent]);
                    result.styles.push(layer.options.style ? layer.options.style : '');
                    if (layer.options.treeOptions.info === true) {
                        result.infolayers.push(layer.options[self.layerNameIdent]);
                    }
                }
            }
            var stateNames = ['outOfScale', 'outOfBounds', 'visibility'];
            for (var sni = 0; sni < stateNames.length; ++ sni) {
                var stateName = stateNames[sni];
                if (layerChanges.state[stateName] !== layer.state[stateName]) {
                    layerChanges.state[stateName] = layer.state[stateName];
                    elchanged = true;
                } else {
                    delete(layerChanges.state[stateName]);
                }
            }
            if (elchanged) {
                layerChanges.state = layer.state;
                result.changed.children[layer.options.id] = layerChanges;
            }
            var customLayerOrder = Mapbender.Geo.layerOrderMap["" + source.id];
            if (customLayerOrder && customLayerOrder.length && result.layers && result.layers.length) {
                result.layers = _.filter(customLayerOrder, function(layerName) {
                    return result.layers.indexOf(layerName) !== -1;
                });
            }

            return layer;
        }
    },
    /**
     * @param {object} source wms source
     * @param {object} changeOptions options in form of:
     * {layers:{'LAYERNAME': {options:{treeOptions:{selected: bool,info: bool}}}}}
     * @param {boolean | null} defaultSelected 
     * @param {boolean} mergeSelected
     * @returns {object} changes
     */
    'public function createOptionsLayerState': function(source, changeOptions, defaultSelected, mergeSelected) {
        var self = this;
        function setSelected(layer, parent, toChange) {
            if (layer.children) {
                var childSelected = false;
                for (var i = 0; i < layer.children.length; i++) {
                    var child = layer.children[i];
                    setSelected(child, layer, toChange);
                    if ((!toChange[child.options.id] && child.options.treeOptions.selected)
                        || (toChange[child.options.id] && toChange[child.options.id].options.treeOptions.selected)) {
                        childSelected = true;
                    }
                }
                var layerOpts = changeOptions.layers[layer.options[[self.layerNameIdent]]]
                    || changeOptions.layers[layer.options.id];
                if (layerOpts && layerOpts.options.treeOptions.selected !== layer.options.treeOptions.selected) {// change it
                    toChange[layer.options.id] = {
                        options: {
                            treeOptions: {
                                selected: layerOpts.options.treeOptions.selected
                            }
                        }
                    };
                    if (layer.options.treeOptions.allow.info) {
                        toChange[layer.options.id].options.treeOptions['info'] =
                            layerOpts.options.treeOptions.selected;
                    }
                }
                if (childSelected && !layerOpts && !layer.options.treeOptions.selected) {
                    toChange[layer.options.id] = {
                        options: {
                            treeOptions: {
                                selected: true
                            }
                        }
                    };
                    if (layer.options.treeOptions.allow.info) {
                        toChange[layer.options.id].options.treeOptions['info'] = true;
                    }
                }
            } else {
                var layerOpts = changeOptions.layers[layer.options[[self.layerNameIdent]]]
                    || changeOptions.layers[layer.options.id];
                if(!layerOpts && defaultSelected === null) {
                    return;
                }
                var sel = layerOpts ? layerOpts.options.treeOptions.selected : defaultSelected;
                if (mergeSelected) {
                    sel = sel || layer.options.treeOptions.selected;
                }
                if (sel !== layer.options.treeOptions.selected) {
                    toChange[layer.options.id] = {
                        options: {
                            treeOptions: {
                                selected: sel
                            }
                        }
                    };
                }
                if (sel && layer.options.treeOptions.allow.info) {
                    if (toChange[layer.options.id]) {
                        toChange[layer.options.id].options.treeOptions['info'] = true;
                    } else {
                        toChange[layer.options.id] = {
                            options: {
                                treeOptions: {
                                    info: true
                                }
                            }
                        };
                    }
                }
            }
        };
        var changed = {
            sourceIdx: {
                id: source.id
            },
            options: {
                children: {},
                type: 'selected'
            }
        };
        setSelected(source.configuration.children[0], null, changed.options.children);
        return {
            change: changed
        };
    },
    /**
     * Gets a layer extent, or the source extent as a fallback
     *
     * @param {object} source config object
     * @param {string} layerId
     * @returns {Object.<string,Array.<float>>} mapping of EPSG code to BBOX coordinate pair
     */
    'public function getLayerExtents': function(source, layerId) {
        var extents = null;
        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layerDef) {
            if (layerDef.options.id === layerId) {
                extents = layerDef.options.bbox || null;
                // abort iteration
                return false;
            }
        });

        if (extents && Object.keys(extents).length) {
            return extents;
        }
        if (source.configuration.options.bbox && Object.keys(source.configuration.options.bbox).length) {
            return source.configuration.options.bbox;
        }
        return null;
    },
    'public function setLayerOrder': function(source, layerIdOrder) {
        var newLayerNameOrder = $.map(layerIdOrder, function(layerId) {
            var layerObj = this.findLayer(source, {id: layerId});
            return layerObj.layer.options.name;
        }.bind(this));
        Mapbender.Geo.layerOrderMap["" + source.id] = newLayerNameOrder;
    }
});

// old declaration
Mapbender['source'] = {};

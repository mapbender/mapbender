/**
 * This prevents OpenLayers making GetMap requests when the LAYER parameter is empty.
 *
 * This is done by adding a test to the in-range calculation which tests the length of
 * the layers parameter.
 *
 * @return {Boolean} Whether the layer is in range or not
 */
OpenLayers.Layer.WMS.prototype.calculateInRange = function(){
    if(!this.params.LAYERS || 0 === this.params.LAYERS.length) {
        // explicitely hide DOM element for this layer
        this.display(false);
        return false;
    }
    ;
    return OpenLayers.Layer.prototype.calculateInRange.apply(this, arguments);
}

var Mapbender = Mapbender || {};
$.extend(true, Mapbender, {
    source: {
        'wms': {
            defaultMqLayer: {
                type: 'wms',
                noMagic: true,
                transitionEffect: 'resize'
            },
            create: function(layerDef){
                var self = this;
                var rootLayer = layerDef.configuration.children[0];

                function _setProperties(layer, parent, id, num, proxy){
                    /* set unic id for a layer */
                    layer.options.origId = layer.options.id;
                    layer.options.id = parent ? parent.options.id + "_" + num : id + "_" + num;
                    if(proxy && layer.options.legend) {
                        if(layer.options.legend.graphic) {
                            layer.options.legend.graphic = self._addProxy(layer.options.legend.graphic);
                        } else if(layer.options.legend.url) {
                            layer.options.legend.url = self._addProxy(layer.options.legend.url);
                        }
                    }
                    if(layer.children) {
                        for(var i = 0; i < layer.children.length; i++) {
                            _setProperties(layer.children[i], layer, id, i, proxy);
                        }
                    }
                }
                _setProperties(rootLayer, null, layerDef.id, 0, layerDef.configuration.options.proxy);

                var finalUrl = layerDef.configuration.options.url;

                if(layerDef.configuration.options.proxy === true) {
                    finalUrl = this._addProxy(finalUrl);
                }

                var mqLayerDef = {
                    label: layerDef.title,
                    url: finalUrl,
                    transparent: layerDef.configuration.options.transparent,
                    format: layerDef.configuration.options.format,
                    isBaseLayer: layerDef.configuration.options.baselayer,
                    opacity: layerDef.configuration.options.opacity,
                    visibility: layerDef.configuration.options.visible,
                    singleTile: !layerDef.configuration.options.tiled,
                    attribution: layerDef.configuration.options.attribution, // attribution add !!!
                    minScale: rootLayer.minScale,
                    maxScale: rootLayer.maxScale,
                    transitionEffect: 'resize',
                    buffer: layerDef.configuration.options.tiled ? layerDef.configuration.options.buffer : null,
                    ratio: !layerDef.configuration.options.tiled ? layerDef.configuration.options.buffer : null
                };
                $.extend(mqLayerDef, Mapbender.source.wms.defaultMqLayer);
                return mqLayerDef;
            },
            _addProxy: function(url){
                return OpenLayers.ProxyHost + encodeURIComponent(url);
            },
            _removeProxy: function(url){
                if(url.indexOf(OpenLayers.ProxyHost) === 0) {
                    return decodeURIComponent(url.substring(OpenLayers.ProxyHost.length));
                }
                return url;
            },
            removeSignature: function(url){
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
            featureInfoUrl: function(mqLayer, x, y, callback){
                if(!mqLayer.visible() || mqLayer.olLayer.queryLayers.length === 0) {
                    return false;
                }
                var param_tmp = {
                    SERVICE: 'WMS',
                    REQUEST: 'GetFeatureInfo',
                    VERSION: mqLayer.olLayer.params.VERSION,
                    EXCEPTIONS: "application/vnd.ogc.se_xml",
                    FORMAT: mqLayer.olLayer.params.FORMAT,
                    INFO_FORMAT: mqLayer.source.configuration.options.info_format || "text/plain",
                    FEATURE_COUNT: mqLayer.source.configuration.options.feature_count || 100,
                    SRS: mqLayer.olLayer.params.SRS,
                    BBOX: mqLayer.map.center().box.join(','),
                    WIDTH: $(mqLayer.map.element).width(),
                    HEIGHT: $(mqLayer.map.element).height(),
                    X: x,
                    Y: y,
                    LAYERS: mqLayer.olLayer.queryLayers.join(','),
                    QUERY_LAYERS: mqLayer.olLayer.queryLayers.join(',')
                };
                if(typeof (mqLayer.source.configuration.options.info_format) !== 'undefined') {
                    param_tmp["INFO_FORMAT"] = mqLayer.source.configuration.options.info_format;
                }
                var params = $.param(param_tmp);
                // this clever shit was taken from $.ajax
                var requestUrl = this._removeProxy(mqLayer.olLayer.url);
                requestUrl += (/\?/.test(mqLayer.options.url) ? '&' : '?') + params;
                return requestUrl;
            },
            
            createSourceDefinitions: function(xml, options){
                if(!options.global.defFormat) {
                    options.global.defFormat = "image/png";
                }
                if(!options.global.defInfoformat) {
                    options.global.defInfoformat = "text/html";
                }
                var parser = new OpenLayers.Format.WMSCapabilities(),
                        capabilities = parser.read(xml);

                if(typeof (capabilities.capability) !== 'undefined') {
                    var rootlayer = capabilities.capability.nestedLayers[0];
                    var bboxOb = {}, bboxSrs = null, bboxBounds = null;
                    for(bbox in rootlayer.bbox) {
                        if(options.model.getProj(bbox) !== null) {
                            bboxOb[bbox] = rootlayer.bbox[bbox].bbox;
                            bboxSrs = bbox;
                            bboxBounds = OpenLayers.Bounds.fromArray(bboxOb[bbox]);
                        }
                    }
                    for(srs in rootlayer.srs) {
                        if(rootlayer.srs[srs] === true && typeof bboxOb[srs] === 'undefined' && options.model.getProj(
                                srs) !== null && bboxBounds !== null) {
                            var oldProj = options.model.getProj(bboxSrs);
                            bboxOb[srs] = bboxBounds.transform(oldProj, options.model.getProj(srs)).toArray();
                        }
                    }
                    var format;
                    var formats = capabilities.capability.request.getmap.formats;
                    for(var i = 0; i < formats.length; i++) {
                        if(formats[i].toLowerCase() === options.global.defFormat.toLowerCase()) {
                            format = formats[i];
                            break;
                        }
                    }
                    if(!format)
                        format = formats[0];

                    var infoformat;
                    var gfi = capabilities.capability.request.getfeatureinfo;
                    if(gfi && gfi.formats && gfi.formats.length > 0) {
                        for(var i = 0; i < gfi.formats.length; i++) {
                            if(gfi.formats[i].toLowerCase() === options.global.defInfoformat.toLowerCase()) {
                                infoformat = gfi.formats[i];
                                break;
                            }
                        }
                        if(!infoformat)
                            infoformat = gfi.formats[0];
                    } else {
                        infoformat = options.global.defInfoformat;
                    }
                    //@TODO srs list, srs by layer -> parent layer srs + layer srs
                    var getmap = new Mapbender.Util.Url(capabilities.capability.request.getmap.get.href);
                    getmap.username = options.gcurl.username;
                    getmap.password = options.gcurl.password;
                    var def = {
                        type: 'wms',
                        title: capabilities.service.title,
                        configuration: {
                            isBaseSource: false,
                            options: {
                                bbox: bboxOb,
                                format: format,
                                info_format: infoformat,
                                opacity: 1,
                                proxy: false,
                                tiled: false,
                                transparent: true,
                                url: getmap.asString(),
                                visible: true
                            }
                        }
                    };

                    function readCapabilities(layer, parent, options){
                        // @ TODO getLegendGraphic ?
                        var legend = null, minScale_ = null, maxScale_ = null;
                        if(layer.styles.length !== 0 && layer.styles[0].legend) {
                            legend = {};
                            legend.url = layer.styles[0].legend.href
                            legend.width = layer.styles[0].legend.width;
                            legend.height = layer.styles[0].legend.height;
                        }
                        minScale_ = layer.minScale ? Math.round(layer.minScale) : parent && parent.options.minScale
                                ? parent.options.minScale : null;
                        maxScale_ = layer.maxScale ? Math.round(layer.maxScale) : parent && parent.options.maxScale
                                ? parent.options.maxScale : null;
                        var def = {
                            options: {
                                legend: legend,
                                maxScale: minScale_, // inheritance replace
                                minScale: maxScale_, // inheritance replace
                                name: layer.name, // inheritance
                                queryable: layer.queryable,
                                style: layer.styles.length === 0 ? null : layer.styles[0].name, // inheritance add
                                title: layer.title,
                                treeOptions: {
                                    allow: {
                                        info: layer.queryable ? true : false,
                                        reorder: true,
                                        selected: true,
                                        toggle: layer.nestedLayers.length === 0 ? null : true
                                    },
                                    info: layer.queryable ? true : null,
                                    selected: true,
                                    toggle: layer.nestedLayers.length === 0 ? null : false
                                }
                            },
                            state: {
                                info: null,
                                outOfBounds: null,
                                outOfScale: false,
                                visibility: true
                            }
                        };
                        $.extend(true, def.options, options.global.options);
                        if(options.layers[def.options.name])
                            $.extend(true, def.options, options.layers[def.options.name].options);
                        if(layer.nestedLayers.length > 0) {
                            def.children = [];
                            for(var i = 0; i < layer.nestedLayers.length; i++) {
                                var child = readCapabilities(layer.nestedLayers[i], def, options);
                                if(child.options.treeOptions.selected)
                                    def.options.treeOptions.selected = child.options.treeOptions.selected;
                                def.children.push(child);
                            }
                        }
                        return def;
                    }
                    function getSplitted(service, rootLayer, layer, result, num){

                        if(num !== 0) {
                            var service_new = $.extend(true, {}, service);
                            var root_new = $.extend(true, {}, rootLayer);
                            var layer_new = $.extend(true, {}, layer);
                            root_new.options.title = layer_new.options.title + " (" + root_new.options.title + ")";
                            if(layer_new.children)
                                delete(layer_new.children);
                            root_new.children = [layer_new];
                            service_new.configuration.children = [root_new];
                            return service_new;
                        }
                        if(layer.children) {
                            for(var i = 0; i < layer.children.length; i++) {
                                num++;
                                result.push(getSplitted(service, rootLayer, layer.children[i], result, num));
                            }
                        }
                    }
                    var layers = readCapabilities(capabilities.capability.nestedLayers[0], null, options);
                    if(options.global.splitLayers) {
                        var result = [];
                        getSplitted(def, layers, layers, result, 0);
                        return result;
                    } else {
                        def.configuration.children = [layers];
                        return [def];
                    }
                } else {
                    return null;
                }
            },
            getPrintConfig: function(layer, bounds, isProxy){
                var printConfig = {
                    type: 'wms',
                    url: isProxy ? this._removeProxy(layer.getURL(bounds)) : layer.getURL(bounds)
                };
                return printConfig;
            },
            onLoadError: function(imgEl, sourceId, projection, callback){
                var self = this;
                var loadError = {sourceId: sourceId, details: ''};
                $.ajax({
                    type: "GET",
                    async: false,
                    url: Mapbender.configuration.application.urls.proxy + "?url=" + encodeURIComponent(
                            self._removeProxy(imgEl.attr('src'))),
                    success: function(message, text, response){
                        if(typeof (response.responseText) === "string") {
                            var details = Mapbender.trans("mb.wms.source.image_error.datails");
                            var layerTree;
                            try {
                                layerTree = new OpenLayers.Format.WMSCapabilities().read(response.responseText);
                            } catch(e) {
                                layerTree = null;
                                details += ".\n" + Mapbender.trans("mb.wms.source.image_error.exception", {
                                    'exception': e.toString()});
                            }
                            if(layerTree && layerTree.error) {
                                if(layerTree.error.exceptionReport && layerTree.error.exceptionReport.exceptions) {
                                    var excs = layerTree.error.exceptionReport.exceptions;
                                    details += ":";
                                    for(var m = 0; m < excs.length; m++) {
                                        var exc = excs[m].code;
                                        details += "\n" + exc;
                                        if(excs[m].code == "InvalidSRS") {
                                            details += " (" + projection.projCode + ")";
                                        }
                                    }
                                }
                            }
                        }
                        loadError.details = details;
                        callback(loadError);
                    },
                    error: function(err){
                        var details = Mapbender.trans("mb.wms.source.image_error.datails");
                        if(err.status == 200) {
                            var capabilities;
                            try {
                                capabilities = new OpenLayers.Format.WMSCapabilities().read(err.responseText);
                            } catch(e) {
                                capabilities = null;
                                details += ".\n" + Mapbender.trans("mb.wms.source.image_error.exception", {
                                    'exception': e.toString()});
                            }
                            if(capabilities && capabilities.error) {
                                if(capabilities.error.exceptionReport && capabilities.error.exceptionReport.exceptions) {
                                    var excs = capabilities.error.exceptionReport.exceptions;
                                    details += ":";
                                    for(var m = 0; m < excs.length; m++) {
                                        var exc = excs[m].code;
                                        details += "\n" + exc;
                                        if(excs[m].code == "InvalidSRS") {
                                            details += " (" + projection.projCode + ")";
                                        }
                                        if(exc != excs[m].code) {

                                        } else if(excs[m].text) {
                                            details += "\n" + excs[m].text;
                                        }
                                    }
                                }
                            }
                        } else {
                            details += ".\n" + Mapbender.trans(
                                    "mb.wms.source.image_error.statuscode") + ": " + err.status + " - " + err.statusText;
                        }
                        loadError.details = details;
                        callback(loadError);
                    }
                });
            },
            hasLayers: function(source, withoutGrouped){
                var options = this.layerCount(source);
                if(withoutGrouped) {
                    return options.simpleCount > 0;
                } else { // without root layer
                    return options.simpleCount + options.groupedCount - 1 > 0;
                }
            },
            layerCount: function(source){
                if(source.configuration.children.length === 0) {
                    return {simpleCount: 0, grouppedCount: 0};
                }
                var options = {simpleCount: 0, groupedCount: 0}
                return _layerCount(source.configuration.children[0], options);
                function _layerCount(layer, options){
                    if(layer.children) {
                        options.grouppedCount++;
                        for(var i = 0; i < layer.children.length; i++) {
                            options = _layerCount(layer.children[i], options);
                        }
                    } else {
                        options.simpleCount++;
                    }
                    return options;
                }
            },
            getLayersList: function(source, offsetLayer, includeOffset){
                var rootLayer, _source;
                _source = $.extend(true, {}, source);//.configuration.children[0];
                rootLayer = _source.configuration.children[0];
                var options = {layers: [], found: false, cut_with: includeOffset};
                if(rootLayer.options.id.toString() === offsetLayer.options.id.toString()) {
                    options.found = true;
                }
                options = _findLayers(rootLayer, offsetLayer, options);
                return {source: _source, layers: options.layers};

                function _findLayers(layer, offsetLayer, options){
                    if(layer.children) {
                        var i = 0;
                        for(; i < layer.children.length; i++) {
                            if(layer.children[i].options.id.toString() === offsetLayer.options.id.toString()) {
                                options.found = true;
                                if(options.cut_with) {
                                    var lays = layer.children.splice(i, layer.children.length - i);
                                    options.layers = options.layers.concat(lays);
                                    break;
                                }
                            } else if(options.found) {
                                var lays = layer.children.splice(i, layer.children.length - i);
                                options.layers = options.layers.concat(lays);
                                break;
                            }
                            options = _findLayers(layer.children[i], offsetLayer, options);
                        }
                    }
                    return options;
                }
            },
            addLayer: function(source, layerToAdd, parentLayerToAdd, position){
                var rootLayer = source.configuration.children[0];
                var options = {layer: null};
                options = _addLayer(rootLayer, layerToAdd, parentLayerToAdd, position, options);
                return options.layer;

                function _addLayer(layer, layerToAdd, parentLayerToAdd, position, options){
                    if(layer.options.id.toString() === parentLayerToAdd.options.id.toString()) {
                        if(layer.children) {
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
                    if(layer.children) {
                        for(var i = 0; i < layer.children.length; i++) {
                            options = _addLayer(layer.children[i], layerToAdd, parentLayerToAdd, position, options);
                        }
                    }
                    return options;
                }
            },
            removeLayer: function(source, layerToRemove){
                var rootLayer = source.configuration.children[0];
                if(layerToRemove.options.id.toString() === rootLayer.options.id.toString()) {
                    source.configuration.children = [];
                    return {layer: rootLayer};
                }
                var options = {layer: null, layerToRemove: null};//, listToRemove: {}, addToList: false }
                options = _removeLayer(rootLayer, layerToRemove, options);
                return {layer: options.layerToRemove};

                function _removeLayer(layer, layerToRemove, options){
                    if(layer.children) {
                        for(var i = 0; i < layer.children.length; i++) {
                            options = _removeLayer(layer.children[i], layerToRemove, options);
                            if(options.layer) {
                                if(options.layer.options.id.toString() === layerToRemove.options.id.toString()) {
                                    var layerToRemArr = layer.children.splice(i, 1);
                                    if(layerToRemArr[0]) {
                                        options.layerToRemove = $.extend({}, layerToRemArr[0]);
                                    }
                                }
                            }
                        }
                    }
                    if(layer.options.id.toString() === layerToRemove.options.id.toString()) {
                        options.layer = layer;
                        options.layerToRemove = layer;
                        return options;
                    } else {
                        options.layer = null;
                        return options;
                    }
                }
            },
            findLayer: function(source, optionToFind){
                var rootLayer = source.configuration.children[0];
                var options = {level: 0, idx: 0, layer: null, parent: null};
                options = _findLayer(rootLayer, optionToFind, options, 0);
                return options;
                function _findLayer(layer, optionToFind, options, levelTmp){
                    if(layer.children) {
                        levelTmp++;
                        for(var i = 0; i < layer.children.length; i++) {
                            for(var key in optionToFind) {
                                if(layer.children[i].options[key].toString() === optionToFind[key].toString()) {
                                    options.idx = i;
                                    options.parent = layer;
                                    options.level = levelTmp;
                                    options.layer = layer.children[i];
                                    return options;
                                }
                            }
                            options = _findLayer(layer.children[i], optionToFind, options, levelTmp);
                        }
                        levelTmp--;
                    }
                    for(var key in optionToFind) {
                        if(layer.options[key].toString() === optionToFind[key].toString()) {
                            options.level = levelTmp;
                            options.layer = layer;
                            return options;
                        }
                    }
                    return options;
                }
            },
            checkInfoLayers: function(source, scale, tochange, result){
                if(!result)
                    result = {infolayers: [], changed: {sourceIdx: {id: source.id}, children: {}}};
                var rootLayer = source.configuration.children[0];
                _checkInfoLayers(rootLayer, scale, {state: {visibility: true}}, tochange, result);
                return result;

                function _checkInfoLayers(layer, scale, parent, tochange, result){
                    var layerChanged;
                    if(typeof layer.options.treeOptions.info === 'undefined') {
                        layer.options.treeOptions.info = false;
                    }
                    if(tochange.options.children[layer.options.id] && layer.options.name && layer.options.name.length > 0) {
                        layerChanged = tochange.options.children[layer.options.id];
                        if(layerChanged.options.treeOptions.info !== layer.options.treeOptions.info) {
                            layer.options.treeOptions.info = layerChanged.options.treeOptions.info;
                            result.changed.children[layer.options.id] = layerChanged;
                        }
                    }
                    if(layer.options.treeOptions.info === true && layer.state.visibility) {
                        result.infolayers.push(layer.options.name);
                    }
                    if(layer.children) {
                        for(var j = 0; j < layer.children.length; j++) {
                            _checkInfoLayers(layer.children[j], scale, layer, tochange, result);
                        }
                    }
                }
            },
            /**
             * Returns object's changes : { layers: [], infolayers: [], changed: changed };
             */
            changeOptions: function(source, scale, toChangeOpts, result){
                var optLength = 0;
                if(toChangeOpts.options) {
                    for(attr in toChangeOpts.options)
                        optLength++;
                }
                if(optLength > 0) {/* change source options -> set */
                    if(toChangeOpts.options.configuration) {
                        var configuration = toChangeOpts.options.configuration;
                        if(configuration.options) {
                            var rootId = source.configuration.children[0].options.id;
                            if(!toChangeOpts.options.children)
                                toChangeOpts.options['children'] = {};
                            if(!toChangeOpts.options.children[rootId])
                                toChangeOpts.options.children[rootId] = {options: {}};
                            if(typeof configuration.options.visibility !== 'undefined')
                                $.extend(true, toChangeOpts.options.children[rootId], {options: {treeOptions: {
                                            selected: configuration.options.visibility}}});
                            if(typeof configuration.options.info !== 'undefined')
                                $.extend(true, toChangeOpts.options.children[rootId], {options: {treeOptions: {
                                            info: configuration.options.info}}});
                            if(typeof configuration.options.toggle !== 'undefined')
                                $.extend(true, toChangeOpts.options.children[rootId], {options: {treeOptions: {
                                            toggle: configuration.options.toggle}}});
                        }
                    }
                }
                if(!result)
                    result = {layers: [], infolayers: [], changed: {sourceIdx: {id: source.id}, children: {}}};
                var rootLayer = source.configuration.children[0];
                _changeOptions(rootLayer, scale, {state: {visibility: true}}, toChangeOpts, result);
                return result;
                function _createState(layer){
                    return {
                        outOfScale: layer.state.outOfScale,
                        outOfBounds: layer.state.outOfBounds,
                        visibility: layer.state.visibility
                    };
                }
                function _changeOptions(layer, scale, parentState, toChangeOpts, result){
                    var layerChanged,
                            elchanged = false;
                    if(toChangeOpts.options.children[layer.options.id]) {
                        layerChanged = toChangeOpts.options.children[layer.options.id];
                        layerChanged.state = _createState(layer);
                        if(typeof layerChanged.options.treeOptions !== 'undefined') {
                            var treeOptions = layerChanged.options.treeOptions;
                            if(typeof treeOptions.selected !== 'undefined') {
                                if(layer.options.treeOptions.selected === treeOptions.selected)
                                    delete(treeOptions.selected);
                                else {
                                    layer.options.treeOptions.selected = treeOptions.selected;
                                    elchanged = true;
                                }
                            }
                            if(typeof treeOptions.info !== 'undefined') {
                                if(layer.options.treeOptions.info === treeOptions.info)
                                    delete(treeOptions.info);
                                else {
                                    layer.options.treeOptions.info = treeOptions.info;
                                    elchanged = true;
                                }
                            }
                            if(typeof treeOptions.toggle !== 'undefined') {
                                if(layer.options.treeOptions.toggle === treeOptions.toggle)
                                    delete(treeOptions.toggle);
                                else
                                    layer.options.treeOptions.toggle = treeOptions.toggle;
                            }
                        }
                    } else {
                        layerChanged = {state: _createState(layer)};
                    }
                    layer.state.outOfScale = !Mapbender.Util.isInScale(scale, layer.options.minScale,
                            layer.options.maxScale);
                    /* @TODO outOfBounds for layers ?  */
                    if(layer.children) {
                        if(parentState.state.visibility
                                && layer.options.treeOptions.selected
                                && !layer.state.outOfScale
                                && !layer.state.outOfBounds) {
                            layer.state.visibility = true;
                        } else {
                            layer.state.visibility = false;
                        }
                        var child_visible = false;
                        for(var j = 0; j < layer.children.length; j++) {
                            var child = _changeOptions(layer.children[j], scale, layer, toChangeOpts, result);
                            if(child.state.visibility) {
                                child_visible = true;
                            }
                        }
                        if(child_visible) {
                            layer.state.visibility = true;
                        } else {
                            layer.state.visibility = false;
                        }
                    } else {
                        if(parentState.state.visibility
                                && layer.options.treeOptions.selected
                                && !layer.state.outOfScale
                                && !layer.state.outOfBounds
                                && layer.options.name.length > 0) {
                            layer.state.visibility = true;
                            result.layers.push(layer.options.name);
                            if(layer.options.treeOptions.info === true) {
                                result.infolayers.push(layer.options.name);
                            }
                        } else {
                            layer.state.visibility = false;
                        }
                    }
                    if(layerChanged.state.outOfScale !== layer.state.outOfScale) {
                        layerChanged.state.outOfScale = layer.state.outOfScale;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.outOfScale);
                    }
                    if(layerChanged.state.outOfBounds !== layer.state.outOfBounds) {
                        layerChanged.state.outOfBounds = layer.state.outOfBounds;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.outOfBounds);
                    }
                    if(layerChanged.state.visibility !== layer.state.visibility) {
                        layerChanged.state.visibility = layer.state.visibility;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.visibility);
                    }
                    if(elchanged) {
                        layerChanged.state = layer.state;
                        result.changed.children[layer.options.id] = layerChanged;
                    }
                    return layer;
                }
            },
            /**
             * @param {object} source wms source
             * @param {object} changeOptions options in form of:
             * {layers:{'LAYERNAME': {options:{treeOptions:{selected: bool,info: bool}}}}}
             * @param {boolean} mergeSelected
             * @returns {object} changes
             */
            createOptionsLayerState: function(source, changeOptions, defaultSelected, mergeSelected){
                function setSelected(layer, parent, toChange){
                    if(layer.children) {
                        var childSelected = false;
                        for(var i = 0; i < layer.children.length; i++) {
                            var child = layer.children[i];
                            setSelected(child, layer, toChange);
                            if((!toChange[child.options.id] && child.options.treeOptions.selected)
                                    || (toChange[child.options.id] && toChange[child.options.id].options.treeOptions.selected)) {
                                childSelected = true;
                            }
                        }
                        var layerOpts = changeOptions.layers[layer.options.name] || changeOptions.layers[layer.options.id];
                        if(layerOpts && layerOpts.options.treeOptions.selected !== layer.options.treeOptions.selected) {// change it
                            toChange[layer.options.id] = {options: {treeOptions: {
                                        selected: layerOpts.options.treeOptions.selected}}};
                            if(layer.options.treeOptions.allow.info) {
                                toChange[layer.options.id].options.treeOptions['info'] = layerOpts.options.treeOptions.selected;
                            }
                        }
                        if(childSelected && !layerOpts && !layer.options.treeOptions.selected) {
                            toChange[layer.options.id] = {options: {treeOptions: {selected: true}}};
                            if(layer.options.treeOptions.allow.info) {
                                toChange[layer.options.id].options.treeOptions['info'] = true;
                            }
                        }
                    } else {
                        var layerOpts = changeOptions.layers[layer.options.name] || changeOptions.layers[layer.options.id];
                        var sel = layerOpts ? layerOpts.options.treeOptions.selected : defaultSelected;
                        if(mergeSelected) {
                            sel = sel || layer.options.treeOptions.selected;
                        }
                        if(sel !== layer.options.treeOptions.selected) {
                            toChange[layer.options.id] = {options: {treeOptions: {selected: sel}}};
                        }
                        if(sel && layer.options.treeOptions.allow.info) {
                            if(toChange[layer.options.id]) {
                                toChange[layer.options.id].options.treeOptions['info'] = true;
                            } else {
                                toChange[layer.options.id] = {options: {treeOptions: {info: true}}};
                            }
                        }
                    }
                }
                ;
                var changed = {sourceIdx: {id: source.id}, options: {children: {}, type: 'selected'}};
                setSelected(source.configuration.children[0], null, changed.options.children);
                return {change: changed};
            },
            /**
             * Gets a layer extent or an extent from layer parents
             * @param {object} source wms source
             * @param {object} changeOptions options in form of:
             * @returns {object} extent of form {projectionCode: OpenLayers.Bounds.toArray, ...}
             */
            getLayerExtents: function(source, layerId){
                function _layerExtent(layer, toFindLayerId){
                    if(layer.options.id === toFindLayerId) {
                        return layer.options.bbox ? layer.options.bbox : null;
                    }
                    if(layer.children) {
                        for(var j = 0; j < layer.children.length; j++) {
                            var temp = _layerExtent(layer.children[j], toFindLayerId);
                            if(temp) {
                                return temp;
                            }
                        }
                    }
                    return null;
                }
                var extent = _layerExtent(source.configuration.children[0], layerId);
                return extent ? extent : source.configuration.options.bbox;
            }
        }
    }
});

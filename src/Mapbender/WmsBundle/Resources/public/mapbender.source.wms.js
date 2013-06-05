var Mapbender = Mapbender || {};
$.extend(true, Mapbender, {
    source: {
        'wms': {
            create: function(layerDef) {
                var layers = [];
                var queryLayers = [];
                var layersDefs = [];
                layerDef.origId =  layerDef.id;
                var rootLayer = layerDef.configuration.children[0];
                this._readLayerDef(layersDefs, layers, queryLayers, rootLayer, true);
                layersDefs.reverse();
                var finalUrl = layerDef.configuration.options.url;

                if(layerDef.configuration.options.proxy === true) {
                    finalUrl = this._addProxy(finalUrl);
                }

                var mqLayerDef = {
                    type:        'wms',
                    label:       layerDef.title,
                    url:         finalUrl,
                    noMagic: true,

                    transparent: layerDef.configuration.options.transparent,
                    format:      layerDef.configuration.options.format,

                    isBaseLayer: layerDef.configuration.options.baselayer,
                    opacity:     layerDef.configuration.options.opacity,
                    visibility:  layerDef.configuration.options.visible &&
                    (layers.length > 0),
                    singleTile:  !layerDef.configuration.options.tiled,
                    attribution: layerDef.configuration.options.attribution, // attribution add !!!

                    minScale:    rootLayer.minScale,
                    maxScale:    rootLayer.maxScale,
                    transitionEffect: 'resize'
                };
                return mqLayerDef;
            },
        
            _readLayerDef: function(layersDefs, layers, queryLayers, layer, isroot){
                //            $.each(layerDef.configuration.layers, function(idx, layer) {
                var layerDef = $.extend({},
                {
                    visible: true, 
                    queryable: false
                }, layer.options );
                delete(layerDef.treeOptions);
                if(!isroot && layerDef.visible && typeof layerDef.name === 'string' && layerDef.name.length > 0) {
                    layers.push(layerDef.name);
                }
                if(!isroot && layerDef.queryable) {
                    queryLayers.push(layerDef.name);
                }
                if(!isroot){
                    layersDefs.push(layerDef);
                }
                if(layer.children){
                    for(var i = 0; i < layer.children.length; i++){
                        this._readLayerDef(layersDefs, layers, queryLayers, layer.children[i], false);
                    }
                }
            //            });
            },
        
            _addProxy: function(url) {
                return OpenLayers.ProxyHost + url;
            },
        
            _removeProxy: function(url) {
                if(url.indexOf(OpenLayers.ProxyHost) === 0) {
                    return url.substring(OpenLayers.ProxyHost.length);
                }
                return url;
            },

            featureInfo: function(layer, x, y, callback) {
                if(layer.olLayer.queryLayers.length === 0) {
                    return;
                }
                var param_tmp = {
                    SERVICE: 'WMS',
                    REQUEST: 'GetFeatureInfo',
                    VERSION: layer.olLayer.params.VERSION,
                    EXCEPTIONS: "application/vnd.ogc.se_xml",
                    FORMAT: layer.olLayer.params.FORMAT,
                    INFO_FORMAT: layer.source.configuration.options.info_format || "text/plain",
                    SRS: layer.olLayer.params.SRS,
                    BBOX: layer.map.center().box.join(','),
                    WIDTH: $(layer.map.element).width(),
                    HEIGHT: $(layer.map.element).height(),
                    X: x,
                    Y: y,
                    LAYERS: layer.olLayer.queryLayers.join(','),
                    QUERY_LAYERS: layer.olLayer.queryLayers.join(',')
                };
                var contentType_ = "";
                if(typeof(layer.source.configuration.options.info_format)
                    !== 'undefined'){
                    param_tmp["INFO_FORMAT"] =
                    layer.source.configuration.options.info_format;
                //                contentType_ +=
                //                    layer.options.configuration.configuration.info_format;
                }
                if(typeof(layer.source.configuration.options.feature_count)
                    !== 'undefined'){
                    param_tmp["FEATURE_COUNT"] =
                    layer.source.configuration.options.feature_count;
                }
                if(typeof(layer.source.configuration.options.info_charset)
                    !== 'undefined'){
                    contentType_ += contentType_.length > 0 ? ";" : "" +
                    layer.source.configuration.options.info_charset;
                }
                var params = $.param(param_tmp);


                // this clever shit was taken from $.ajax
                var requestUrl = this._removeProxy(layer.options.url);
            
                requestUrl += (/\?/.test(layer.options.url) ? '&' : '?') + params;
            
                $.ajax({
                    url: Mapbender.configuration.application.urls.proxy,
                    contentType: contentType_,
                    data: {
                        url: encodeURIComponent(requestUrl)
                    },
                    success: function(data) {
                        callback({
                            layerId: layer.id,
                            response: data
                        });
                    },
                    error: function(error) {
                        callback({
                            layerId: layer.id,
                            response: 'ERROR'
                        });
                    }
                });
            },

            loadFromUrl: function(url) {
                var dlg = $('<div></div>').attr('id', 'loadfromurl-wms'),
                spinner = $('<img />')
                .attr('src', Mapbender.configuration.assetPath + 'bundles/mapbenderwms/images/spinner.gif')
                .appendTo(dlg);
                dlg.appendTo($('body'));

                $('<script></type')
                .attr('type', 'text/javascript')
                .attr('src', Mapbender.configuration.assetPath + 'bundles/mapbenderwms/mapbender.source.wms.loadfromurl.js')
                .appendTo($('body'));
            },

            layersFromCapabilities: function(xml, id, splitLayers, model, defFormat, defInfoformat) {
                if(!defFormat){
                    defFormat = "image/png";
                }
                if(!defInfoformat){
                    defInfoformat = "text/html";
                }
                var parser = new OpenLayers.Format.WMSCapabilities(),
                capabilities = parser.read(xml);

                if(typeof(capabilities.capability) !== 'undefined') {
                    var rootlayer = capabilities.capability.nestedLayers[0];
                    var bboxOb = {}, bboxSrs = null, bboxBounds = null;
                    for(bbox in rootlayer.bbox){
                        if(model.getProj(bbox) !== null){
                            bboxOb[bbox] = rootlayer.bbox[bbox].bbox;
                            bboxSrs = bbox;
                            bboxBounds = OpenLayers.Bounds.fromArray(bboxOb[bbox]);
                        }
                    }
                    for(srs in rootlayer.srs){
                        if(rootlayer.srs[srs] === true && typeof bboxOb[srs] === 'undefined' && model.getProj(srs) !== null && bboxBounds !== null){
                            var oldProj = model.getProj(bboxSrs);
                            bboxOb[srs] = bboxBounds.transform(oldProj, model.getProj(srs)).toArray();
                        }
                    }
                    var format;
                    var formats = capabilities.capability.request.getmap.formats;
                    for(var i = 0; i < formats.length; i++){
                        if(formats[i].toLowerCase().indexOf(defFormat)!== -1)
                            format = formats[i];
                    }
                    if(!format)
                        format = formats[0];
                    var infoformat;
                    var infoformats = capabilities.capability.request.getfeatureinfo.formats;
                    for(var i = 0; i < infoformats.length; i++){
                        if(infoformats[i].toLowerCase().indexOf(defInfoformat)!== -1)
                            infoformat = infoformats[i];
                    }
                    if(!infoformat)
                        infoformat = infoformats[0];
                    //@TODO srs list, srs by layer -> parent layer srs + layer srs
                    var def = {
                        type: 'wms',
                        id: id,
                        origId: id,
                        title: capabilities.service.title,
                        configuration: {
                            isBaseSource: false,
                            options: {
                                baselayer: false,
//                                bbox: bboxOb,
                                srslist: bboxOb,
                                format: format,
                                info_format: infoformat,
                                opacity: 1,
                                proxy: false,
                                tiled: false,
                                transparent: true,
                                url: capabilities.capability.request.getmap.get.href,
                                visible: true
                            },
                            children: [],
                            layers: []
                        }
                    };
                    
                    function readCapabilities(layer, parent, id, num){
                        // @ TODO getLegendGraphic ?
                        var legend = {};
                        if(layer.styles.length !== 0){
                            legend.url = layer.styles[0].legend.href
                            legend.width = layer.styles[0].legend.width;
                            legend.height = layer.styles[0].legend.height;
                        }
                        var def = {
                            options: {
                                id: id+"_"+num,
                                legend: legend, // inheritance from style
                                bbox: null, // inheritance replace
                                srslist: null,
                                maxScale: layer.minScale ? layer.minScale : parent && parent.options.maxScale ? parent.options.maxScale : null, // inheritance replace
                                minScale: layer.maxScale ? layer.maxScale : parent && parent.options.minScale ? parent.options.minScale : null, // inheritance replace
                                name: layer.name, // inheritance
                                queryable: layer.queryable,
                                style: layer.styles.length === 0 ? null : layer.styles[0].name, // inheritance add
                                title: layer.title,
                                treeOptions: {
                                    allow: {
                                        info: layer.queryable ? true: false,
                                        reorder: true,
                                        selected: true,
                                        toggle: true
                                    },
                                    info: layer.queryable ? true: null,
                                    selected: true,
                                    toggle: true
                                }
                            },
                            state: {
                                info: null,
                                outOfBounds: false,
                                outOfScale: false,
                                visibility: true
                            }
                        };
                        if(layer.nestedLayers.length > 0){
                            def.children = [];
                            for(var i = 0; i < layer.nestedLayers.length; i++){
                                num ++;
                                def.children.push(readCapabilities(layer.nestedLayers[i], def, id, num));
                            }
                        }
                        return def;
                    }
                    function getSplitted(service, rootLayer, layer, result, num){
                        
                        if(num !== 0){
                            var service_new = $.extend(true, {}, service);
                            service_new.id = service_new.id + "_" + num;
                            service_new.origId = service_new.id;
                            var root_new = $.extend(true, {}, rootLayer);
                            root_new.options.id = service_new.id + "_0";
                            var layer_new = $.extend(true, {}, layer);
                            layer_new.options.id = service_new.id + "_1";
                            if(layer_new.children)
                                delete(layer_new.children);
                            root_new.children = [layer_new];
                            service_new.configuration.children = [root_new];
                            return service_new;
                        }
                        if(layer.children){
                            for(var i = 0; i < layer.children.length; i++){
                                num++;
                                result.push(getSplitted(service, rootLayer, layer.children[i], result, num));
                            }
                        }
                    }
                    var layers = readCapabilities(capabilities.capability.nestedLayers[0], null, id, 0);
                    if(splitLayers){
                        var service = $.extend(true, {}, def);
                        var result = [];
                        var defs = getSplitted(def, layers, layers, result, 0);
                        return result;
                    } else {
                        def.configuration.children = [layers];
                        return [def];
                    }
                    
                } else {
                    return null;
                }
            },
        
            getPrintConfig: function(layer, bounds) {
                return {
                    type: 'wms',
                    url: layer.getURL(bounds)
                };
            },
            
            onLoadError: function(imgEl, sourceId, projection){
                var loadError = {sourceid: sourceId, details: ''};
                $.ajax({
                    type: "GET",
                    async: false,
                    url: Mapbender.configuration.application.urls.proxy+"?url="+encodeURIComponent(imgEl.attr('src')),
                    success: function(message, text, response){
                        if(typeof(response.responseText) === "string"){
                            var details = "The map cannot be displayed.";
                            var layerTree;
                            try {
                                layerTree = new OpenLayers.Format.WMSCapabilities().read(response.responseText);
                            } catch(e) {
                                layerTree = null;
                                details += ".\n" + "Exception" + ": " + e.toString();
                            }
                            if(layerTree && layerTree.error) {
                                if(layerTree.error.exceptionReport && layerTree.error.exceptionReport.exceptions){
                                    var excs = layerTree.error.exceptionReport.exceptions;
                                    details += ":";
                                    for(var m = 0; m < excs.length; m++){
                                        var exc = excs[m].code;
                                        details += "\n" + exc;
                                        if(excs[m].code == "InvalidSRS"){
                                            details += " (" + projection.projCode + ")";
                                        }
                                    }
                                }
                            }
                        }
                        loadError.details = details;
                    },
                    error: function(err) {
                        var details = "The map cannot be displayed.";
                        if(err.status == 200){
                            var capabilities;
                            try {
                                capabilities = new OpenLayers.Format.WMSCapabilities().read(err.responseText);
                            } catch(e) {
                                capabilities = null;
                                details += ".\n" + "Exception" + ": " + e.toString();
                            }
                            if(capabilities && capabilities.error) {
                                if(capabilities.error.exceptionReport && capabilities.error.exceptionReport.exceptions){
                                    var excs = capabilities.error.exceptionReport.exceptions;
                                    details += ":";
                                    for(var m = 0; m < excs.length; m++){
                                        var exc = excs[m].code;
                                        details += "\n" + exc;
                                        if(excs[m].code == "InvalidSRS"){
                                            details += " (" + projection.projCode + ")";
                                        }
                                        if(exc != excs[m].code){

                                        } else if(excs[m].text){
                                            details += "\n" + excs[m].text;
                                        }
                                    }
                                }
                            }
                        } else {
                            details += ".\n" + "HTTP status code" + ": " + err.status + " - " + err.statusText;
                        }
                        loadError.details = details;
                    }
                });
                return loadError;
            },
            
            hasLayers: function(source, withoutGrouped){
                var options = this.layerCount(source);
                if(withoutGrouped){
                    return options.simpleCount > 0;
                } else { // without root layer
                    return options.simpleCount + options.groupedCount - 1 > 0;
                }
            },
            
            layerCount: function(source){
                if(source.configuration.children.length === 0){
                    return {simpleCount: 0, grouppedCount: 0};
                }
                var options = {simpleCount: 0, groupedCount: 0}
                return _layerCount(source.configuration.children[0], options);
                function _layerCount(layer, options){
                    if(layer.children){
                        options.grouppedCount++;
                        for (var i = 0; i < layer.children.length; i++){
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
                var options ={layers: [], found: false, cut_with: includeOffset};
                if(rootLayer.options.id.toString() === offsetLayer.options.id.toString()){
                    options.found = true;
                }
                options = _findLayers(rootLayer, offsetLayer, options);
                return {source: _source, layers: options.layers};
                
                function _findLayers(layer, offsetLayer, options){
                    if(layer.children){
                        var i = 0;
                        for (; i < layer.children.length; i++){
                            if(layer.children[i].options.id.toString() === offsetLayer.options.id.toString()){
                                options.found = true;
                                if(options.cut_with){
                                    var lays = layer.children.splice(i, layer.children.length - i);
                                    options.layers = options.layers.concat(lays);
                                    break;
                                }
                            } else if(options.found){
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
                    if(layer.options.id.toString() === parentLayerToAdd.options.id.toString()){
                        if(layer.children){
                            layer.children.splice(position, 0, layerToAdd);
                            options.layer = layer.children[position];
                        } else {
                            // ignore position
                            layer.children = [];
                            layer.children.push($.extend(true,layerToAdd));
                            options.layer = layer.children[0];
                        }
                        return options;
                    }
                    if(layer.children){
                        for (var i = 0; i < layer.children.length; i++){
                            options = _addLayer(layer.children[i], layerToAdd, parentLayerToAdd, position, options);
                        }
                    }
                    return options;
                }
            },
        
            removeLayer: function(source, layerToRemove){
                var rootLayer = source.configuration.children[0];
                if(layerToRemove.options.id.toString() === rootLayer.options.id.toString()){
                    source.configuration.children = [];
                    return {layer: rootLayer};
                }
                var options = {layer: null, layerToRemove: null};//, listToRemove: {}, addToList: false }
                options = _removeLayer(rootLayer, layerToRemove, options);
                return { layer: options.layerToRemove};
                
                function _removeLayer(layer, layerToRemove, options){
                    if(layer.children){
                        for (var i = 0; i < layer.children.length; i++){
                            options = _removeLayer(layer.children[i], layerToRemove, options);
                            if(options.layer){
                                if(options.layer.options.id.toString() === layerToRemove.options.id.toString()){
                                    var layerToRemArr = layer.children.splice(i, 1);
                                    if(layerToRemArr[0]){
                                        options.layerToRemove = $.extend({},layerToRemArr[0]);
                                    }
                                }
                            }
                        }
                    }
                    if(layer.options.id.toString() === layerToRemove.options.id.toString()){
                        options.layer = layer;
                        options.layerToRemove = layer;
                        return options;
                    }  else {
                        options.layer = null;
                        return options;
                    }
                }
            },
            
            findLayer: function(source, idToFind){
                var rootLayer = source.configuration.children[0];
                var options = {level: 0, idx: 0, layer: null, parent: null};
                options = _findLayer(rootLayer, idToFind, options, 0);
                return options;
                function _findLayer(layer, idToFind, options, levelTmp){
                    if(layer.children){
                        levelTmp++;
                        for (var i = 0; i < layer.children.length; i++){
                            if(layer.children[i].options.id.toString() === idToFind.toString()){
                                options.idx = i;
                                options.parent = layer;
                                options.level = levelTmp;
                                options.layer = layer.children[i];
                                return options;
                            } else {
                                options = _findLayer(layer.children[i], idToFind, options, levelTmp);
                            }
                        }
                        levelTmp--;
                    }
                    if(layer.options.id.toString() === idToFind.toString()){
                        options.level = levelTmp;
                        options.layer = layer;
                        return options;
                    }  else {
                        return options;
                    }
                }
            },
            
            checkInfoLayers: function(source, scale, tochange, result){
                var rootLayer = source.configuration.children[0];
                _checkInfoLayers(rootLayer, scale, {state:{visibility: true}}, tochange, result);
                return result;
                
                function _checkInfoLayers(layer, scale, parent, tochange, result){
                    var layerChanged;
                    if(typeof layer.options.treeOptions.info === 'undefined'){
                         layer.options.treeOptions.info = false;
                    }
                    if(tochange.children[layer.options.id] && layer.options.name.length > 0){
                        layerChanged = tochange.children[layer.options.id];
                        if(layerChanged.options.treeOptions.info !== layer.options.treeOptions.info){
                            layer.options.treeOptions.info = layerChanged.options.treeOptions.info;
                            result.changed.children[layer.options.id] = layerChanged;
                        }
                    }
                    if(layer.options.treeOptions.info === true && layer.state.visibility){
                        result.info.push(layer.options.name);
                    }
                    if(layer.children){
                        for(var j = 0; j < layer.children.length; j++){
                            _checkInfoLayers(layer.children[j], scale, layer, tochange, result);
                        }
                    }
                }
            },
            
            checkLayers: function(source, scale, tochange, result){
                var rootLayer = source.configuration.children[0];
                _checkLayers(rootLayer, scale, { state:{ visibility: true } }, tochange, result);
                return result;
                function _checkLayers(layer, scale, parent, tochange, result){
                    var layerChanged;
                    if(tochange.children[layer.options.id]){
                        layerChanged = tochange.children[layer.options.id];
                        layerChanged.state = {
                            outOfScale: layer.state.outOfScale,
                            outOfBounds:layer.state.outOfBounds,
                            visibility: layer.state.visibility
                        };
                        if(typeof layerChanged.options.treeOptions.selected !== 'undefined'){
                            layer.options.treeOptions.selected = layerChanged.options.treeOptions.selected;
                        }
                    } else {
                        layerChanged = {
                            state: {
                                outOfScale: layer.state.outOfScale,
                                outOfBounds:layer.state.outOfBounds,
                                visibility: layer.state.visibility
                            }
                        };
                    }
                    if(layer.options.minScale){
                        if(layer.options.minScale <= scale){
                            layer.state.outOfScale = false;
                        } else {
                            layer.state.outOfScale = true;
                        }
                    } else {
                        layer.state.outOfScale = false;
                    }
                    if(!layer.state.outOfScale){
                        if(layer.options.maxScale){
                            if(layer.options.maxScale >= scale){
                                layer.state.outOfScale = false;
                            } else {
                                layer.state.outOfScale = true;
                            }
                        } else {
                            layer.state.outOfScale = false;
                        }
                    }
                    /* @TODO outOfBound for layers */
                    layer.options.outOfBounds = false;

                    if(layer.children){
                        //                var this_vsbl = false;
                        if(parent.state.visibility
                            && layer.options.treeOptions.selected
                            && !layer.state.outOfScale
                            && !layer.state.outOfBounds){
                            layer.state.visibility = true;
                        } else {
                            layer.state.visibility = false;
                        }
                        var child_visible = false;
                        for(var j = 0; j < layer.children.length; j++){
                            var child = _checkLayers(layer.children[j], scale, layer, tochange, result);
                            if(child.state.visibility){
                                child_visible = true;
                            }
                        }
                        if(child_visible){
                            layer.state.visibility = true;
                        } else {
                            layer.state.visibility = false;
                        }
                    } else {
                        if(parent.state.visibility
                            && layer.options.treeOptions.selected
                            && !layer.state.outOfScale
                            && !layer.state.outOfBounds
                            && layer.options.name.length > 0){
                            layer.state.visibility = true;
                            result.layers.push(layer.options.name);
                            if(layer.options.treeOptions.info === true){
                                result.infolayers.push(layer.options.name);
                            }
                        } else {
                            layer.state.visibility = false;
                        }
                    }
                    var elchanged = false;
                    if(layerChanged.state.outOfScale !== layer.state.outOfScale){
                        layerChanged.state.outOfScale = layer.state.outOfScale;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.outOfScale);
                    }
                    if(layerChanged.state.outOfBounds !== layer.state.outOfBounds){
                        layerChanged.state.outOfBounds = layer.state.outOfBounds;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.outOfBounds);
                    }
                    if(layerChanged.state.visibility !== layer.state.visibility){
                        layerChanged.state.visibility = layer.state.visibility;
                        elchanged = true;
                    } else {
                        delete(layerChanged.state.visibility);
                    }
                    if(elchanged) {
                        layerChanged.treeElm = layer;
                        result.changed.children[layer.options.id] = layerChanged;
                    }
                    return layer;
                }
            },
            
            changeOptions: function(tochange){
                if(typeof tochange.options !== 'undefined'
                    && typeof tochange.options.visibility !== 'undefined'){
                    tochange.children[tochange.source.configuration.children[0].options.id] = {options: {treeOptions: {selected: tochange.options.visibility}}};
                    return tochange;
                } else {
                    // @TODO
                    return null;
                }
            }
        }
    }
});


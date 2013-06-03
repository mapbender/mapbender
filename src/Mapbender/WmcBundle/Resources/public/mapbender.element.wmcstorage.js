(function($) {

    $.widget('mapbender.mbWmcStorage', $.ui.dialog,  {
        options: {
            modal: true,
            autoOpen: false,
            width: 600,
            height: 400,
            searchParams: []
        },

        elementUrl: null,
        saveForm: null,
        loadForm: null,
        map: null,

        _create: function() {
            if(!Mapbender.checkTarget("mbWmcStorage", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function() {
            var self = this,
            me = $(this.element);
            // Initialize super widget (dialog)
            this._super('_create');
            // Save elementUrl for later use
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            // Store a reference to the map
            this.map = $('#' + this.options.target).data('mapQuery').olMap;
            // Make all buttons jQuery UI buttons
            me.find('button').button();
            // Trigger save from within save dialog
            me.find('button#save-wmc').click(function() {
                var docName = me.find('input#wmc-doc-title-save').val();
                self.save.call(self, docName);
            });
            me.find('#wmc-load button').click(function() {
                var docId = me.find('select#wmc-doc-title-load').val();
                self.load.call(self, {
                    id: docId
                });
            });
            me.find('button#delete-wmc').click(function() {
                var docId = me.find('select#wmc-delete').val();
                if(docId !== null) {
                    self.deleteWmc.call(self, docId);
                }
            });
        },

        load: function(params, options) {
            if(!params) {
                this._showLoadDialog();
            } else {
                this._load(params, options);
            }
        },

        save: function(docName) {
            if(!docName) {
                this._showSaveDialog();
            } else {
                this._save(docName);
            }
        },

        _load: function(params, options) {
            var self = this;
            $.ajax({
                url: this.elementUrl + 'load?' + $.param({
                    params: params
                }),
                context: this,
                success: function(data) {
                    this._loadSuccess(data, options)
                },
                error: this._loadError
            });
        },

        loadWmcDocument: function(doc) {
            var xmlDoc = null;
            if(window.DOMParser) {
                var parser = new DOMParser();
                xmlDoc = parser.parseFromString(doc, 'text/xml');
            } else if(window.ActiveXObject) {
                xmlDoc = new ActiveXObject('Microsoft.XMLDOM');
                xmlDoc.async = false;
                xmlDoc.loadXML(doc);
            }
            xmlDoc && this._loadDocSuccess(xmlDoc);
        },
        
        loadWmcJson: function(json) {
            json && this._loadJsonSuccess(json);
        },

        download: function(wmc) {
            $('#wmc-download-textarea').val(wmc);
            $('#wmc-download-form').attr('action', this.elementUrl + 'download');
            $('#wmc-download-form').submit();
        },

        _save: function(docName) {
            if(!docName) {
                throw "No WMC document title given.";
            }

            var me = $(this.element);
            this.open();
            me.find('#wmc-save-spinner').show()
            .siblings().hide();

            var format = new OpenLayers.Format.WMC(),
            wmc = format.write(this.map);

            var extraData = {
                title: docName,
                'public': false,
                crs: this.map.getProjection()
            };

            $.ajax({
                url: this.elementUrl + 'save?' + $.param(extraData),
                type: 'post',
                contentType: 'text/xml',
                processData: false,
                data: wmc,
                context: this,
                success: this._onSaveSuccess,
                error: this._onSaveError
            });
        },

        _onSaveSuccess: function(data) {
            this._trigger('savesuccess');
            this.close();
        },

        _onSaveError: function(jqXHR, textStatus, errorThrown) {
            $(this.element).find('div#wmc-save-error').show().siblings().hide();
        },

        _loadDocSuccess: function(data, options) {
//            options = options || {};
//            var me = $(this.element),
//                format = new OpenLayers.Format.WMC(),
//                map = $('#' + this.options.target).data('mbMap');
//
//            me.find('div#wmc-load-spinner').show().siblings().hide();
//
//            var wmc = format.read(data);
//            // Check if wmc.projection supported
//            var supported = map.getAllSrs();
//            var found = false;
//            for(var i = 0; i < supported.length; i++){
//                if(wmc.projection.toUpperCase() === supported[i].name.toUpperCase()){
//                    found = true;
//                    break;
//                }
//            }
//            if(!found){
//                Mapbender.error('The projection:"'+wmc.projection+'" is not supported by this application.');
//                return;
//            }
//            // remove all sources from model
////            var sources = map.getSources();
////            for(i = (sources.length - 1); i > -1; i--){
////                map.removeSource(sources[i]);
////            }
//
//            /**
//         * Set projection, maxExtent and bounds
//         */
////            map.map.olMap.projection = wmc.projection;
////            map.map.olMap.maxExtent = wmc.maxExtent;
//
//            /**
//         * Load layers from WMC into map
//         */
//            //TODO: queryLayers, opacity
////            var hasBaseLayer = false;
//            var layerDefs = $.map(wmc.layersContext, function(layerContext) {
////                hasBaseLayer |= layerContext.isBaselayer;
//                return {
//                    type: 'wms',
//                    label: layerContext.title,
//                    url: layerContext.url,
//
//                    allLayers: layerContext.allLayers,
//                    layers: layerContext.name,
//                    transparent: layerContext.transparent,
//                    format: layerContext.formats[0].value,
//                    isBaseLayer: layerContext.isBaseLayer,
//
//                    visible: layerContext.visibility,
//                    tiled: !layerContext.singleTile
//                };
//            });
////            map.map.olMap.allOverlays = hasBaseLayer ? false : true;
//            $.each(layerDefs, function(idx, layerDef) {
//                map.map.layers(layerDef);
//            });
//            if(!options.dontZoom) {
//                map.zoomToExtent(wmc.bounds);
//            }
//
//            this._trigger('loaddone');
//
//            this.close();
        },
        
        _loadJsonSuccess: function(wmc, options) {
            options = options || {};
            var me = $(this.element),
//                format = new OpenLayers.Format.WMC(),
                map = $('#' + this.options.target).data('mbMap');
//
//            me.find('div#wmc-load-spinner').show().siblings().hide();
//
//            var wmc = format.read(data);
            // Check if wmc.projection supported
            var supported = map.getAllSrs();
            var found = false;
            for(var i = 0; i < supported.length; i++){
                if(wmc.extent.srs.toUpperCase() === supported[i].name.toUpperCase()){
                    found = true;
                    var bbox = wmc.extent;
                    var extent = OpenLayers.Bounds.fromArray([bbox.minx, bbox.miny, bbox.maxx, bbox.maxy]);
                    map.setExtent(extent);
                    var bboxm = wmc.maxextent;
                    var extentm = OpenLayers.Bounds.fromArray([bboxm.minx, bboxm.miny, bboxm.maxx, bboxm.maxy]);
                    map.setMaxExtent(extentm, bbox.srs);
                    map.changeProjection(bbox.srs);
                    map.zoomToExtent(extent);
                    break;
                }
            }
            if(!found){
                Mapbender.error('The projection:"'+wmc.extent.srs+'" is not supported by this application.');
                return;
            }
            // remove all sources from model
            var sources = map.getSourceTree();
            for(i = (sources.length - 1); i > -1; i--){
                map.removeSource(sources[i]);
            }
            
            for(i = 0; i < wmc.sources.length; i++){
                map.addSource(wmc.sources[i]);
            }

            /**
         * Set projection, maxExtent and bounds
         */
//            map.map.olMap.projection = wmc.projection;
//            map.map.olMap.maxExtent = wmc.maxExtent;

            /**
         * Load layers from WMC into map
         */
//            //TODO: queryLayers, opacity
////            var hasBaseLayer = false;
//            var layerDefs = $.map(wmc.layersContext, function(layerContext) {
////                hasBaseLayer |= layerContext.isBaselayer;
//                return {
//                    type: 'wms',
//                    label: layerContext.title,
//                    url: layerContext.url,
//
//                    allLayers: layerContext.allLayers,
//                    layers: layerContext.name,
//                    transparent: layerContext.transparent,
//                    format: layerContext.formats[0].value,
//                    isBaseLayer: layerContext.isBaseLayer,
//
//                    visible: layerContext.visibility,
//                    tiled: !layerContext.singleTile
//                };
//            });
////            map.map.olMap.allOverlays = hasBaseLayer ? false : true;
//            $.each(layerDefs, function(idx, layerDef) {
//                map.map.layers(layerDef);
//            });
//            if(!options.dontZoom) {
//                map.zoomToExtent(wmc.bounds);
//            }
//
//            this._trigger('loaddone');
//
//            this.close();
        },

        _loadError: function(jqXHR, textStatus, errorThrown) {
            this._trigger('loaderror');
        },

        _showSaveDialog: function() {
            $(this.element).find('div#wmc-save')
            .show()
            .siblings().hide();

            this._loadDeleteList();
            this.open();
        },

        _loadDeleteList: function() {
            $.ajax({
                url: this.elementUrl + 'list',
                data: {
                    params: $.extend({}, this.options.searchParams, {
                        'private': true
                    })
                },
                context: this,
                success: this._deleteListWmcSuccess
            //error: this._listWmcError
            });
        },

        deleteWmc: function(id) {
            $.ajax({
                url: this.elementUrl + 'delete',
                data: {
                    id: id
                },
                complete: $.proxy(this._loadDeleteList, this)
            });
        },

        _deleteListWmcSuccess: function(data) {
            var me = $(this.element),
            select = me.find('select#wmc-delete').
            empty();

            $.each(data, function(idx, wmc) {
                var obj = $('<option></option>');
                obj.attr('value', wmc.id);
                obj.html(wmc.title);
                obj.appendTo(select);
            });
        },

        _showLoadDialog: function() {
            $(this.element).find('div#wmc-list-spinner')
            .show()
            .siblings().hide();

            this.open();

            $.ajax({
                url: this.elementUrl + 'list',
                data: {
                    params: this.options.searchParams
                },
                context: this,
                success: this._listWmcSuccess,
                error: this._listWmcError
            });
        },

        listWmc: function(callback) {
            if(typeof(callback) !== 'function') {
                throw "callback is not a function!";
            }

            $.ajax({
                url: this.elementUrl + 'list',
                data: {
                    params: this.options.searchParams
                },
                context: this,
                success: callback,
                error: function() {
                    callback({});
                }
            });
        },

        _listWmcSuccess: function(data) {
            var me = $(this.element),
            select = me.find('select#wmc-doc-title-load').empty();

            $.each(data, function(idx, wmc) {
                $('<option></option>')
                .attr('value', wmc.id)
                .html(wmc.title)
                .appendTo(select);
            });

            me.find('div#wmc-load')
            .show()
            .siblings().hide();
        },

        _listWmcError: function(jqXHR, textStatus, errorThrown) {
            $(this.element).find('div#wmc-list-error')
            .show()
            .siblings().hide();
        }
    });

})(jQuery);

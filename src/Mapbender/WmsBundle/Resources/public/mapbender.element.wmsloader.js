(function($){

    $.widget("mapbender.mbWmsloader", {
        options: {
            autoOpen: false,
            title: Mapbender.trans('mb.wms.wmsloader.title'),
            splitLayers: false,
            wms_url: null
        },
        loadedSourcesCount: 0,
        elementUrl: null,
        _create: function(){
            var self = this;
            if(!Mapbender.checkTarget("mbWmsloader", this.options.target)){
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.element.hide();
            if(Mapbender.declarative){
                Mapbender.declarative['source.add.wms'] = $.proxy(this.loadDeclarativeWms, this);
            }else{
                Mapbender['declarative'] = {'source.add.wms': $.proxy(this.loadDeclarativeWms, this)};
            }
            if(this.options.wms_url && this.options.wms_url !== ''){
                var urlObj = new Mapbender.Util.Url(this.options.wms_url);
                var options = {
                    'gcurl': urlObj,
                    'type': 'url',
                    'layers': {},
                    'global': {
                        'mergeSource': false,
                        'splitLayers': this.options.splitLayers,
                        'options': {'treeOptions': {'selected': true}}
                    }
                };
                this.loadWms(options);
            }

            if (this.options.wms_id && this.options.wms_id !== '') {
                var options = {
                    'gcurl': '',
                    'type': 'id',
                    'layers': {},
                    'global': {
                        'mergeSource': false,
                        'splitLayers': this.options.splitLayers,
                        'options': {'treeOptions': {'selected': true}}
                    }
                };
                this._getInstances(this.options.wms_id, options);
            }
            this._trigger('ready');
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.element.show();
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: false,
                    content: self.element,
                    destroyOnClose: true,
                    width: 500,
                    height: 325,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans('mb.wms.wmsloader.dialog.btn.cancel'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans('mb.wms.wmsloader.dialog.btn.load'),
                            cssClass: 'button right',
                            callback: function(){
                                var url = $('input[name="loadWmsUrl"]', self.element).val();
                                if(url === ''){
                                    $('input[name="loadWmsUrl"]', self.element).focus();
                                    return false;
                                }
                                var urlObj = new Mapbender.Util.Url(url);
                                urlObj.username = $('input[name="loadWmsUser"]', self.element).val();
                                urlObj.password = $('input[name="loadWmsPass"]', self.element).val();
                                var options = {
                                    'gcurl': urlObj,
                                    'type': 'url',
                                    'layers': {},
                                    'global': {
                                        'mergeSource': false,
                                        'splitLayers': self.options.splitLayers,
                                        'options': {'treeOptions': {'selected': true}}
                                    }
                                };
                                self.loadWms.call(self, options);
                                self.element.hide().appendTo($('body'));
                                self.close();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            }else{
                this.popup.open();
            }
        },
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                if(this.popup.$element)
                    this.popup.destroy();
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        loadDeclarativeWms: function(elm){
            var self = this;
            var options = {
                'gcurl': new Mapbender.Util.Url(elm.attr('mb-url') ? elm.attr('mb-url') : elm.attr('href')),
                'type': 'declarative',
                'layers': {},
                'global': {
                    'mergeSource': !elm.attr('mb-wms-merge') ? true : elm.attr('mb-wms-merge') === '1' ? true : false,
                    'splitLayers': false,
                    'mergeLayers': !elm.attr('mb-wms-layer-merge') ? true : elm.attr('mb-wms-layer-merge') === '1' ? true : false,
                    'options': {'treeOptions': {'selected': false}}
                }
            };
            if (elm.attr('mb-wms-layers')) {
                var layerNamesToActivate = elm.attr('mb-wms-layers').split(',');
                if (layerNamesToActivate.indexOf('_all') !== -1) {
                    options.global.options.treeOptions.selected = true;
                } else {
                    _.each(layerNamesToActivate, function(layerName) {
                        options.layers[layerName] = {options: {treeOptions: {selected: true}}};
                    });
                }
            }
            if(options.global.mergeSource){
                var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
                var sources = mbMap.model.getSources();
                for(var i = 0; i < sources.length; i++){
                    var source = sources[i];
                    var url_source = Mapbender.Util.removeSignature(source.configuration.options.url.toLowerCase());
                    if(decodeURIComponent(options.gcurl.asString().toLowerCase()).indexOf(decodeURIComponent(url_source)) === 0){
                        // source exists
                        mbMap.model.changeLayerState({id: source.id}, options, options.global.options.treeOptions.selected, options.global.mergeLayers);
                        return false;
                    }
                }
            }
            this.loadWms(options);
            return false;
        },
        loadWms: function (sourceOpts) {
            var self = this;
            var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
            sourceOpts['global']['defaultFormat'] = this.options.defaultFormat;
            sourceOpts['global']['defaultInfoFormat'] = this.options.defaultInfoFormat;
            sourceOpts['model'] = mbMap.model;
            $.ajax({
                url: self.elementUrl + 'loadWms',
                data: {
                    url: sourceOpts.gcurl.asString()
                },
                dataType: 'json',
                success: function(data, textStatus, jqXHR){
                    var i;

                    for (i = 0; i < data.length; i++) {
                      data[i].configuration.options.info_format = self.options.defaultInfoFormat;
                      data[i].configuration.options.format = self.options.defaultFormat;
                    }

                    self._addSources(data, sourceOpts);
                },
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _addSources: function(sourceDefs, sourceOpts) {
            var srcIdPrefix = 'wmsloader-' + $(this.element).attr('id');
            var self = this;
            var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
            $.each(sourceDefs, function(idx, sourceDef) {
                var sourceId = srcIdPrefix + '-' + (self.loadedSourcesCount++);
                var findOpts = {configuration: {options: {url: sourceDef.configuration.options.url}}};
                sourceDef.id = sourceId;
                sourceDef.origId = sourceId;
                Mapbender.Util.SourceTree.generateLayerIds(sourceDef);
                sourceDef.wmsloader = true;
                if (sourceOpts.global.options.treeOptions.selected !== true) {
                    // We only need to do this if we DO NOT want all layers selected
                    // because all layer configs received from the server will start
                    // out selected by default
                    self._initializeTreeOptions(sourceDef, sourceOpts);
                }
                if (!sourceOpts.global.mergeSource || !mbMap.model.findSource(findOpts).length){
                    mbMap.addSource(sourceDef, false);
                }
            });
            // Enable feature info
            // @todo: find a way to do this directly on the map, without using the layertree
            // @todo: fix default for newly added source (no fi) to match default layertree visual (fi on)
             $('.mb-element-layertree .featureInfoWrapper input[type="checkbox"]').trigger('change');
        },
        _initializeTreeOptions: function(sourceDef, loaderOptions) {
            // pass one: reset everything except root layer
            Mapbender.Util.SourceTree.iterateLayers(sourceDef, false, function(layerDef, offset, parents) {
                if (parents.length) {
                    layerDef.options.treeOptions.selected = false;
                }
            });
            // pass two: activate desired layers along with their parent chain
            Mapbender.Util.SourceTree.iterateLayers(sourceDef, false, function(layerDef, offset, parents) {
                // Skip checking layers that have empty names. This is common on group layers or even root
                // layers that the WMS server doesn't intend for direct GetMap usage.
                if (layerDef.options.name && loaderOptions.layers.hasOwnProperty(layerDef.options.name)) {
                    var newLayerOptions = loaderOptions.layers[layerDef.options.name];
                    var selected = layerDef.options.treeOptions.selected = newLayerOptions.options.treeOptions.selected;
                    if (selected) {
                        // layer active => activate all parents implicitly
                        parents.map(function(parentLayer) {
                            parentLayer.options.treeOptions.selected = parentLayer.options.treeOptions.selected || selected;
                        });
                    }
                }
            });
        },
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR){
            Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.load'));
        },
        _destroy: $.noop
    });

})(jQuery);

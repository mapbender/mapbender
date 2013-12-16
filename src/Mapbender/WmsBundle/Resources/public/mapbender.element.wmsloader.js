(function($){

    $.widget("mapbender.mbWmsloader", {
        options: {
            autoOpen: false,
            title: Mapbender.trans('mb.wms.wmsloader.title')
        },
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
            this._trigger('ready');
            this._ready();
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
                    closeButton: false,
                    closeOnESC: false,
                    closeOnPopupCloseClick: false,
                    content: self.element,
                    destroyOnClose: true,
                    width: 500,
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
                                var url = $('#' + $(self.element).attr('id') + ' input[name="loadWmsUrl"]').val();
                                if(url === ''){
                                    $('#' + $(self.element).attr('id') + ' input[name="loadWmsUrl"]').focus();
                                    return false;
                                }
                                var options = {
                                    'gcurl': url,
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
                'gcurl': elm.attr('mb-url') ? elm.attr('mb-url') : elm.attr('href'),
                'type': 'declarative',
                'layers': {},
                'global': {
                    'mergeSource': !elm.attr('mb-wms-merge') ? true : elm.attr('mb-wms-merge') === '1' ? true : false,
                    'splitLayers': false,
                    'mergeLayers': !elm.attr('mb-wms-layer-merge') ? true : elm.attr('mb-wms-layer-merge') === '1' ? true : false,
                    'options': {'treeOptions': {'selected': false}}
                }
            };
            if(elm.attr('mb-wms-layers') && elm.attr('mb-wms-layers') === '_all'){
                options.global.options.treeOptions.selected = true;
            }else if(elm.attr('mb-wms-layers')){
                var layers = {};
                $.each(elm.attr('mb-wms-layers').split(','), function(idx, item){
                    layers[item] = {options: {treeOptions: {selected: true}}};
                });
                options.layers = layers;
            }
            if(options.global.mergeSource){
                var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
                var sources = mbMap.model.getSources();
                for(var i = 0; i < sources.length; i++){
                    var source = sources[i];
                    var url_source = Mapbender.source.wms.removeSignature(source.configuration.options.url.toLowerCase());
                    if(decodeURIComponent(options.gcurl.toLowerCase()).indexOf(decodeURIComponent(url_source)) === 0){
                        // source exists
                        mbMap.model.changeLayerState({id: source.id}, options, options.global.options.treeOptions.selected, options.global.mergeLayers);
                        return false;
                    }
                }
                this.loadWms(options);
            }else{
                this.loadWms(options);
            }
            return false;
        },
        loadWms: function(options){
            var self = this;
            if(!options.gcurl || options.gcurl === '' ||
                (options.gcurl.toLowerCase().indexOf("http://") !== 0 && options.gcurl.toLowerCase().indexOf("https://") !== 0)){
                Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.url'));
                return;
            }
            var params = OpenLayers.Util.getParameters(options.gcurl);
            var version = null, request = null, service = null;
            for(param in params){
                if(param.toUpperCase() === "VERSION"){
                    version = params[param];
                }else if(param.toUpperCase() === "REQUEST"){
                    request = params[param];
                }else if(param.toUpperCase() === "SERVICE"){
                    service = params[param];
                }
            }
            if(request === null || service === null){
                Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.url'));
                return;
            }

            if(service.toUpperCase() !== "WMS"){
                Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.service', {"service": service}));
                return false;
            }else if(request.toUpperCase() !== "GETCAPABILITIES" && request.toUpperCase() !== 'CAPABILITIES'){
                Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.operation', {"operation": request }));
                return false;
            }else if(version && !(version.toUpperCase() === "1.1.0" || version.toUpperCase() === "1.1.1" || version.toUpperCase() === "1.3.0")){
                Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.version', {"version": version}));
                return false;
            }
            $.ajax({
                url: self.elementUrl + 'getCapabilities',
                data: {
                    url: encodeURIComponent(options.gcurl)
                },
                dataType: 'text',
                success: function(data, textStatus, jqXHR){
                    self._getCapabilitiesUrlSuccess(data, options);
                    // Maybe to much, need to be scoped!
//                    $(".checkbox").trigger("change");
                },
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _getCapabilitiesUrlSuccess: function(xml, sourceOpts){
            var self = this;
            var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
            sourceOpts['global']['defaultFormat'] = this.options.defaultFormat;
            sourceOpts['global']['defaultInfoFormat'] = this.options.defaultInfoFormat;
            sourceOpts['model'] = mbMap.model;
            var sourceDefs = Mapbender.source.wms.createSourceDefinitions(xml, sourceOpts);
            $.ajax({
                url: self.elementUrl + 'signeSources',
                data: {
                    sources: JSON.stringify(sourceDefs)
                },
                type: 'POST',
                dataType: 'json',
                success: function(response){
                    if(response.success){
                        var sources = $.parseJSON(response.success);
                        self._addSources(sources, sourceOpts);
                    }else if(response.error){
                        Mapbender.error(response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        
        _addSources: function(sourceDefs, sourceOpts){
            var self = this;
            var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
            $.each(sourceDefs, function(idx, sourceDef){
                var opts = {configuration: {options: {url: sourceDef.configuration.options.url}}};
                if(!sourceOpts.global.mergeSource){
                    mbMap.addSource(sourceDef, null, null);
                }else if(mbMap.model.findSource(opts).length === 0){
                    mbMap.addSource(sourceDef, null, null);
                }

            });
        },
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR){
            Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.load'));
        },
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);

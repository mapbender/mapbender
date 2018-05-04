(function($){

    $.widget("mapbender.mbWmsloader", {
        options: {
            autoOpen: false,
            title: Mapbender.trans('mb.wms.wmsloader.title'),
            splitLayers: false,
            wms_url: null
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
                    closeOnPopupCloseClick: true,
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
                    if(decodeURIComponent(options.gcurl.asString().toLowerCase()).indexOf(decodeURIComponent(url_source)) === 0){
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
                    self._addSources([data], sourceOpts)
                },
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _addSources: function(sourceDefs, sourceOpts) {
            var self = this;
            var mbMap = $('#' + self.options.target).data('mapbenderMbMap');
            $.each(sourceDefs, function(idx, sourceDef) {
                var opts = {configuration: {options: {url: sourceDef.configuration.options.url}}};
                sourceDef.configuration.status = 'ok';
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

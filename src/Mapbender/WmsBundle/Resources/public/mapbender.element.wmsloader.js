(function($){

    $.widget("mapbender.mbWmsloader", {
        options: {
            autoOpen: false,
            title: Mapbender.trans('mb.wms.wmsloader.title'),
            wms_url: null
        },
        loadedSourcesCount: 0,
        elementUrl: null,
        mbMap: null,
        _layerOptionsOn: {options: {treeOptions: {selected: true}}},
        _layerOptionsOff: {options: {treeOptions: {selected: false}}},
        _create: function() {
            var self = this;
            if(!Mapbender.checkTarget("mbWmsloader", this.options.target)){
                return;
            }
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
                self._trigger('ready');
            });
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
                        'options': {'treeOptions': {'selected': true}}
                    }
                };
                this.loadWms(options);
            }

            if (this.options.wms_id && this.options.wms_id !== '') {
                this._getInstances(this.options.wms_id);
            }
            if (this.options.autoOpen) {
                this.open();
            }
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
                                        'options': {'treeOptions': {'selected': true}}
                                    }
                                };
                                self.loadWms(options);
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
            var layerNamesToActivate = typeof(elm.attr('mb-wms-layers')) !== 'undefined' && elm.attr('mb-wms-layers').split(',');
            var mergeSource = !elm.attr('mb-wms-merge') || elm.attr('mb-wms-merge') === '1';
            var sourceUrl = elm.attr('mb-url') || elm.attr('href');

            if (mergeSource && this.mergeDeclarative(elm, sourceUrl, layerNamesToActivate)) {
                // Merged successfully to existing source, we're done
                return false;
            }
            // No merge allowed or merge allowed but no merge candidate found.
            // => load as an entirely new source
            var options = {
                gcurl: new Mapbender.Util.Url(sourceUrl),
                type: 'declarative',
                // assigned values are irrelevant, we only need an object with the layer names as keys
                layers: _.invert(layerNamesToActivate || []),
                global: {
                    mergeSource: false,
                    // Default other layers (=not passed in via mb-wms-layers) to off, as per documentation
                    options: this._layerOptionsOff.options
                }
            };
            this.loadWms(options);
            return false;
        },
        /**
         * @param {jQuery} $link
         * @param {string} sourceUrl
         * @param {Array<string>|false} layerNamesToActivate
         * @return {boolean} to indicate if a merge target was found and processed
         */
        mergeDeclarative: function($link, sourceUrl, layerNamesToActivate) {
            // NOTE: The evaluated attribute name has always been 'mb-wms-layer-merge', but documenented name
            //       was 'mb-layer-merge'. Just support both equivalently.
            var mergeLayersAttribValue = $link.attr('mb-wms-layer-merge') || $link.attr('mb-layer-merge');
            var mergeLayers = !mergeLayersAttribValue || (mergeLayersAttribValue === '1');
            var mergeCandidate = this._findMergeCandidateByUrl(sourceUrl);
            if (mergeCandidate) {
                if (layerNamesToActivate !== false) {
                    this.activateLayersByName(mergeCandidate, layerNamesToActivate, mergeLayers, true);
                }
                // NOTE: With no explicit layers to modify via mb-wms-layers, none of the other
                //       attributes and config params matter. We leave the source alone completely.
                return true;    // indicate merge target found, merging performed
            }
            return false;       // indicate no merge target, no merging performed
        },
        loadWms: function (sourceOpts) {
            var self = this;
            $.ajax({
                url: self.elementUrl + 'loadWms',
                data: {
                    url: sourceOpts.gcurl.asString()
                },
                dataType: 'json',
                success: function(data, textStatus, jqXHR){
                    self._addSources(data, sourceOpts);
                },
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _getInstances: function(scvIds) {
            var self = this;
            $.ajax({
                url: self.elementUrl + 'getInstances',
                data: {
                    instances: scvIds
                },
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    (response.success || []).map(function(sourceDef) {
                        if (!self.mbMap.model.getSource({id: sourceDef.id})) {
                            self.mbMap.addSource(sourceDef, false);
                        }
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.load'));
                }
            });
        },
        _addSources: function(sourceDefs, sourceOpts) {
            var srcIdPrefix = 'wmsloader-' + $(this.element).attr('id');
            var self = this;
            $.each(sourceDefs, function(idx, sourceDef) {
                var sourceId = srcIdPrefix + '-' + (self.loadedSourcesCount++);
                sourceDef.id = sourceId;
                sourceDef.origId = sourceId;
                // Need to pre-generate layer ids now because layer activation only works if layers
                // already have ids
                Mapbender.Util.SourceTree.generateLayerIds(sourceDef);
                sourceDef.wmsloader = true;
                var mergeCandidate = sourceOpts.global.mergeSource && self._findMergeCandidateByUrl(sourceDef.configuration.options.url);
                var updateTarget = mergeCandidate || sourceDef;
                var defaultLayerActive = sourceOpts.global.options.treeOptions.selected;
                self.activateLayersByName(updateTarget, Object.keys(sourceOpts.layers), defaultLayerActive, true);

                if (!mergeCandidate) {
                    self.mbMap.addSource(sourceDef, false);
                }
            });
        },
        /**
         * Locates an already loaded source with an equivalent base url, returns that source object or null
         * if no source matched.
         *
         * @param {string} url
         * @returns {Object|null}
         * @private
         */
        _findMergeCandidateByUrl: function(url) {
            var normalizeUrl = function(url) {
                var strippedUrl = Mapbender.Util.removeSignature(Mapbender.Util.removeProxy(url)).toLowerCase();
                // normalize query parameter encoding
                return new Mapbender.Util.Url(strippedUrl).asString();
            };
            var normUrl = normalizeUrl(url);
            var matches = this.mbMap.model.getSources().filter(function(source) {
                var sourceNormUrl = normalizeUrl(source.configuration.options.url);
                return sourceNormUrl.indexOf(normUrl) === 0 || normUrl.indexOf(sourceNormUrl) === 0;
            });
            return matches[0] || null;
        },
        activateLayersByName: function(source, names, keepCurrentActive, activateRoot) {
            if (names.indexOf('_all') !== -1) {
                this.mbMap.model.changeLayerState(source, {layers: {}}, true);
            } else {
                // translate layer-name-based mapping to id mapping understood by Model
                var matchedNames = [];
                var layerOptionMap = {};
                var layerOn = this._layerOptionsOn;
                Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, offset, parents) {
                    if (names.indexOf(layer.options.name) !== -1) {
                        matchedNames.push(layer.options.name);
                        layerOptionMap[layer.options.id] = layerOn;
                    } else if (activateRoot && !parents.length) {
                        layerOptionMap[layer.options.id] = layerOn;
                    }
                });
                if (matchedNames.length !== names.length) {
                    console.warn("Declarative merge didn't find all layer names requested for activation", names, matchedNames);
                }
                this.mbMap.model.changeLayerState(source, {layers: layerOptionMap}, false, keepCurrentActive);
            }
        },
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR){
            Mapbender.error(Mapbender.trans('mb.wms.wmsloader.error.load'));
        },
        _destroy: $.noop
    });

})(jQuery);

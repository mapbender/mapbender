(function($){

    $.widget("mapbender.mbWmsloader", $.mapbender.mbDialogElement, {
        options: {
            wms_url: null
        },
        loadedSourcesCount: 0,
        elementUrl: null,
        mbMap: null,
        _layerOptionsOn: {options: {treeOptions: {selected: true}}},
        _layerOptionsOff: {options: {treeOptions: {selected: false}}},
        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
                self._trigger('ready');
            }, function() {
                Mapbender.checkTarget('mbWmsloader');
            });
        },
        _setup: function(){
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            var queryParams = Mapbender.Util.getUrlQueryParams(window.location.href);
            Mapbender.declarative = Mapbender.declarative || {};
            Mapbender.declarative['source.add.wms'] = $.proxy(this.loadDeclarativeWms, this);
            if (queryParams.wms_url) {
                // Fold top-level "VERSION=" and "SERVICE=" onto url (case insensitive)
                var wmsUrl = this._fixWmsUrl(queryParams.wms_url, queryParams);
                this.loadWms(wmsUrl);
            }

            if (queryParams.wms_id) {
                this._getInstances(queryParams.wms_id);
            }

            if (Mapbender.ElementUtil.checkDialogMode(this.element)) {
                if (this.checkAutoOpen()) {
                    this.open();
                }
                // Button is added via the popup constructor in dialog mode
                this.element.find('.-js-submit').remove();
            } else {
                this.element.find('.-js-submit').on('click', this._submit.bind(this));
            }
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup({
                    title: this.element.attr('data-title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: true,
                    content: this.element,
                    detachOnClose: false,
                    width: 500,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.add'),
                            cssClass: 'btn btn-sm btn-primary',
                            attrDataTest: 'mb-wms-btn-add',
                            callback: this._submit.bind(this),
                        },
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'btn btn-sm btn-light popupClose',
                            attrDataTest: 'mb-wms-btn-close',
                        }
                    ]
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            } else {
                this.popup.$element.removeClass('d-none');
                this.popup.focus();
            }

            this.notifyWidgetActivated();
        },
        _submit: function(e) {
            if (e) e.preventDefault();
            var form = this.element.find('form').get(0);
            if (form.reportValidity && !form.reportValidity()) return;
            var url = $('input[name="loadWmsUrl"]', this.element).val();
            if (url === '') {
                $('input[name="loadWmsUrl"]', this.element).focus();
                return false;
            }
            var urlObj = new Mapbender.Util.Url(url);
            urlObj.username = $('input[name="loadWmsUser"]', this.element).val();
            urlObj.password = $('input[name="loadWmsPass"]', this.element).val();
            this.loadWms(urlObj.asString());
            if (this.popup) this.close();
        },
        close: function(){
            if (this.popup && this.popup.$element) {
                this.popup.$element.addClass('d-none');
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            this.notifyWidgetDeactivated();
        },
        loadDeclarativeWms: function(elm){
            const layers = elm.attr('data-mb-wms-layers') || elm.attr('mb-wms-layers');
            const merge = elm.attr('data-mb-wms-merge') || elm.attr('mb-wms-merge');
            var layerNamesToActivate = (layers && layers.split(',')) || ['_all'];
            var mergeSource = !merge || merge === '1';
            var sourceUrl = elm.attr('data-mb-url') || elm.attr('mb-url') || elm.attr('href');
            var infoFormat = elm.attr('data-mb-infoformat') || elm.attr('mb-infoformat') || 'text/html';
            var customParams = this.parseCustomParams(elm);

            var source = mergeSource && this.mergeDeclarative(elm, sourceUrl, layerNamesToActivate);
            if (source && typeof (source.addParams) === 'function') {
                source.addParams(customParams);
            } else {
                this.loadWms(sourceUrl, {
                    layers: layerNamesToActivate,
                    // Default other layers (=not passed in via mb-wms-layers) to off, as per documentation
                    keepOtherLayerStates: false,
                    infoFormat : infoFormat
                }).then(function(source) {
                    if (typeof (source.addParams) === 'function') {
                        source.addParams(customParams);
                    }
                });
            }
        },
        /**
         * @param {jQuery} $link
         * @param {string} sourceUrl
         * @param {Array<string>|false} layerNamesToActivate
         * @return {(Mapbender.Source)|null} to indicate if a merge target was found and processed
         */
        mergeDeclarative: function($link, sourceUrl, layerNamesToActivate) {
            // NOTE: The evaluated attribute name has always been 'mb-wms-layer-merge', but documenented name
            //       was 'mb-layer-merge'. Just support both equivalently.
            var mergeLayersAttribValue = $link.attr('data-mb-wms-layer-merge') || $link.attr('data-mb-layer-merge')
                || $link.attr('mb-wms-layer-merge') || $link.attr('mb-layer-merge');
            var keepCurrentActive = !mergeLayersAttribValue || (mergeLayersAttribValue === '1');
            var mergeCandidate = this._findMergeCandidateByUrl(sourceUrl);
            if (mergeCandidate) {
                this.activateLayersByName(mergeCandidate, layerNamesToActivate, keepCurrentActive);
                // NOTE: With no explicit layers to modify via mb-wms-layers, none of the other
                //       attributes and config params matter. We leave the source alone completely.
            }
            return mergeCandidate;
        },
        /**
         * @param {String} url
         * @param {Object} [options]
         * @property {Array<String>} [options.layers]
         * @property {boolean} [options.keepOtherLayerStates]
         */
        loadWms: function (url, options) {
            var self = this;
            return $.ajax({
                url: self.elementUrl + 'loadWms',
                data: {
                    url: url,
                    infoFormat: options?.infoFormat
                },
                dataType: 'json',
                error: function(jqXHR, textStatus, errorThrown){
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            }).then(function(data) {
                return self._addSources(data, options || {});
            }).fail((e) => Mapbender.handleAjaxError(e, () => this.loadWms(url, options), Mapbender.trans('mb.wms.wmsloader.error.load')));
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
                        if (!self.mbMap.model.getSourceById(sourceDef.id)) {
                            self.mbMap.model.addSourceFromConfig(sourceDef);
                        }
                    });
                }
            }).fail((e) => Mapbender.handleAjaxError(e, () => this._getInstances(scvIds), Mapbender.trans('mb.wms.wmsloader.error.load')));
        },
        /**
         * @param {Array<Object>} sourceDefs
         * @param {Object} options
         * @property {Array<String>} [options.layers]
         * @property {boolean} [options.keepOtherLayerStates]
         */
        _addSources: function(sourceDefs, options) {
            var keepStates = options.keepOtherLayerStates || (typeof (options.keepOtherLayerStates) === 'undefined');
            var srcIdPrefix = 'wmsloader-' + $(this.element).attr('id');
            var source;
            for (var i = 0; i < sourceDefs.length; ++i) {
                var sourceDef = sourceDefs[i];
                sourceDef.id = srcIdPrefix + '-' + (this.loadedSourcesCount++);
                // Need to pre-generate layer ids now because layertree visual updates need layer ids
                Mapbender.Util.SourceTree.generateLayerIds(sourceDef);
                sourceDef.isDynamicSource = true;
                if (options.hasOwnProperty('layers')) {
                    // deactivate root layer, when no layer is selected
                    if (options.layers.length === 0) {
                        sourceDef.children[0].options.treeOptions.selected = false;
                    }
                    sourceDef.children[0].children.forEach(layer => {
                        var allActive = options.layers.indexOf('_all') !== -1;
                        layer.options.treeOptions.selected = (options.layers.indexOf(layer.options.name) !== -1) || allActive;
                    });
                }
                source = source || this.mbMap.model.addSourceFromConfig(sourceDef);
            }
            return source || null;
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
                if (!source.options.url) {
                    // no static url (e.g. WMTS instance) => cannot process further
                    return false;
                }
                var sourceNormUrl = normalizeUrl(source.options.url);
                return sourceNormUrl.indexOf(normUrl) === 0 || normUrl.indexOf(sourceNormUrl) === 0;
            });
            return matches[0] || null;
        },
        activateLayersByName: function(source, names, keepCurrentActive) {
            var activateAll = names.indexOf('_all') !== -1;
            var matchedNames = [];
            if (!keepCurrentActive && !activateAll) {
                // Deactivate all non-root layers before activating only the required ones
                Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, offset, parents) {
                    var isRootLayer = !parents.length;
                    if (!isRootLayer) {
                        layer.options.treeOptions.selected = false;
                    }
                });
            }
            // always activate root layer
            var rootLayer = source.getRootLayer();
            rootLayer.options.treeOptions.selected = rootLayer.options.treeOptions.allow.selected;
            Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer, offset, parents) {
                var doActivate = activateAll;
                if (names.indexOf(layer.options.name) !== -1 || activateAll) {
                    matchedNames.push(layer.options.name);
                    doActivate = true;
                }
                if (doActivate) {
                    layer.options.treeOptions.selected = layer.options.treeOptions.allow.selected;
                    layer.options.treeOptions.info = layer.options.treeOptions.allow.info;
                    // also activate parent layers
                    for (var p = 0; p < parents.length; ++p) {
                        parents[p].options.treeOptions.selected = layer.options.treeOptions.allow.selected;
                    }
                }

            });
            if (!activateAll && matchedNames.length !== names.length) {
                console.warn("Declarative merge didn't find all layer names requested for activation", names, matchedNames);
            }
            Mapbender.Model.updateSource(source);
        },
        _fixWmsUrl: function(baseUrl, defaultParams) {
            var extraParams = {};
            var extraParamNames = ['VERSION', 'SERVICE', 'REQUEST'];
            var existingParams = Mapbender.Util.getUrlQueryParams(baseUrl);
            var existingParamNames = Object.keys(existingParams).map(function(name) {
                return name.toUpperCase();
            });
            Object.keys(defaultParams).forEach(function(prop) {
                var ucName = prop.toUpperCase();
                if (-1 !== extraParamNames.indexOf(ucName) && -1 === existingParamNames.indexOf(ucName)) {
                    extraParams[ucName] = defaultParams[prop];
                }
            });
            return Mapbender.Util.replaceUrlParams(baseUrl, extraParams, true);
        },
        /**
         * @param {jQuery} $element
         * @return {Object<String, String>}
         */
        parseCustomParams: function($element) {
            var customParams = {};
            ($element.attr('data-mb-add-vendor-specific') || $element.attr('mb-add-vendor-specific') || '').split(/[&?]/).forEach(function(assignment) {
                var match = assignment && assignment.match(/^(.*?)(?:=(.*))?$/);
                if (match && match[1]) {
                    var key = decodeURIComponent(match[1]);
                    var val = match[2] && decodeURIComponent(match[2]) || key;
                    customParams[key] = val;
                }
            });
            return customParams;
        },
        _destroy: $.noop
    });

})(jQuery);

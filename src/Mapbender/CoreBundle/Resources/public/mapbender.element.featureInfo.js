(function($) {

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            autoActivate: false,
            deactivateOnClose: true,
            displayType: 'tabs',
            printResult: false,
            onlyValid: false,
            highlighting: false,
            fillColorDefault: '#ffa500',
            fillColorHover: 'ff0000',
            maxCount: 100,
            width: 700,
            height: 500
        },
        mbMap: null,
        popup: null,
        contentManager: null,
        mobilePane: null,
        isActive: false,
        highlightLayer: null,
        showingSources: [],
        template: {
            header: null,
            content: null
        },
        iframeScriptContent_: '',

        _create: function() {
            this.iframeScriptContent_ = $('.-js-iframe-script-template[data-script]', this.element).remove().attr('data-script');
            this.mobilePane = this.element.closest('.mobilePane');
            this.template = {
                header: $('.js-header', this.element).remove(),
                content: $('.js-content', this.element).remove()
            };

            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbFeatureInfo");
            });

        },


        _setup: function(mbMap) {
            var widget = this;
            var options = widget.options;
            this.mbMap = mbMap;
            this._setupMapClickHandler();
            if (options.autoActivate || options.autoOpen) { // autoOpen old configuration
                widget.activate();
            }

            if (Mapbender.mapEngine.code !== 'ol2' && options.highlighting) {

                this._createLayerStyle();

                this.highlightLayer = new ol.layer.Vector({
                    source: new ol.source.Vector({}),
                    name: 'featureInfo',
                    style: widget.featureInfoStyle,
                    visible: true,
                    minResolution: Mapbender.Model.scaleToResolution(0),
                    maxResolution: Mapbender.Model.scaleToResolution(Infinity)
                });

                this.mbMap.getModel().olMap.addLayer(this.highlightLayer);
                window.addEventListener("message", function(message) {
                    widget._postMessage(message);
                });
                this._createHighlightControl();
            }

            widget._trigger('ready');
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.activate(callback);
        },
        activate: function(callback) {
            this.callback = callback;
            this.mbMap.element.addClass('mb-feature-info-active');
            this.isActive = true;
        },
        deactivate: function() {
            this.mbMap.element.removeClass('mb-feature-info-active');
            this.isActive = false;
            this.clearAll();

            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
        },
        /**
         * Trigger the Feature Info call for each layer.
         * Also set up feature info dialog if needed.
         */
        _triggerFeatureInfo: function(x, y) {
            if (!this.isActive) {
                return;
            }
            var self = this, i;
            var model = this.mbMap.getModel();
            var showingPreviously = this.showingSources.slice();
            this.showingSources.splice(0);  // clear
            var sourceUrlPairs = model.getSources().map(function(source) {
                var url = model.getPointFeatureInfoUrl(source, x, y, self.options.maxCount);
                return url && {
                    source: source,
                    url: url
                };
            }).filter(function(x) { return !!x; });
            var validSources = sourceUrlPairs.map(function(sourceUrlEntry) {
                return sourceUrlEntry.source;
            });
            for (i = 0; i < showingPreviously.length; ++i) {
                if (-1 === validSources.indexOf(showingPreviously[i])) {
                    this._removeContent(showingPreviously[i]);
                }
            }
            var requestsPending = sourceUrlPairs.length;
            if (!requestsPending) {
                self._handleZeroResponses();
            }
            sourceUrlPairs.forEach(function(entry) {
                var source = entry.source;
                var url = entry.url;
                self._setInfo(source, url).then(function(success) {
                    if (success) {
                        self.showingSources.push(source);
                        self._open();
                    } else {
                        self._removeContent(source);
                    }
                }, function() {
                    self._removeContent(source);
                }).always(function() {
                    --requestsPending;
                    if (!requestsPending && !self.showingSources.length) {
                        // No response content to display, no more requests pending
                        // Remain active, but hide popup
                        self._handleZeroResponses();
                    }
                });
            });
        },
        _setInfo: function(source, url) {
            var self = this;
            var layerTitle = source.getTitle();
            var ajaxOptions = {
                url: url
            };
            var useProxy = source.configuration.options.proxy;
            // also use proxy on different host / scheme to avoid CORB
            useProxy |= !Mapbender.Util.isSameSchemeAndHost(url, window.location.href);
            if (useProxy && !source.configuration.options.tunnel) {
                ajaxOptions.data = {
                    url: ajaxOptions.url
                };
                ajaxOptions.url = Mapbender.configuration.application.urls.proxy;
            }
            var request = $.ajax(ajaxOptions).then(function (data, textStatus, jqXHR) {
                var data_ = data;
                var mimetype = jqXHR.getResponseHeader('Content-Type').toLowerCase().split(';')[0];
                data_ = $.trim(data_);
                if (data_.length && (!self.options.onlyValid || self._isDataValid(data_, mimetype))) {
                    self._showOriginal(source, layerTitle, data_, mimetype);
                    // Bind original url for print interaction
                    var $documentNode = self._getDocumentNode(source.id);
                    $documentNode.attr('data-url', url);
                    return true;
                }
            }, function (jqXHR, textStatus, errorThrown) {
                Mapbender.error(layerTitle + ' GetFeatureInfo: ' + errorThrown);
            });
            return request;
        },
        _isDataValid: function(data, mimetype) {
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    return !!("" + data).match(/<[/][a-z]+>/gi);
                case 'text/plain':
                    return !!("" + data).match(/[^\s]/g);
                default:
                    return true;
            }
        },
        _showOriginal: function(source, layerTitle, data, mimetype) {
            var self = this;
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    var script = self._getInjectionScript(source.id);
                    var iframe = $('<iframe sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox allow-downloads">');
                    iframe.attr("srcdoc",script+data);
                    self._addContent(source, layerTitle, iframe);
                    break;
                case 'text/plain':
                default:
                    this._addContent(source, layerTitle, '<pre>' + data + '</pre>');
                    break;
            }
        },
        _getContentManager: function() {
            if (!this.contentManager) {
                this.contentManager = {
                    $headerParent: $('.js-header-parent', this.element),
                    $header: this.template.header,
                    headerContentSel: '.js-header-content',
                    headerId: function (id) {
                        return this.$header.attr('data-idname') + id
                    },
                    $contentParent: $('.js-content-parent', this.element),
                    $content: this.template.content,
                    contentContentSel: '.js-content-content',
                    contentId: function (id) {
                        return this.$content.attr('data-idname') + id
                    }
                };
            }
            return this.contentManager;
        },
        _open: function() {
            $(document).trigger('mobilepane.switch-to-element', {
                element: this.element
            });
            var widget = this;
            var options = widget.options;
            if (!this.mobilePane.length) {
                if (!widget.popup || !widget.popup.$element) {
                    if (this.highlightLayer) {
                        this.highlightLayer.getSource().clear();
                    }
                    widget.popup = new Mapbender.Popup2({
                        title: widget.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeOnESC: false,
                        detachOnClose: false,
                        content: this.element,
                        resizable: true,
                        cssClass: 'featureinfoDialog',
                        width: options.width,
                        height: options.height,
                        buttons: this._getPopupButtonOptions()
                    });
                    widget.popup.$element.on('close', function () {
                        widget._close();
                    });
                }
                widget.popup.$element.show();
            }
        },
        _hide: function() {
            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
        },
        _close: function() {
            if (this.options.deactivateOnClose) {
                this.deactivate();
            } else {
                this._hide();
            }
        },
        _handleZeroResponses: function() {
            // @todo mobile-style display: no popup, cannot hide popup; show placeholder text instead
            this._hide();
        },
        /**
         * @returns {Array<Object>}
         */
        _getPopupButtonOptions: function() {
            var buttons = [{
                label: Mapbender.trans('mb.actions.close'),
                cssClass: 'button critical popupClose'
            }];
            if (this.options.printResult) {
                var self = this;
                buttons.unshift({
                    label: Mapbender.trans('mb.actions.print'),
                    // both buttons float right => will visually appear in reverse dom order, Print first
                    cssClass: 'button',
                    callback: function () {
                        self._printContent();
                    }
                });
            }
            return buttons;
        },
        _selectorSelfAndSub: function(idStr, classSel) {
            return '#' + idStr + classSel + ',' + '#' + idStr + ' ' + classSel;
        },
        _removeContent: function(source) {
            $('[data-source-id="' + source.id + '"]', this.element).remove();
            this._removeFeaturesBySourceId(source.id);
            // If there are tabs / accordions remaining, ensure at least one of them is active
            var $container = $('.tabContainer,.accordionContainer', this.element);
            if (!$('.active', $container).length) {
                $('>.tabs .tab, >.accordion', $container).first().click();
            }
         },
        clearAll: function() {
            // Must call getContentManager at least once to "save" template markup
            // @todo: get rid of contentManager to fix all remaining DOM state races
            this._getContentManager();
            if (this.highlightLayer) {
                this.highlightLayer.getSource().clear();
            }
            $('>.accordionContainer', this.element).empty();
            $('>.tabContainer > .tabs', this.element).empty();
            $('>.tabContainer > :not(.tabs)', this.element).remove();
            this.showingSources.splice(0);
        },
        _getDocumentNode: function(sourceId) {
            // @todo: get rid of the content manager
            return $('#' + this._getContentManager().contentId(sourceId), this.element);
        },
        _addContent: function(source, layerTitle, content) {
            var manager = this._getContentManager();
            var headerId = manager.headerId(source.id);
            var contentId = manager.contentId(source.id);
            var $header = $('#' + headerId, this.element);
            if ($header.length === 0) {
                $header = manager.$header.clone();
                $header.attr('id', headerId);
                $header.attr('data-source-id', source.id);
                manager.$headerParent.append($header);
            }
            if (!$('>.active', $header.closest('.tabContainer,.accordionContainer')).length) {
                $header.addClass('active');
            }
            $(this._selectorSelfAndSub(headerId, manager.headerContentSel), this.element).text(layerTitle);
            var $content = $('#' + contentId, this.element);
            if ($content.length === 0) {
                $content = manager.$content.clone();
                $content.attr('id', contentId);
                $content.attr('data-source-id', source.id);
                manager.$contentParent.append($content);
            }
            $content.toggleClass('active', $header.hasClass('active'));
            $(this._selectorSelfAndSub(contentId, manager.contentContentSel), this.element)
                .empty().append(content);
            initTabContainer(this.element);
        },
        _printContent: function() {
            var $documentNode = $('.js-content.active', this.element);
            var url = $documentNode.attr('data-url');
            // Always use proxy. Calling window.print on a cross-origin window is not allowed.
            var proxifiedUrl = Mapbender.configuration.application.urls.proxy + '?' + $.param({url: url});
            var w = window.open(proxifiedUrl, 'title', "attributes,scrollbars=yes,menubar=yes");
            w.print();
        },
        _setupMapClickHandler: function () {
            var self = this;
            $(document).on('mbmapclick', function (event, data) {
                self._triggerFeatureInfo(data.pixel[0], data.pixel[1]);
            });
        },
        _createLayerStyle: function () {
            var settingsDefault = {
                fill: this.options.fillColorDefault,
                stroke: this.options.strokeColorDefault || this.options.fillColorDefault,
                opacity: this.options.opacityDefault,
                fallbackOpacity: 0.7
            };
            var settingsHover = {
                fill: this.options.fillColorHover || settingsDefault.fill,
                stroke: this.options.strokeColorHover || this.options.fillColorHover || settingsDefault.stroke,
                opacity: this.options.opacityHover,
                fallbackOpacity: 0.4
            };
            this.featureInfoStyle = this.processStyle_(settingsDefault);
            this.featureInfoStyle_hover = this.processStyle_(settingsHover);
            this.featureInfoStyle_hover.setZIndex(1);
        },
        processStyle_: function(settings) {
            var fillRgb = Mapbender.StyleUtil.parseCssColor(settings.fill).slice(0, 3);
            var strokeRgb = Mapbender.StyleUtil.parseCssColor(settings.stroke).slice(0, 3);
            var opacityFloat = parseFloat(settings.opacity);
            if (!isNaN(opacityFloat)) {
                if (!(opacityFloat >= 0.0 && opacityFloat < 1.0)) {
                    // Percentage to [0;1]
                    opacityFloat /= 100.0;
                }
                opacityFloat = Math.min(Math.max(opacityFloat, 0.0), 1.0);
            } else {
                opacityFloat = settings.fallbackOpacity;
            }
            var strokeOpacity = Math.sqrt(opacityFloat);
            return new ol.style.Style({
                fill: new ol.style.Fill({
                    color: fillRgb.concat(opacityFloat)
                }),
                stroke: new ol.style.Stroke({
                    color: strokeRgb.concat(strokeOpacity)
                })
            });
        },
        _postMessage: function(message) {
            var widget = this;
            var data = message.data;
            if (data.elementId !== this.element.attr('id')) {
                return;
            }
            if (this.isActive && this.highlightLayer && data.command === 'features') {
                widget._populateFeatureInfoLayer(data);
            }
            if (this.isActive && this.highlightLayer && data.command === 'hover') {
                var feature = this.highlightLayer.getSource().getFeatureById(data.id);
                if (feature) {
                    if (data.state) {
                        feature.setStyle(this.featureInfoStyle_hover);
                    } else {
                        feature.setStyle(null);
                    }
                }
            }
        },
        _populateFeatureInfoLayer: function (data) {
            var features = (data.features || []).map(function(featureData) {
                var feature = Mapbender.Model.parseWktFeature(featureData.wkt, featureData.srid);
                feature.setId(featureData.id);
                feature.set('sourceId', data.sourceId);
                return feature;
            });

            this._removeFeaturesBySourceId(data.sourceId);
            this.highlightLayer.getSource().addFeatures(features);
        },
        _removeFeaturesBySourceId: function(sourceId) {
            if (this.highlightLayer) {
                var source = this.highlightLayer.getSource();
                var features = source.getFeatures().filter(function(feature) {
                    return feature.get('sourceId') === sourceId;
                });
                features.forEach(function(feature) {
                    source.removeFeature(feature);
                });
            }
        },
        _createHighlightControl: function() {

            var widget = this;

            var highlightControl = new ol.interaction.Select({
                condition: ol.events.condition.pointerMove,
                layers: [this.highlightLayer],
                multi: true
            });

            highlightControl.on('select', function (e) {
                e.deselected.forEach(function (feature) {
                    feature.setStyle(null);
                });
                e.selected.forEach(function (feature) {
                    feature.setStyle(widget.featureInfoStyle_hover);
                });
            });

            this.mbMap.getModel().olMap.addInteraction(highlightControl);
            highlightControl.setActive(true);
        },
        _getInjectionScript: function(sourceId) {
            var parts = [
                '<script>',
                // Hack to prevent DOMException when loading jquery
                'var replaceState = window.history.replaceState;',
                'window.history.replaceState = function(){ try { replaceState.apply(this,arguments); } catch(e) {} };',
                // Highlighting support (generate source-scoped feature ids)
                ['var sourceId = "', sourceId, '";'].join(''),
                ['var elementId = ', JSON.stringify(this.element.attr('id')) , ';'].join(''),
                this.iframeScriptContent_,
                '</script>'
            ];
            return parts.join('');
        }
    });

})(jQuery);

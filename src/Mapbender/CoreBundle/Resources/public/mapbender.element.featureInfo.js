(function($) {

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            target: null,
            autoActivate: false,
            deactivateOnClose: true,
            displayType: 'tabs',
            printResult: false,
            onlyValid: false,
            iframeInjection: null,
            highlighting: false,
            maxCount: 100,
            width: 700,
            height: 500
        },
        target: null,
        model: null,
        popup: null,
        context: null,
        state: null,
        contentManager: null,
        mobilePane: null,
        isActive: false,
        highlightLayer: null,



        _create: function() {
            this.mobilePane = this.element.closest('.mobilePane');
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbFeatureInfo", self.options.target);
            });

        },


        _setup: function(mbMap) {
            var widget = this;
            var options = widget.options;
            this.target = mbMap;
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

                this.target.map.olMap.addLayer(this.highlightLayer);
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
            this.target.element.addClass('mb-feature-info-active');
            this.isActive = true;
        },
        deactivate: function() {
            this.target.element.removeClass('mb-feature-info-active');
            this.isActive = false;
            if (this.highlightLayer) {
                this.highlightLayer.getSource().clear();
            }

            if (this.popup) {
                if (this.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    this.popup.destroy();
                }
                this.popup = null;
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
            var self = this;
            var model = this.target.getModel();
            $.each(model.getSources(), function (idx, src) {
                var url = model.getPointFeatureInfoUrl(src, x, y, self.options.maxCount);
                if (url) {
                    self._setInfo(src, url);
                }
            });
        },
        _getTabTitle: function(source) {
            // @todo: Practically the last place that uses the instance title. Virtually everywhere else we use the
            //  title of the root layer. This should be made consistent one way or the other.
            return source.configuration.title;
        },
        _setInfo: function(source, url) {
            var self = this;
            var layerTitle = this._getTabTitle(source);
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
            var request = $.ajax(ajaxOptions);
            request.done(function (data, textStatus, jqXHR) {
                var data_ = data;
                var mimetype = jqXHR.getResponseHeader('Content-Type').toLowerCase().split(';')[0];
                data_ = $.trim(data_);
                if (!data_.length || (self.options.onlyValid && !self._isDataValid(data_, mimetype))) {
                    Mapbender.info(layerTitle + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    self._removeContent(source);
                } else {
                    self._showOriginal(source, layerTitle, data_, mimetype);
                    // Bind original url for print interaction
                    var $documentNode = self._getDocumentNode(source.id);
                    $documentNode.attr('data-url', url);
                    self._open();
                }
            });
            request.fail(function (jqXHR, textStatus, errorThrown) {
                Mapbender.error(layerTitle + ' GetFeatureInfo: ' + errorThrown);
                self._removeContent(source);
            });
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
                    var iframe = $('<iframe sandbox="allow-scripts">');
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
                    headerSel: '.js-header',
                    $headerParent: $('.js-header', this.element).parent(),
                    $header: $('.js-header', this.element).remove(),
                    headerContentSel: '.js-header-content',
                    headerId: function (id) {
                        return this.$header.attr('data-idname') + id
                    },
                    contentSel: '.js-content',
                    $contentParent: $('.js-content', this.element).parent(),
                    $content: $('.js-content', this.element).remove(),
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
                    widget.popup = new Mapbender.Popup2({
                        title: widget.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeOnESC: false,
                        detachOnClose: false,
                        content: widget.element.removeClass('hidden'),
                        resizable: true,
                        cssClass: 'featureinfoDialog',
                        width: options.width,
                        height: options.height,
                        buttons: this._getPopupButtonOptions()
                    });
                    widget.popup.$element.on('close', function () {
                        if (widget.options.deactivateOnClose) {
                            widget.deactivate();
                        }
                        if (widget.popup && widget.popup.$element) {
                            widget.popup.$element.hide();
                        }
                        widget.state = 'closed';
                    });
                    widget.popup.$element.on('open', function () {
                        widget.state = 'opened';
                    });
                }
                if (widget.state !== 'opened') {
                    if (this.highlightLayer) {
                        this.highlightLayer.getSource().clear();
                    }
                    widget.popup.open();
                }

                if (widget.popup && widget.popup.$element) {
                    widget.popup.$element.show();
                }
            }
        },
        /**
         * @returns {Array<Object>}
         */
        _getPopupButtonOptions: function() {
            var buttons = [{
                label: Mapbender.trans('mb.actions.close'),
                cssClass: 'button buttonCancel critical right',
                callback: function () {
                    /** @this Mapbender.Popup2 */
                    this.close();
                }
            }];
            if (this.options.printResult) {
                var self = this;
                buttons.push({
                    label: Mapbender.trans('mb.actions.print'),
                    // both buttons float right => will visually appear in reverse dom order, Print first
                    cssClass: 'button right',
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
            var manager = this._getContentManager();
            $('#' + manager.headerId(source.id), this.element).remove();
            $(this._selectorSelfAndSub(manager.contentId(source.id), manager.contentContentSel), this.element).remove();
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
            self.target.element.on('mbmapclick', function (event, data) {
                self._triggerFeatureInfo(data.pixel[0], data.pixel[1]);
            });
        },

        _createLayerStyle: function () {
            this.featureInfoStyle = function (feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255, 255, 255, 1)',
                        lineCap: 'round',
                        width: 1
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 165, 0, 0.4)'
                    })
                });
            };

            this.featureInfoStyle_hover = function (feature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: 'rgba(255, 255, 255, 1)',
                        lineCap: 'round',
                        width: 1
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 0, 0, 0.7)'
                    }),
                    zIndex: 1000
                });
            }
        },

        _postMessage: function(message) {
            var widget = this;
            var data = message.data;
            if (data.elementId !== this.element.attr('id')) {
                return;
            }
            if (this.isActive && this.highlightLayer && data.ewkts && data.ewkts.length) {
                widget._populateFeatureInfoLayer(data.ewkts);
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
        _populateFeatureInfoLayer: function (ewkts) {
            var features = ewkts.map(function (ewkt) {
                var feature = Mapbender.Model.parseWktFeature(ewkt.wkt, ewkt.srid);
                feature.setId(ewkt.id);
                return feature;
            });

            this.highlightLayer.getSource().addFeatures(features);
        },

        _createHighlightControl: function() {

            var widget = this;
            var map = this.target.map.olMap;

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

            map.addInteraction(highlightControl);
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
                this.options.iframeInjection,
                '</script>'
            ];
            return parts.join('');
        }
    });

})(jQuery);

(function($) {

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            target: null,
            autoActivate: false,
            deactivateOnClose: true,
            displayType: 'tabs',
            printResult: false,
            showOriginal: false,
            onlyValid: false,
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

            window.addEventListener("message", widget._postMessage.bind(widget));
            if (Mapbender.mapEngine.code !== 'ol2' && options.highlightLayer) {

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
                this._createHighlightControl();
            }

            widget._trigger('ready');
        },
        _contentElementId: function(source) {
            var id = this._getContentManager().contentId(source.id);
            // verify element is in DOM
            if ($('#' + id, this.element).length) {
                return id;
            }
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
                if (!self.options.showOriginal && mimetype.search(/text[/]html/i) === 0) {
                    data_ = self._cleanHtml(data_);
                }
                data_ = $.trim(data_);
                if (!data_.length || (self.options.onlyValid && !self._isDataValid(data_, mimetype))) {
                    Mapbender.info(layerTitle + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    self._removeContent(source);
                } else {
                    if (self.options.showOriginal) {
                        self._showOriginal(source, layerTitle, data_, mimetype);
                    } else {
                        self._showEmbedded(source, layerTitle, data_, mimetype);
                    }
                    self._triggerHaveResult(source);
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
        _triggerHaveResult: function(source) {
            // only used for mobile hacks
            // @todo: add mobile hacks here, remove event
            var eventData = {
                action: "haveresult",
                content: this._contentElementId(source),
                source: source,
                id: this.element.attr('id')
            };
            this._trigger('featureinfo', null, eventData);
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
        _showEmbedded: function(source, layerTitle, data, mimetype) {
            this._addContent(source, layerTitle, data);
        },
        _cleanHtml: function(data) {
            try {
                if (data.search(/<link/i) > -1 || data.search(/<style/i) > -1 || data.search(/<script/i) > -1) {
                    return data.replace(/document.writeln[^;]*;/g, '')
                        .replace(/\n|\r/g, '')
                        .replace(/<!--[^>]*-->/g, '')
                        .replace(/<link[^>]*>/gi, '')
                        .replace(/<meta[^>]*>/gi, '')
                        .replace(/<title>*(?:[^<]*<\/title>)/gi, '')
                        .replace(/<style[^>]*(?:[^<]*<\/style>|>)/gi, '')
                        .replace(/<script[^>]*(?:[^<]*<\/script>|>)/gi, '');
                }
            } catch (e) {
            }
            return data;
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
            var w = window.open("", "title", "attributes,scrollbars=yes,menubar=yes");
            var el = $('.js-content-content.active,.active .js-content-content', this.element);
            var printContent;
            var iframe = $('iframe', el).get(0);
            if (iframe) {
                printContent = iframe.contentWindow.document.documentElement.innerHTML;
            } else {
                printContent = el.html();
            }
            w.document.write(printContent);
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
            var feature;
            if (this.highlightLayer && data.ewkts && data.ewkts.length) {
                widget._populateFeatureInfoLayer(data.ewkts);
            }
            if (this.highlightLayer && data.command === 'hover') {
                feature = this.highlightLayer.getSource().getFeatureById(data.id);
                if (feature) {
                    if (data.state) {
                        feature.setStyle(this.featureInfoStyle_hover);
                    } else {
                        feature.setStyle(null);
                    }
                }
            }
            if (data.actionValue && data.element) {
                if(Mapbender.declarative && Mapbender.declarative[data.actionValue] && typeof Mapbender.declarative[data.actionValue] === 'function') {
                    // Method to simulate attribute access has to be inserted within Mapbender's frame to prevent clone errors on postMessage
                    data.element.attr = function(val) {
                        return this.attributes[val];
                    };
                    Mapbender.declarative[data.actionValue](data.element);
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

        _getInjectionScript: function(source_id) {
            var script = `<script>
                            // Hack to prevent DOMException when loading jquery
                            var replaceState = window.history.replaceState;
                            window.history.replaceState = function(){ try { replaceState.apply(this,arguments); } catch(e) {} };
                            document.addEventListener('DOMContentLoaded', function() {
                                if (document.readyState === 'interactive' || document.readyState === 'complete' ) {

                                    var origin = '*';
                                    var nodes = document.querySelectorAll('[data-geometry]') || [];
                                    var ewkts = Array.from(nodes).map(function (node) {
                                        return {
                                            srid: node.getAttribute('data-srid'),
                                            wkt: node.getAttribute('data-geometry'),
                                            id: ${source_id}+'-'+node.getAttribute('id'),
                                        };
                                    });
                                    Array.from(nodes).forEach(function (node) {
                                        node.addEventListener('mouseover', function (event) {
                                            var id = ${source_id}+'-'+node.getAttribute('id');
                                            window.parent.postMessage({ command: 'hover', state: true, id: id },origin);
                                        });
                                        node.addEventListener('mouseout', function (event) {
                                            var id = ${source_id}+'-'+node.getAttribute('id');
                                            window.parent.postMessage({ command: 'hover', state: false, id: id },origin);
                                        });
                                    });
                                    window.parent.postMessage({ ewkts :  ewkts }, origin);

                                    var mbActionLinks = document.querySelectorAll("[mb-action]");
                                    mbActionLinks.forEach(function(actionLink) {
                                        actionLink.addEventListener('click',  function(e) {
                                            var element= e.target;
                                            var actionValue = element.getAttribute('mb-action');
                                            var attributesMap = new Object();
                                            for (var i = 0; i < element.attributes.length; i++) {
                                                var attrib = element.attributes[i];
                                                attributesMap[attrib.name] = attrib.value;
                                            }
                                            e.preventDefault();
                                            window.parent.postMessage({
                                                actionValue: actionValue,
                                                element: {
                                                    attributes: attributesMap
                                                }
                                            },origin);
                                            return false;
                                        });
                                    });

                                }
                            });
                            </script>`;
            return script;
        }


    });

})(jQuery);

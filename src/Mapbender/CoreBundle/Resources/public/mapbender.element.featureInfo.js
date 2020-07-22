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
            if (options.autoActivate || options.autoOpen){ // autoOpen old configuration
                widget.activate();
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
            $.each(model.getSources(), function(idx, src) {
                var url = model.getPointFeatureInfoUrl(src, x, y, self.options.maxCount);
                if (url) {
                    self._setInfo(src, url);
                }
            });
        },
        _getIframeDeclaration: function() {
            return '<iframe class="featureInfoFrame"></iframe>';
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
            request.done(function(data, textStatus, jqXHR) {
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
            request.fail(function(jqXHR, textStatus, errorThrown) {
                Mapbender.error(layerTitle + ' GetFeatureInfo: ' + errorThrown);
                this._removeContent(source);
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
                title: this.element.attr('title'),
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
                    /* add a blank iframe and replace it's content (document.domain == iframe.document.domain */
                    var iframe = $(this._getIframeDeclaration());
                    self._addContent(source, layerTitle, iframe);
                    var doc = iframe.get(0).contentWindow.document;
                    iframe.on('load', function() {
                        if (Mapbender.Util.addDispatcher) {
                           Mapbender.Util.addDispatcher(doc);
                        }
                        $('body', doc).css("background", "transparent");
                    });
                    doc.open();
                    doc.write(data);
                    doc.close();
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
                    headerId: function(id) {
                        return this.$header.attr('data-idname') + id
                    },
                    contentSel: '.js-content',
                    $contentParent: $('.js-content', this.element).parent(),
                    $content: $('.js-content', this.element).remove(),
                    contentContentSel: '.js-content-content',
                    contentId: function(id) {
                        return this.$content.attr('data-idname') + id
                    }
                };
            }
            return this.contentManager;
        },
        _open: function() {
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

                        buttons: {
                            'ok': {
                                label: Mapbender.trans('mb.core.featureinfo.popup.btn.ok'),
                                cssClass: 'button buttonCancel critical right',
                                callback: function() {
                                    this.close();
                                }
                            }
                        }
                    });
                    widget.popup.$element.on('close', function() {
                        if (widget.options.deactivateOnClose) {
                            widget.deactivate();
                        }
                        if (widget.popup && widget.popup.$element) {
                            widget.popup.$element.hide();
                        }
                        widget.state = 'closed';
                    });
                    widget.popup.$element.on('open', function() {
                        widget.state = 'opened';
                    });
                    if (options.printResult === true) {
                        widget.popup.addButtons({
                            'print': {
                                label: Mapbender.trans('mb.core.featureinfo.popup.btn.print'),
                                cssClass: 'button right',
                                callback: function() {
                                    widget._printContent();
                                }
                            }
                        });
                    }
                }
                if(widget.state !== 'opened') {
                    widget.popup.open();
                }

                if(widget.popup && widget.popup.$element){
                    widget.popup.$element.show();
                }
            }
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
            self.target.element.on('mbmapclick', function(event, data) {
                self._triggerFeatureInfo(data.pixel[0], data.pixel[1]);
            });
        }
    });
})(jQuery);

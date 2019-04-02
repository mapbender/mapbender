(function($) {

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            target: null,
            autoActivate: false,
            deactivateOnClose: true,
            type: 'dialog',
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
        mapClickHandler: null,
        popup: null,
        context: null,
        queries: {},
        state: null,
        contentManager: null,

        _create: function() {
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
            var widgetElement = widget.element;
            this.target = mbMap;
            widget.mapClickHandler = new OpenLayers.Handler.Click(widget,
                {'click': widget._triggerFeatureInfo},
                {map: this.target.map.olMap});

            if (options.autoActivate || options.autoOpen){ // autoOpen old configuration
                widget.activate();
            }

            widgetElement.on('click', '.js-header', function(e) {
                $('.js-content.active:first', widgetElement).each(function(idx, item){ // only one tab is active
                    if($('iframe:first', $(item)).length){
                        function fireIfLoaded($item, num){
                            if($('iframe:first', $item).data('loaded')){
                                widget._trigger('featureinfo', null, {
                                    action: "activated_content",
                                    id: widgetElement.attr('id'),
                                    activated_content: [$item.attr('id')]
                                });
                                return;
                            }
                            if (num > 100) {
                                window.console && console.warn("FeatureInfoIframe: the content can not be loaded!");
                                return;
                            }
                            window.setTimeout(function(){
                                fireIfLoaded($item, num++);
                            }, 100);
                        }
                        fireIfLoaded($(item), 0);
                    } else {
                        widget._trigger('featureinfo', null, {
                            action: "activated_content",
                            id: widgetElement.attr('id'),
                            activated_content: [$(item).attr('id')]
                        });
                    }
                });
            });
            widget._trigger('ready');
        },
        _contentElementId: function(source) {
            // @todo: stop using mapqueryish stuff
            var id0 = source.mqlid;
            var id = this._getContentManager().contentId(id0);
            // verify element is in DOM
            if ($('#' + id, this._getContext()).length) {
                return id;
            }
        },
        _contentRef: function(layerId) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            return $('#' + manager.contentId(layerId), $context);
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
            this.mapClickHandler.activate();
        },
        deactivate: function() {
            var widget = this;
            widget._trigger('featureinfo', null, {
                action: "deactivate",
                title: this.element.attr('title'),
                id: this.element.attr('id')
            });

            this.target.element.removeClass('mb-feature-info-active');

            if (widget.popup) {
                if (widget.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    widget.popup.destroy();
                }
                widget.popup = null;
            }

            widget.mapClickHandler.deactivate();
            widget.callback ? widget.callback.call() : widget.callback = null;
        },
        _isVisible: function() {
            if (this.options.type === 'dialog') {// visible for dialog
                if (this.state && this.state === 'opened') {
                    return true;
                } else {
                    return false;
                }
            } else { // TODO visible for element ?
                return true;
            }
        },
        /**
         * Trigger the Feature Info call for each layer.
         * Also set up feature info dialog if needed.
         */
        _triggerFeatureInfo: function(e) {
            this._trigger('featureinfo', null, {
                action: "clicked",
                title: this.element.attr('title'),
                id: this.element.attr('id')
            });
            var self = this;
            var x = e.xy.x;
            var y = e.xy.y;
            var called = false;
            this.queries = {};
            $('#js-error-featureinfo').addClass('hidden');
            $.each(this.target.getModel().getSources(), function(idx, src) {
                var layerTitle = self._getTabTitle(src);
                var url = src.getPointFeatureInfoUrl(x, y, self.options.maxCount);
                if (url) {
                    self.queries[src.mqlid] = url;
                    if (!self.options.onlyValid) {
                        self._addContent(src.mqlid, layerTitle, 'wird geladen');
                        self._open();
                    }
                    called = true;
                    self._setInfo(src, url);
                } else {
                    self._removeContent(src.mqlid);
                }
            });
            if (!called) {
                $('#js-error-featureinfo').removeClass('hidden');
            }
        },
        _getIframeDeclaration: function(uuid, url) {
            var id = uuid ? (' id="' + uuid + '"') : '';
            var src = url ? (' src="' + url + '"') : '';
            return '<iframe class="featureInfoFrame"' + id + src + '></iframe>'
        },
        _getTabTitle: function(source) {
            // @todo: Practically the last place that uses the instance title. Virtually everywhere else we use the
            //  title of the root layer. This should be made consistent one way or the other.
            return source.configuration.title;
        },
        _setInfo: function(source, url) {
            var self = this;
            var layerTitle = this._getTabTitle(source);
            var contentType_ = "";
            if (typeof (source.configuration.options.info_charset) !== 'undefined') {
                contentType_ += contentType_.length > 0 ? ";"
                    : "" + source.configuration.options.info_charset;
            }
            var ajaxOptions = {
                url: url,
                contentType: contentType_
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
                var mimetype = jqXHR.getResponseHeader('Content-Type').toLowerCase().split(';')[0];
                if (self.options.showOriginal) {
                    self._showOriginal(source, layerTitle, data, mimetype);
                } else {
                    self._showEmbedded(source, layerTitle, data, mimetype);
                }
            });
            request.fail(function(jqXHR, textStatus, errorThrown) {
                Mapbender.info(layerTitle + ' GetFeatureInfo: ' + errorThrown);
                self._addContent(source.mqlid, layerTitle, errorThrown);
            });
        },
        _isDataValid: function(data, mimetype) {
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    return !!("" + data).match(/<[a-z]+>\s*[^<]+\s*<[/][a-z]+>/gi);
                case 'text/plain':
                    return !!("" + data).match(/[^\s]/g);
                default: // TODO other mimetypes ?
                    return true;
            }
        },
        _triggerHaveResult: function(source) {
            var eventData = {
                action: "haveresult",
                title: this.element.attr('title'),
                content: this._contentElementId(source),
                source: source,
                id: this.element.attr('id')
            };
            Object.defineProperty(eventData, 'mqlid', {
                enumerable: true,
                get: function() {
                    console.warn("You are accessing the legacy .mqlid property on feature info event data. Please access the also provided source object instead")
                    return this.source.mqlid;
                }
            });
            this._trigger('featureinfo', null, eventData);
        },
        _showOriginal: function(source, layerTitle, data, mimetype) {
            var self = this;
            var layerId = source.mqlid; // @todo: stop using mapquery-specific stuff
            if (this.options.onlyValid && !this._isDataValid(data, mimetype)) {
                this._removeContent(layerId);
                Mapbender.info(layerTitle + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                return;
            }
            /* handle only onlyValid=true. handling for onlyValid=false see in "_triggerFeatureInfo" */
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    /* add a blank iframe and replace it's content (document.domain == iframe.document.domain */
                    this._open();
                    var uuid = Mapbender.Util.UUID();
                    var iframe = $(self._getIframeDeclaration(uuid, null));
                    self._addContent(layerId, layerTitle, iframe);
                    var doc = document.getElementById(uuid).contentWindow.document;
                    iframe.on('load', function() {
                        if (Mapbender.Util.addDispatcher) {
                           Mapbender.Util.addDispatcher(doc);
                        }
                        iframe.data('loaded', true);
                        $('#' + self._getContentManager().headerId(layerId), self.element).click();
                        iframe.contents().find("body").css("background","transparent");
                        self._triggerHaveResult(source);
                    });
                    doc.open();
                    doc.write(data);
                    doc.close();
                    break;
                case 'text/plain':
                default:
                    this._addContent(layerId, layerTitle, '<pre>' + data + '</pre>');
                    this._triggerHaveResult(source);
                    this._open();
                    break;
            }
        },
        _showEmbedded: function(source, layerTitle, data, mimetype) {
            var layerId = source.mqlid; // @todo: stop using mapquery-specific stuff
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    var self = this;
                    data = this._cleanHtml(data);
                    if (!this.options.onlyValid || (this.options.onlyValid && this._isDataValid(data, mimetype))) {
                        this._addContent(layerId, layerTitle, data);
                        this._triggerHaveResult(source);
                        this._open();
                        $('#' + self._getContentManager().headerId(layerId), self.element).click();
                    } else {
                        this._removeContent(layerId);
                        Mapbender.info(layerTitle + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    }
                    break;
                case 'text/plain':
                default:
                    if (!this.options.onlyValid || (this.options.onlyValid && this._isDataValid(data, mimetype))) {
                        this._addContent(layerId, layerTitle, '<pre>' + data + '</pre>');
                        this._triggerHaveResult(source);
                    } else {
                        this._setContentEmpty(layerId);
                        Mapbender.info(layerTitle + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    }
                    break;
            }
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
            if (options.type === 'dialog') {
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
                                    if (widget.options.deactivateOnClose) {
                                        widget.deactivate();
                                    }
                                }
                            }
                        }
                    });
                    widget.popup.$element.on('close', function() {
                        widget._trigger('featureinfo', null, {
                            action: "closedialog",
                            title:  widget.element.attr('title'),
                            id:     widget.element.attr('id')
                        });
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
        _getContext: function() {
            return this.element;
        },
        _selectorSelfAndSub: function(idStr, classSel) {
            return '#' + idStr + classSel + ',' + '#' + idStr + ' ' + classSel;
        },
        _removeContent: function(layerId) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            $(this._selectorSelfAndSub(manager.headerId(layerId), manager.headerContentSel), $context).remove();
            $(this._selectorSelfAndSub(manager.contentId(layerId), manager.contentContentSel), $context).remove();
            delete(this.queries[layerId]);
            for (var prop in this.queries) {
                return;
            }
            this._setContentEmpty();
         },
        _setContentEmpty: function(id) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            if (id) {
//                $(this._selectorSelfAndSub(manager.headerId(id), manager.headerContentSel), $context).text('');
                $(this._selectorSelfAndSub(manager.contentId(id), manager.contentContentSel), $context).empty();
            } else {
                $(manager.headerSel, manager.$headerParent).remove();
                $(manager.contentSel, manager.$contentParent).remove();
            }
        },
        _addContent: function(layerId, layerTitle, content) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            var headerId = manager.headerId(layerId);
            var contentId = manager.contentId(layerId);
            var $header = $('#' + headerId, $context);
            if ($header.length === 0) {
                $header = manager.$header.clone();
                $header.attr('id', headerId);
                manager.$headerParent.append($header);
            }
            $(this._selectorSelfAndSub(headerId, manager.headerContentSel), $context).text(layerTitle);
            var $content = $('#' + contentId, $context);
            if ($content.length === 0) {
                $content = manager.$content.clone();
                $content.attr('id', contentId);
                manager.$contentParent.append($content);
            }
            $(this._selectorSelfAndSub(contentId, manager.contentContentSel), $context)
                .empty().append(content);
            if (this.options.displayType === 'tabs' || this.options.displayType === 'accordion') {
                var $tabcont;
                initTabContainer($context);
                if(this.options.displayType === 'tabs') {
                    $tabcont = $header.parents('.tabContainer:first');
                    $('.tabs .tab', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $header.addClass('active');
                    $('.container', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $('#container' + $header.attr('id').replace('tab', ''), $tabcont).addClass('active');
                } else if (this.options.displayType === 'accordion') {
                    $tabcont = $header.parents('.accordionContainer:first');
                    $('.accordion', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $header.addClass('active');
                    $('.container-accordion', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $('#container' + $header.attr('id').replace('accordion', ''), $tabcont).addClass('active');
                }
            }
        },
        _printContent: function() {
            var $context = this._getContext();
            var w = window.open("", "title", "attributes,scrollbars=yes,menubar=yes");
            var el = $('.js-content-content.active,.active .js-content-content', $context);
            var printContent = "";
            if ($('> iframe', el).length === 1) {
                var a = document.getElementById($('iframe', el).attr('id'));
                printContent = a.contentWindow.document.documentElement.innerHTML;
            } else {
                printContent = el.html();
            }
            w.document.write(printContent);
            w.print();
        }
    });
})(jQuery);

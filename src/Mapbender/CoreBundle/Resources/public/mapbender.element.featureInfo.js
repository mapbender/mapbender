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
            if (!Mapbender.checkTarget("mbFeatureInfo", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function() {
            var widget = this;
            var options = widget.options;
            var widgetElement = widget.element;
            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            widget.target = this.map;

            widgetElement.addClass('display-as-' + options.displayType);

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
            widget._ready();
        },
        _contentRef: function(mqLayer){
            var $context = this._getContext();
            var manager = this._getContentManager();
            return $('#' + manager.contentId(mqLayer.id), $context);
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.activate(callback);
        },
        activate: function(callback) {
            var widget = this;
            var options = widget.options;
            var mapElement = $('#' + options.target);

            widget.callback = callback ? callback : null;
            mapElement.addClass('mb-feature-info-active');
            this._setupMapClickHandler();
        },
        deactivate: function() {
            var widget = this;
            var element = widget.element;
            var options = widget.options;
            var mapElement = $('#' + options.target);

            widget._trigger('featureinfo', null, {
                action: "deactivate",
                title: element.attr('title'),
                id: element.attr('id')
            });

            mapElement.removeClass('mb-feature-info-active');

            $(".toolBarItemActive").removeClass("toolBarItemActive");
            if (widget.popup) {
                if (widget.popup.$element) {
                    $('body').append(element.addClass('hidden'));
                    widget.popup.destroy();
                }
                widget.popup = null;
            }

            this.map.model.removeEventListenerByKey(this.mapClickHandler);
            this.mapClickHandler = null;
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
            var called = false;
            this.queries = {};
            $('#js-error-featureinfo').addClass('hidden');
            $.each(this.map.model.collectFeatureInfoUrls(e.coordinate), function(idx, url) {
                var source = self._getSourceByFeatureInfoUrl(url);
                var mqLayer = {
                    source: {
                        configuration: source.configuration
                    },
                    label: source.configuration.title,
                    id: "-" + source.id,
                    type: source.type
                };

                if (mqLayer && Mapbender.source[mqLayer.type]) {
                    self.queries[mqLayer.id] = url;

                    if (!self.options.onlyValid) {
                        self._addContent(mqLayer, 'wird geladen');
                        self._open();
                    }
                    
                    called = true;
                    self._setInfo(mqLayer, url);
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
        _setInfo: function(mqLayer, url) {
            var self = this;
            var contentType_ = "";
            if (typeof (mqLayer.source.configuration.options.info_charset) !== 'undefined') {
                contentType_ += contentType_.length > 0 ? ";"
                    : "" + mqLayer.source.configuration.options.info_charset;
            }
            var _ajaxreq = null;
            if (mqLayer.source.configuration.options.tunnel) {
                 _ajaxreq = {
                    url: url,
                    contentType: contentType_
                };
            } else {
                _ajaxreq = {
                    url: Mapbender.configuration.application.urls.proxy,
                    contentType: contentType_,
                    data: {
                        url: mqLayer.source.configuration.options.proxy ? url : encodeURIComponent(url)
                    }
                };
            }
            var request = $.ajax(_ajaxreq);
            request.done(function(data, textStatus, jqXHR) {
                var mimetype = jqXHR.getResponseHeader('Content-Type').toLowerCase().split(';')[0];
                if (self.options.showOriginal) {
                    self._showOriginal(mqLayer, data, mimetype, url);
                } else {
                    self._showEmbedded(mqLayer, data, mimetype);
                }
            });
            request.fail(function(jqXHR, textStatus, errorThrown) {
                Mapbender.info(mqLayer.label + ' GetFeatureInfo: ' + errorThrown);
                self._addContent(mqLayer, errorThrown);
            });
        },
        _isDataValid: function(data, mimetype) {
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    var help = data.replace(/\r|\n/g, "");
                    var found = (/<body>(.+?)<\/body>/gi).exec(help);
                    if (found && $.isArray(found) && found[1] && found[1].trim() !== '') {
                        /* valid "text/html" response */
                        return true;
                    } else {
                        /* not valid "text/html" response, but ... */
                        var found = (/<[a-zA-Z]+>[^<]+<\/[a-zA-Z]+>/gi).exec(help);
                        if (found && $.isArray(found)) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                case 'text/plain':
                    return data.trim() === '';
                default: // TODO other mimetypes ?
                    return true;
            }
        },
        _showOriginal: function(mqLayer, data, mimetype, url) {
            var self = this;
            /* handle only onlyValid=true. handling for onlyValid=false see in "_triggerFeatureInfo" */
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    if (! this.options.onlyValid || (this.options.onlyValid && this._isDataValid(data, mimetype))) {
                        /* add a blank iframe and replace it's content (document.domain == iframe.document.domain */
                        this._open();
                        var uuid = Mapbender.Util.UUID();
                        var iframe = $(self._getIframeDeclaration(uuid, null));
                        self._addContent(mqLayer, iframe);
                        var doc = document.getElementById(uuid).contentWindow.document;
                        iframe.on('load', function(){
                            iframe.data('loaded', true);
                            $('#' + self._getContentManager().headerId(mqLayer.id), self.element).click();
                            iframe.contents().find("body").css("background","transparent");
                            self._trigger('featureinfo', null, {
                                action: "haveresult",
                                title: self.element.attr('title'),
                                content: self._contentRef(mqLayer).attr('id'),
                                mqlid: mqLayer.id,
                                id: self.element.attr('id')
                        });
                        });
                        doc.open();
                        doc.write(data);
                        doc.close();
                    } else {
                        this._removeContent(mqLayer);
                        Mapbender.info(mqLayer.label + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    }
                    break;
                case 'text/plain':
                default:
                    if (this.options.onlyValid && this._isDataValid(data, mimetype)) {
                        this._addContent(mqLayer, '<pre>' + data + '</pre>');
                        this._trigger('featureinfo', null, {
                            action: "haveresult",
                            title: this.element.attr('title'),
                            content: this._contentRef(mqLayer).attr('id'),
                            mqlid: mqLayer.id,
                            id: this.element.attr('id')
                        });
                        this._open();
                    } else {
                        this._removeContent(mqLayer);
                        Mapbender.info(mqLayer.label + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    }
                    break;
            }
        },
        _showEmbedded: function(mqLayer, data, mimetype) {
            switch (mimetype.toLowerCase()) {
                case 'text/html':
                    var self = this;
                    data = this._cleanHtml(data);
                    if (!this.options.onlyValid || (this.options.onlyValid && this._isDataValid(data, mimetype))) {
                        this._addContent(mqLayer, data);
                        this._trigger('featureinfo', null, {
                            action: "haveresult",
                            title: this.element.attr('title'),
                            content: this._contentRef(mqLayer).attr('id'),
                            mqlid: mqLayer.id,
                            id: this.element.attr('id')
                        });
                        this._open();
                        $('#' + self._getContentManager().headerId(mqLayer.id), self.element).click();
                    } else {
                        this._removeContent(mqLayer);
                        Mapbender.info(mqLayer.label + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
                    }
                    break;
                case 'text/plain':
                default:
                    if (!this.options.onlyValid || (this.options.onlyValid && this._isDataValid(data, mimetype))) {
                        this._addContent(mqLayer, '<pre>' + data + '</pre>');
                        this._trigger('featureinfo', null, {
                            action: "haveresult",
                            title: this.element.attr('title'),
                            content: this._contentRef(mqLayer).attr('id'),
                            mqlid: mqLayer.id,
                            id: this.element.attr('id')
                        });
                    } else {
                        this._setContentEmpty(mqLayer.id);
                        Mapbender.info(mqLayer.label + ': ' + Mapbender.trans("mb.core.featureinfo.error.noresult"));
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
                        closeButton: false,
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
        _removeContent: function(mqLayer) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            $(this._selectorSelfAndSub(manager.headerId(mqLayer.id), manager.headerContentSel), $context).remove();
            $(this._selectorSelfAndSub(manager.contentId(mqLayer.id), manager.contentContentSel), $context).remove();
            delete(this.queries[mqLayer.id]);
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
        _addContent: function(mqLayer, content) {
            var $context = this._getContext();
            var manager = this._getContentManager();
            var $header = $('#' + manager.headerId(mqLayer.id), $context);
            if ($header.length === 0) {
                $header = manager.$header.clone();
                $header.attr('id', manager.headerId(mqLayer.id));
                manager.$headerParent.append($header);
            }
            $(this._selectorSelfAndSub(manager.headerId(mqLayer.id), manager.headerContentSel), $context).text(
                mqLayer.label);
            var $content = $('#' + manager.contentId(mqLayer.id), $context);
            if ($content.length === 0) {
                $content = manager.$content.clone();
                $content.attr('id', manager.contentId(mqLayer.id));
                manager.$contentParent.append($content);
            }
            $(this._selectorSelfAndSub(manager.contentId(mqLayer.id), manager.contentContentSel), $context)
                .empty().append(content);
            if (this.options.displayType === 'tabs' || this.options.displayType === 'accordion') {
                initTabContainer($context);
                if(this.options.displayType === 'tabs') {
                    var $tabcont = $header.parents('.tabContainer:first');
                    $('.tabs .tab', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $header.addClass('active');
                    $('.container', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $('#container' + $header.attr('id').replace('tab', ''), $tabcont).addClass('active');
                } else if (this.options.displayType === 'accordion') {
                    var $tabcont = $header.parents('.accordionContainer:first');
                    $('.accordion', $tabcont).each(function(idx, item){
                        $(item).removeClass('active');
                    });
                    $header.addClass('active');
                    $('.container', $tabcont).each(function(idx, item){
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
        },
        
        /**
         *
         */
        ready: function(callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _setupMapClickHandler: function () {
            var widget = this;

            if (!widget.mapClickHandler) {
                widget.mapClickHandler = this.map.model.setOnSingleClickHandler( $.proxy(this._triggerFeatureInfo, this));
            }

            return this;
        },
        _getSourceByFeatureInfoUrl: function (url) {
            var model = this.map.model;

            for (var i = 0; i < model.pixelSources.length; ++i) {
                var source = model.pixelSources[i];
                var index = url.indexOf(source.baseUrl_);

                if (index !== -1 && index === 0) {

                    return source;
                }
            }

            return null;
        }
    });
})(jQuery);

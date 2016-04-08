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
            var self = this;
            this.target = $("#" + this.options.target).data("mapbenderMbMap");//.getModel();
            this.mapClickHandler = new OpenLayers.Handler.Click(this,
                {'click': this._triggerFeatureInfo},
                {map: $('#' + this.options.target).data('mapQuery').olMap}
            );
            if (this.options.autoActivate || this.options.autoOpen){ // autoOpen old configuration
                this.activate();
            }
            $(this.element).on('click', '.js-header', function(e) {
                $('.js-content.active:first', self.element).each(function(idx, item){ // only one tab is active
                    if($('iframe:first', $(item)).length){
                        function fireIfLoaded($item, num){
                            if($('iframe:first', $item).data('loaded')){
                                self._trigger('featureinfo', null, {
                                    action: "activated_content",
                                    id: self.element.attr('id'),
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
                        self._trigger('featureinfo', null, {
                            action: "activated_content",
                            id: self.element.attr('id'),
                            activated_content: [$(item).attr('id')]
                        });
                    }
                });
            });
            this._trigger('ready');
            this._ready();
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
            this.callback = callback ? callback : null;
            $('#' + this.options.target).addClass('mb-feature-info-active');
            this.mapClickHandler.activate();
        },
        deactivate: function() {
            this._trigger('featureinfo', null, {
                action: "deactivate",
                title: this.element.attr('title'),
                id: this.element.attr('id')
            });
            $('#' + this.options.target).removeClass('mb-feature-info-active');
            $(".toolBarItemActive").removeClass("toolBarItemActive");
            if (this.popup) {
                if (this.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.mapClickHandler.deactivate();
            this.callback ? this.callback.call() : this.callback = null;
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
                var mqLayer = self.target.getModel().map.layersList[src.mqlid];
                if (Mapbender.source[src.type]) {
                    var url = Mapbender.source[src.type].featureInfoUrl(mqLayer, x, y, $.proxy(self._setInfo, self));
                    if (url) {
                        self.queries[mqLayer.id] = url;
                        if (!self.options.onlyValid) {
                            self._addContent(mqLayer, 'wird geladen');
                            self._open();
                        }
                        called = true;
                        self._setInfo(mqLayer, url);
                    } else {
                        self._removeContent(mqLayer);
                    }
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
            var proxy = mqLayer.source.configuration.options.proxy;
            var contentType_ = "";
            if (typeof (mqLayer.source.configuration.options.info_charset) !== 'undefined') {
                contentType_ += contentType_.length > 0 ? ";"
                    : "" + mqLayer.source.configuration.options.info_charset;
            }
            var request = $.ajax({
                url: Mapbender.configuration.application.urls.proxy,
                contentType: contentType_,
                data: {
                    url: proxy ? url : encodeURIComponent(url)
                }
            });
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
            var self = this;
            if (this.options.type === 'dialog') {
                if (!this.popup || !this.popup.$element) {
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeButton: false,
                        closeOnESC: false,
                        detachOnClose: false,
                        content: this.element.removeClass('hidden'),
                        resizable: true,
                        cssClass: 'featureinfoDialog',
                        width: self.options.width,
                        height: self.options.height,
                        buttons: {
                            'ok': {
                                label: Mapbender.trans('mb.core.featureinfo.popup.btn.ok'),
                                cssClass: 'button buttonCancel critical right',
                                callback: function() {
                                    this.close();
                                    if (self.options.deactivateOnClose) {
                                        self.deactivate();
                                    }
                                }
                            }
                        }
                    });
                    this.popup.$element.on('close', function() {
                        self._trigger('featureinfo', null, {
                            action: "closedialog",
                            title:  self.element.attr('title'),
                            id:     self.element.attr('id')
                        });
                        self.state = 'closed';
                        if (self.options.deactivateOnClose) {
                            self.deactivate();
                        }
                        if (self.popup && self.popup.$element) {
                            self.popup.$element.hide();
                        }
                    });
                    this.popup.$element.on('open', function() {
                        self.state = 'opened';
                    });
                    if (this.options.printResult === true) {
                        this.popup.addButtons({
                            'print': {
                                label: Mapbender.trans('mb.core.printclient.popup.btn.ok'),
                                cssClass: 'button right',
                                callback: function() {
                                    self._printContent();
                                }
                            }
                        });
                    }
                }
                if(self.state !== 'opened') {
                    this.popup.open();
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
        }
    });
})(jQuery);

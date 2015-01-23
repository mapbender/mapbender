(function($){

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            target: null,
            autoActivate: false,
            deactivateOnClose: true,
            type: 'dialog',
            displayType: 'tabs',
            printResult: false,
            showOriginal: false,
            onlyValid: false
        },
        target: null,
        model: null,
        mapClickHandler: null,
        popup: null,
        context: null,
        queries: [],
        contentManager: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbFeatureInfo", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function(){
            this.target = $("#" + this.options.target).data("mapbenderMbMap");//.getModel();
            this.mapClickHandler = new OpenLayers.Handler.Click(this,
                    {'click': this._triggerFeatureInfo}, {map: $('#' + this.options.target).data('mapQuery').olMap});
            if(this.options.autoActivate)
                this.activate();
            this._trigger('ready');
            this._ready();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            $('#' + this.options.target).addClass('mb-feature-info-active');
            this.mapClickHandler.activate();
        },
        deactivate: function(){
            $('#' + this.options.target).removeClass('mb-feature-info-active');
            $(".toolBarItemActive").removeClass("toolBarItemActive");
            if(this.popup) {
                if(this.popup.$element) {
                    $('body').append(this.element.addClass('hidden'));
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.mapClickHandler.deactivate();
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         * Trigger the Feature Info call for each layer.
         * Also set up feature info dialog if needed.
         */
        _triggerFeatureInfo: function(e){
            this._trigger('featureinfo', null, {action: "clicked", title: this.element.attr('title'),
                id: this.element.attr('id')});
            var self = this, x = e.xy.x, y = e.xy.y, num = 0;
            var queries = [];
            if(!self.options.onlyValid) {
                this._setContentEmpty();
            }
            $.each(this.target.getModel().getSources(), function(idx, src){
                var mqLayer = self.target.getModel().map.layersList[src.mqlid];
                if(Mapbender.source[src.type]) {
                    var url = Mapbender.source[src.type].featureInfoUrl(mqLayer, x, y, $.proxy(self._setInfo, self));
                    if(url) {
                        if(!self.options.onlyValid) {
                            self._addContent(mqLayer, 'wird geladen');
                        }
                        queries[mqLayer.id] = num++;
                        if(self.options.showOriginal && !self.options.onlyValid) {
                            self._addContent(mqLayer, self._getIframeDeclaration(null, url));
                        } else {
                            self._setInfo(mqLayer, url);
                        }
                    }
                }
            });
//            var content = (fi_exist) ? tabContainer : '<p class="description">' + Mapbender.trans('mb.core.featureinfo.error.nolayer') + '</p>';
            
        },
        _getIframeDeclaration: function(uuid, url){
            var id = uuid ? (' id="' + uuid + '"') : '';
            var src = url ? (' src="' + url + '"') : '';
            return '<iframe class="featureInfoFrame"' + id + src + '></iframe>'
        },
        _setInfo: function(mqLayer, url){
            var self = this;
            var proxy = mqLayer.source.configuration.options.proxy;
            var contentType_ = "";
            if(typeof (mqLayer.source.configuration.options.info_charset) !== 'undefined') {
                contentType_ += contentType_.length > 0 ? ";"
                        : "" + mqLayer.source.configuration.options.info_charset;
            }
            var request = $.ajax({
                url: Mapbender.configuration.application.urls.proxy,
                contentType: contentType_,
                data: {url: proxy ? url : encodeURIComponent(url)}
            });
            request.done(function(data, textStatus, jqXHR){
                var mimetype = jqXHR.getResponseHeader('Content-Type').toLowerCase().split(';')[0];
                if(self.options.showOriginal) {
                    self._showOriginal(mqLayer, data, mimetype, url);
                } else {
                    self._showEmbedded(mqLayer, data, mimetype);
                }
            });
            request.fail(function(jqXHR, textStatus, errorThrown){
                Mapbender.error(textStatus);
            });
        },
        _isDataValid: function(data, mimetype){
            switch(mimetype.toLowerCase()) {
                case 'text/html':
                    var $test = $(data).find('body');
                    if($test.length !== 0) {
                        return $test.html().trim() === '';
                    } else {
                        return false;
                    }
                case 'text/plain':
                    return data.trim() === '';
                default: // TODO other mimetypes ?
                    return true;
            }
        },
        _showOriginal: function(mqLayer, data, mimetype, url){
            /* handle only onlyValid=true. handling for onlyValid=false see in "_triggerFeatureInfo" */
            switch(mimetype.toLowerCase()) {
                case 'text/html':
                    var $html = data ? $.parseHTML(data, document, true) : null;
                    if($html && $html.length > 0 && this.options.onlyValid) { // !onlyValid s. _triggerFeatureInfo
                        /* add a blank iframe and replace it's content */
                        var uuid = Mapbender.Util.UUID();
                        this._addContent(mqLayer, this._getIframeDeclaration(uuid, null));
                        var doc = document.getElementById(uuid).contentWindow.document;
                        doc.open();
                        doc.write(data);
                        doc.close();
                    }
                    break;
                case 'text/plain':
                default:
                    if(this.options.onlyValid && data) {
                        this._addContent(mqLayer, '<pre>' + data + '</pre>');
                    }
                    break;
            }
        },
        _showEmbedded: function(mqLayer, data, mimetype){
            switch(mimetype.toLowerCase()) {
                case 'text/html':
                    data = this._cleanHtml(data);
                    if(!this.options.onlyValid || (this.options.onlyValid && data)) {
                        this._addContent(mqLayer, data);
                    }
                    break;
                case 'text/plain':
                default:
                    if(!this.options.onlyValid || (this.options.onlyValid && data)) {
                        this._addContent(mqLayer, '<pre>' + data + '</pre>');
                    }
                    break;
            }
        },
        _cleanHtml: function(data){
            try {
                if(data.search('<link') > -1 || data.search('<style') > -1) {
                    return data.replace(/document.writeln[^;]*;/g, '')
                            .replace(/\n/g, '')
                            .replace(/<link[^>]*>/gi, '')
                            .replace(/<meta[^>]*>/gi, '')
                            .replace(/<title>*(?:[^<]*<\/title>)/gi, '')
                            .replace(/<style[^>]*(?:[^<]*<\/style>|>)/gi, '')
                            .replace(/<script[^>]*(?:[^<]*<\/script>|>)/gi, '');
                }
            } catch(e) {
            }
            return '';
        },
        _wrapData: function(data, $wraper){
            return $wraper.append(data);
        },
        _getContentManager: function(){
            if(!this.contentManager) {
                this.contentManager = {
                    headerSel: '.js-header',
                    $headerParent: $('.js-header', this.element).parent(),
                    $header: $('.js-header', this.element).remove(),
                    headerContentSel: '.js-header-content',
                    headerId: function(id){
                        return this.$header.attr('data-idname') + id
                    },
                    contentSel: '.js-content',
                    $contentParent: $('.js-content', this.element).parent(),
                    $content: $('.js-content', this.element).remove(),
                    contentContentSel: '.js-content-content',
                    contentId: function(id){
                        return this.$content.attr('data-idname') + id
                    }
                };
            }
            return this.contentManager;
        },
        _getContext: function(){
            var self = this;
            if(this.options.type === 'dialog') {
                if(!this.popup || !this.popup.$element) {
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeButton: false,
                        closeOnESC: false,
                        content: this.element.removeClass('hidden'),
                        resizable: true,
                        width: 700,
                        height: 500,
                        buttons: {
                            'ok': {
                                label: Mapbender.trans('mb.core.featureinfo.popup.btn.ok'),
                                cssClass: 'button buttonCancel critical right',
                                callback: function(){
                                    this.close();
                                    if(self.options.deactivateOnClose) {
                                        self.deactivate();
                                    }
                                }
                            }
                        }
                    });
                    this.popup.$element.on('close', function(){
                        if(self.options.deactivateOnClose) {
                            self.deactivate();
                        }
                    });
                    if(this.options.printResult === true) {
                        this.popup.addButtons({
                            'print': {
                                label: Mapbender.trans('mb.core.printclient.popup.btn.ok'),
                                cssClass: 'button right',
                                callback: function(){
                                    self._printContent();
                                }
                            }
                        });
                    }
                }
                this.popup.open();
            }
            return this.element;
        },
        _selectorSelfAndSub: function(idStr, classSel){
            return '#' + idStr + classSel + ',' + '#' + idStr + ' ' + classSel;
        },
        _setContentEmpty: function(id){
            var $context = this._getContext();
            var manager = this._getContentManager();
            if(id) {
                $(this._selectorSelfAndSub(manager.headerId(id), manager.headerContentSel), $context).text('');
                $(this._selectorSelfAndSub(manager.contentId(id), manager.contentContentSel), $context).empty();
            } else {
                $(manager.headerSel, manager.$headerParent).remove();
                $(manager.contentSel, manager.$contentParent).remove();
            }
        },
        _addContent: function(mqLayer, content){
            var $context = this._getContext();
            var manager = this._getContentManager();
            var $header = $('#' + manager.headerId(mqLayer.id), $context);
            if($header.length === 0) {
                $header = manager.$header.clone();
                $header.attr('id', manager.headerId(mqLayer.id));
                manager.$headerParent.append($header);
            }
            $(this._selectorSelfAndSub(manager.headerId(mqLayer.id), manager.headerContentSel), $context).text(
                    mqLayer.label);
            var $content = $('#' + manager.contentId(mqLayer.id), $context);
            if($content.length === 0) {
                $content = manager.$content.clone();
                $content.attr('id', manager.contentId(mqLayer.id));
                manager.$contentParent.append($content);
            }
            $(this._selectorSelfAndSub(manager.contentId(mqLayer.id), manager.contentContentSel), $context)
                    .empty().append(content);
            if(this.options.displayType === 'tabs') {
                $(".tabContainer", $context).off('click', '.tab');
                $(".tabContainer", $context).on('click', '.tab', function(){
                    var me = $(this);
                    me.parents('.tabContainer').find(".active").removeClass("active");
                    me.addClass("active");
                    $("#" + me.attr("id").replace("tab", "container")).addClass("active");
                });
                $('.tabContainer .tab:first', $context).addClass("active");
                $('.tabContainer .container:first', $context).addClass("active");
                $header.click();
            } else if(this.options.displayType === 'tabs') {

            }
        },
        _show: function(){
            if(this.options.type === 'dialog') {

            } else if(this.options.type === 'element') {
                this.element.append(content);
            }
        },
        _printContent: function(){
            var w = window.open("", "title", "attributes");
            var c = $('#featureInfoTabContainer').find('div.active').html();
            w.document.write(c);
            w.setTimeout(function(){
                w.print();
            }, 1000);
        },
        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });
})(jQuery);

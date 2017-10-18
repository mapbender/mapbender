(function($){

    $.widget("mapbender.mbLegend", {
        options: {
            autoOpen: true,
            target: null,
            noLegend: "No legend available",
            elementType: "dialog",
            displayType: "list",
            checkGraphic: false,
            hideEmptyLayers: true,
            generateLegendGraphicUrl: false,
            showSourceTitle: true,
            showLayerTitle: true,
            showGrouppedTitle: true,
            maxImgWidth: 0,
            maxImgHeight: 0
        },
        model: null,
        layerTitle: "",
        sourceTitle: "",
        grouppedTitle: "",
        hiddeEmpty: "",
        _create: function(){
            if(!Mapbender.checkTarget("mbLegend", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            var self = this;
            this.options.noLegend = Mapbender.trans("mb.core.legend.nolegend");
            this.model = $("#" + self.options.target).data("mapbenderMbMap").getModel();

            this.layerTitle = this.options.showLayerTitle ? "" : "notshow";
            this.sourceTitle = this.options.showSourceTitle ? "" : "notshow";
            this.grouppedTitle = this.options.showGrouppedTitle ? "" : "notshow";
            this.hiddeEmpty = this.options.hideEmptyLayers ? "notshow" : "";

            if(this.options.autoOpen) {
                this.open();
            } else if(this.options.elementType !== 'dialog') {
                var self = this;
                var sources = this._getSources();
                if(this.options.checkGraphic) {
                    this._createCheckedLegendHtml(sources, 0, 0, "", "", $.proxy(self._createLegend, self));
                } else {
                    this._createLegend(this._createLegendHtml(sources));
                }
                sources = this.model.getSources();
                for(var i = 0; i < sources.length; i++) {
                    this._checkLayers(sources[i]);
                }
            }
            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            $(document).bind('mbmapsourcemoved', $.proxy(self._onSourceMoved, self));
            this._trigger('ready');
            this._ready();
        },
        _onSourceAdded: function(event, options){
            if(!options.added)
                return;
            var added = options.added;
            var self = this;
            var hasChildren = false;
            for(layer in added.children) {
                hasChildren = true;
            }
            if(!hasChildren) {
                var sources = this._getSource(added.source, added.source.configuration.children[0], 1);
                if(this.options.checkGraphic) {
                    this._createCheckedLegendHtml([sources], 0, 0, "", "", $.proxy(self._addSource, self), added);
                } else {
                    this._addSource(this._createLayerHtml(sources, ""), added);
                }
            }
        },
        _addSource: function(html, added){
            var hasChildren = false;
            for(layer in added.children) {
                hasChildren = true;
            }
            if(!hasChildren) {
                if(added.after && added.after.source) {
                    $(this.element).find('[data-sourceid="' + added.after.source.id + '"]:last').after($(html));
                } else if(added.before && added.before.source) {
                    $(this.element).find('[data-sourceid="' + added.before.source.id + '"]:first').before($(html));
                } else {
                    $(this.element).find('ul').append($(html));
                }
            }
        },
        _onSourceMoved: function(event, moved){
            if(moved.layerId) {
                if(moved.before) {
                    $(this.element).find('[data-id="' + moved.before.layerId + '"]:first').before($(this.element).find(
                        '[data-id="' + moved.layerId + '"]'));
                } else if(moved.after) {
                    $(this.element).find('[data-id="' + moved.after.layerId + '"]:last').after($(this.element).find(
                        '[data-id="' + moved.layerId + '"]'));
                }
            } else {
                if(moved.before) {
                    $(this.element).find('[data-id="' + moved.before.layerId + '"]:first').before($(this.element).find(
                        '[data-sourceid="' + moved.source.id + '"]'));
                } else if(moved.after) {
                    $(this.element).find('[data-id="' + moved.after.layerId + '"]:last').after($(this.element).find(
                        '[data-sourceid="' + moved.source.id + '"]'));
                }
            }
        },
        _onSourceChanged: function(event, options){
            var self = this;
            var context = null;
            if(this.options.elementType === "blockelement") {
                context = this.element;
            } else if(this.options.elementType === "dialog" && this.popup && this.popup.$element) {
                context = this.popup.$element;
            } else {
                return;
            }
            if(options.changed && options.changed.options) {

            } else if(context && options.changed && options.changed.children) {
                for(layerName in options.changed.children) {
                    var layer = options.changed.children[layerName];

                    if(layer.state) {

                        if(layer.state.visibility) {

                            if($('li.image[data-id="' + layerName + '"]', context).attr("data-gisurl"))
                                $('li.image[data-id="' + layerName + '"]', context).html('<img src="' + $('li.image[data-id="' + layerName + '"]', context).attr("data-gisurl") + '">');

                            $('li[data-id="' + layerName + '"]', context).removeClass('notvisible');
                        } else {
                            $('li[data-id="' + layerName + '"]', context).addClass('notvisible');
                        }
                    }
                }
            } else if(context && options.changed && options.changed.childRemoved) {
                function layerlist(layer, layers){
                    layers.push(layer.options.id);
                    if(layer.children)
                        $.each(layer.children, function(idx, layer_){
                            layerlist(layer_, layers);
                        })
                }
                var layers = [];
                layerlist(options.changed.childRemoved.layer, layers);
                $.each(layers, function(idx, layerid){
                    $('li[data-id="' + layerid + '"]', context).remove();
                });
            }
            var source = this.model.getSource(options.changed.sourceIdx);
            var root = source ? source.configuration.children[0] : null;
            if(root) {
                if($('ul[data-id="' + root.options.id + '"] li', context).not('.notshow').not(
                        '.notvisible').length === 0) {
                    $('li[data-id="' + root.options.id + '"]', context).addClass('notvisible');
                } else {
                    $('li[data-id="' + root.options.id + '"]', context).removeClass('notvisible');
                }
            }

        },
        _onSourceRemoved: function(event, removed){
            var context = null;
            if(this.options.elementType === "blockelement") {
                context = this.element;
            } else if(this.options.elementType === "dialog" && this.popup && this.popup.$element) {
                context = this.popup.$element;
            } else {
                return;
            }
            if(context && removed && removed.source && removed.source.id) {
                $('ul[data-sourceid="' + removed.source.id + '"]', context).remove();
                $('li[data-sourceid="' + removed.source.id + '"]', context).remove();
            }
        },
        _onSourceLoadStart: function(event, option){
        },
        _onSourceLoadEnd: function(event, option){
            this._checkLayers(option.source);
        },
        _checkLayers: function(source){
            var self = this, elm = null;
            if(this.options.elementType === "dialog" && this.popup && this.popup.$element) {
                elm = this.popup.$element;
            } else {
                elm = self.element;
            }
            function checkLayers(layer, parent){
                if(layer.state) {
                    var $li = $('li[data-id="' + layer.options.id + '"]', elm);
                    if(layer.state.visibility) {
                        $li.removeClass('notvisible');
                    } else {
                        $li.addClass('notvisible');
                    }
                }
                if(layer.children) {
                    for(var i = 0; i < layer.children.length; i++) {
                        checkLayers(layer.children[i], layer);
                    }
                }
            }
            checkLayers(source.configuration.children[0], null);
        },
        _onSourceLoadError: function(event, option){
            $(this.element).find('ul[data-sourceid="' + option.source.id + '"] li').addClass('notvisible');
        },
        _checkMaxImgWidth: function(val){
            if(this.options.maxImgWidth < val)
                this.options.maxImgWidth = val;
        },
        _checkMaxImgHeight: function(val){
            if(this.options.maxImgHeight < val)
                this.options.maxImgHeight = val;
        },
        _getSources: function(){
            var allLayers = [];
            var sources = this.model.getSources();
            for(var i = (sources.length - 1); i > -1; i--) {
                allLayers.push(this._getSource(sources[i], sources[i].configuration.children[0], 1));
            }
            return allLayers;
        },
        _getSource: function(source, layer, level){
            var children_ = this._getSublayers(source, layer, level + 1, []);
            var childrenLeg = false;
            for(var i = 0; i < children_.length; i++) {
                if(children_[i].childrenLegend || (children_[i].legend && children_[i].legend.url)) {
                    childrenLeg = true;
                }
            }
            return {
                sourceId: source.id,
                id: layer.options.id,
                visible: layer.state.visibility ? '' : 'notvisible',
                title: layer.options.title,
                level: level,
                children: children_,
                childrenLegend: childrenLeg
            };
        },
        _getSublayers: function(source, layer, level, children){
            var self = this;
            if(layer.children)
                for(var i = (layer.children.length - 1); i > -1; i--) {
                    children = children.concat(self._getSublayer(source, layer.children[i], "wms", level, []));
                }

            return children;
        },
        _getSublayer: function(source, sublayer, type, level, children){
            var sublayerLeg = {
                sourceId: source.id,
                id: sublayer.options.id,
                visible: sublayer.state.visibility ? '' : ' notvisible',
                title: sublayer.options.title,
                level: level,
                isNode: sublayer.children && sublayer.children.length > 0 ? true : false
            };
            function getLegend(layer, generate){
                var legendObj = null;
                if(layer.options.legend) {
                    legendObj = layer.options.legend;
                    if(!legendObj.url && generate && legendObj.graphic) {
                        legendObj['url'] = legendObj.graphic;
                    }
                }
                return legendObj;
            }
            sublayerLeg["legend"] = getLegend(sublayer, this.options.generateLegendGraphicUrl);
            if(!sublayerLeg.isNode)
                children.push(sublayerLeg);
            if(sublayer.children) {
                if(this.options.showGrouppedTitle) {
                    children.push(sublayerLeg);
                }
                var childrenLegend = false;
                for(var i = (sublayer.children.length - 1); i > -1; i--) {
                    var layleg = getLegend(sublayer.children[i], this.options.generateLegendGraphicUrl);
                    if(layleg && layleg.url) {
                        childrenLegend = true;
                    }
                    children = children.concat(this._getSublayer(source, sublayer.children[i], type, level, [
                    ]));//children
                }
                sublayerLeg['childrenLegend'] = childrenLegend;
            }
            return children;
        },
        _createSourceTitleLine: function(layer){
            return '<li class="ebene' + layer.level + ' ' + this.sourceTitle + (!layer.childrenLegend ? ' notshow'
                : '') + ' title" data-sourceid="' + layer.sourceId + '" data-id="' + layer.id + '">' + layer.title + '</li>';
        },
        _createNodeTitleLine: function(layer){
            return '<li class="ebene' + layer.level + ' ' + layer.visible + ' ' + this.grouppedTitle + (!layer.childrenLegend
                ? ' notshow'
                : '') + ' subTitle" data-id="' + layer.id + '">' + layer.title + '</li>';
        },
        _createTitleLine: function(layer, hide){
            return '<li class="ebene' + layer.level + ' ' + layer.visible + ' ' + this.layerTitle + ' ' + (hide
                ? this.hiddeEmpty : '') + ' subTitle" data-id="' + layer.id + '">' + layer.title + '</li>';
        },
        _createImageLine: function(layer){

            if(layer.visible == " notvisible")
                return '<li class="ebene' + layer.level + ' ' + layer.visible + ' image" data-id="' + layer.id + '" data-gisurl="' + layer.legend.url + '"></li>';
            else
                return '<li class="ebene' + layer.level + ' ' + layer.visible + ' image" data-id="' + layer.id + '"><img src="' + layer.legend.url + '"></img></li>';
        },
        _createTextLine: function(layer, hide){
            return '<li class="ebene' + layer.level + ' ' + layer.visible + ' ' + (hide ? this.hiddeEmpty
                : '') + ' text" data-id="' + layer.id + '">' + this.options.noLegend + '</li>';
        },
        _createLegendHtml: function(sources){
            var html = "";
            for(var i = 0; i < sources.length; i++) {
                html += this._createLayerHtml(sources[i], "");
            }
            return html;
        },
        _createLayerHtml: function(layer, html){
            if(layer.children) {
                html += this._createSourceTitleLine(layer);
                html += '<ul class="ebene' + layer.level + '" data-sourceid="' + layer.sourceId + '" data-id="' + layer.id + '">';
                for(var i = 0; i < layer.children.length; i++) {
                    if(layer.children[i].visible !== 'notvisible') {
                        html += this._createLayerHtml(layer.children[i], "");
                    }
                }
                html += '</ul>';
            } else {
                if(layer.isNode) {
                    html += this._createNodeTitleLine(layer);
                } else {
                    if(layer.legend && layer.legend.url) {
                        html += this._createTitleLine(layer, false);
                        html += this._createImageLine(layer, false);
                    } else {
                        html += this._createTitleLine(layer, true);
                        html += this._createTextLine(layer, true);
                    }
                }
            }
            return html;
        },
        _createCheckedLegendHtml: function(layers, layidx, sublayidx, html, reshtml, callback, added){
            var self = this;
            if(layers.length > layidx) {
                var layer = layers[layidx];
                if(layers[layidx].children.length > sublayidx) {
                    if(layers[layidx].children[sublayidx].legend) {
                        $(self.element).find("#imgtest").html(
                            '<img id="testload" style="display: none;" src="' + layers[layidx].children[sublayidx].legend.url + '"></img>');
                        $(self.element).find("#imgtest #testload").load(function(){
                            self._checkMaxImgWidth(self.width);
                            self._checkMaxImgHeight(self.height);
                            if(layers[layidx].children[sublayidx].isNode) {
                                html += self._createNodeTitleLine(layers[layidx].children[sublayidx]);
                            } else {
                                html += self._createTitleLine(layers[layidx].children[sublayidx], false);
                                html += self._createImageLine(layers[layidx].children[sublayidx]);
                            }
                            self._createCheckedLegendHtml(layers, layidx, ++sublayidx, html, reshtml, callback, added);
                        }).error(function(){
                            if(layers[layidx].children[sublayidx].isNode) {
                                html += self._createNodeTitleLine(layers[layidx].children[sublayidx]);
                            } else {
                                html += self._createTitleLine(layers[layidx].children[sublayidx], true);
                                html += self._createImageLine(layers[layidx].children[sublayidx], true);
                            }
                            self._createCheckedLegendHtml(layers, layidx, ++sublayidx, html, reshtml, callback, added);
                        });
                    } else {
                        if(layers[layidx].children[sublayidx].isNode) {
                            html += self._createNodeTitleLine(layers[layidx].children[sublayidx]);
                        } else {
                            html += self._createTitleLine(layers[layidx].children[sublayidx], true);
                            html += self._createImageLine(layers[layidx].children[sublayidx], true);
                        }
                        self._createCheckedLegendHtml(layers, layidx, ++sublayidx, html, reshtml, callback, added);
                    }
                } else {
                    var html_ = '';
                    html_ += _createSourceTitleLine(layer);
                    html_ += '<ul class="ebene' + layer.level + '" data-sourceid="' + layer.sourceId + '" data-id="' + layer.id + '">';
                    html_ += html;
                    html_ += '</ul>';
                    reshtml += html_;
                    self._createCheckedLegendHtml(layers, ++layidx, 0, "", reshtml, callback, added);
                }
            } else {
                if(added)
                    callback(reshtml, added);
                else
                    callback(reshtml);
            }
        },
        _createLegend: function(html){
            var self = this;
            $(self.element).find("#imgtest").html("");
            if(this.options.elementType === "dialog") {
                if(!this.popup || !this.popup.$element) {
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('title'),
                        draggable: true,
                        resizable: true,
                        modal: false,
                        closeButton: false,
                        closeOnPopupCloseClick: true,
                        closeOnESC: false,
                        content: ('<ul>' + html + '</ul>'),
                        width: 350,
                        height: 500,
                        buttons: {
                            'ok': {
                                label: Mapbender.trans('mb.core.legend.popup.btn.ok'),
                                cssClass: 'button right',
                                callback: function(){
                                    self.close();
                                }
                            }
                        }
                    });
                } else {
                    this.popup.open(('<ul>' + html + '</ul>'));
                }
            } else {
                $(this.element).find('#legends:eq(0)').html('<ul>' + html + '</ul>');
            }

            // WATCHOUT:
            // Accordion is not supported in v.3.0.0.0.
            // Support comes in the next versions
            // if(this.options.displayType === 'accordion'){
            //     $(this.element).find('ul.ebene1').each(function(){
            //         $(this).accordion({
            //             header: "li.title",
            //             autoHeight: false,
            //             collapsible: true,
            //             active: false
            //         });
            //     });
            //     $(this.element).find('.layerlegends').each(function(){
            //         $(this).accordion({
            //             autoHeight: false,
            //             collapsible: true,
            //             active: false
            //         });
            //     });
            // }
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        /**
         * This activates this button and will be called on click
         */
        open: function(callback){
            if(callback)
                this.callback = callback;
            else
                this.callback = null;
            var self = this;
            var sources = this._getSources();
            if(this.options.checkGraphic) {
                this._createCheckedLegendHtml(sources, 0, 0, "", "", $.proxy(self._createLegend, self));
            } else {
                this._createLegend(this._createLegendHtml(sources));
            }
            sources = this.model.getSources();
            for(var i = 0; i < sources.length; i++) {
                this._checkLayers(sources[i]);
            }

        },
        close: function(){
            if(this.options.elementType === "dialog") {
                if(this.popup) {
                    if(this.popup.$element)
                        this.popup.destroy();
                    this.popup = null;
                }

            }
            if(this.callback) {
                this.callback.call();
                this.callback = null;
            }
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

(function($) {

    $.widget("mapbender.mbLegend", {
        options: {
            autoOpen:                 true,
            target:                   null,
            noLegend:                 "No legend available",
            elementType:              "dialog",
            displayType:              "list",
            hideEmptyLayers:          true,
            generateLegendGraphicUrl: false,
            showSourceTitle:          true,
            showLayerTitle:           true,
            showGroupedTitle:         true,
            maxImgWidth:              0,
            maxImgHeight:             0
        },

        readyCallbacks: [],
        callback:       null,

        /**
         * Widget constructor
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var options = widget.options;
            if(!Mapbender.checkTarget("mbLegend", options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(options.target, $.proxy(widget._setup, widget));
        },

        /**
         * Setup widget
         *
         * @private
         */
        _setup: function() {
            var widget = this;
            var options = widget.options;


            options.noLegend = Mapbender.trans("mb.core.legend.nolegend");

            // Deprecated check if options exists
            if(options.hasOwnProperty("showGrouppedTitle")) {
                options.showGroupedTitle = options["showGrouppedTitle"];
            }

            widget.isPopUpDialog = options.elementType === "dialog";
            widget.htmlContainer = widget.element.find('> .legends');

            widget.showLoadingProgress();

            $(document)
                .bind('mbmapsourceloadend', $.proxy(widget.onMapLoaded, widget))
            ;
            widget._trigger('ready');
            widget._ready();
        },

        /**
         * On map loaded
         *
         * @param e
         */
        onMapLoaded: function(e) {
            var widget = this;
            var options = widget.options;

            if(widget.isPopUpDialog) {
                widget.element.hide(0);
                if(options.autoOpen) {
                    widget.open();
                }
            }

            widget.onMapLayerChanges();

            $(document)
                .bind('mbmapsourceadded mbmapsourcechanged mbmapsourcemoved mbmapsourcesreordered', $.proxy(widget.onMapLayerChanges, widget))
                .unbind('mbmapsourceloadend', widget.onMapLoaded);
        },

        /**
         * Show loading progress
         */
        showLoadingProgress: function() {
            var widget = this;
            widget.htmlContainer.html($('<i class="fa fa-cog fa-spin fa-fw"></i>'));
        },

        /**
         * On map layer changes handler
         *
         * @param e
         */
        onMapLayerChanges: function(e) {
            var widget = this;
            widget.showLoadingProgress();

            var html = widget.render();

            widget.htmlContainer.html(html);

            if(widget.isPopUpDialog && widget.popupWindow && widget.popupWindow.$element) {
                widget.popupWindow.open(html);
            }
        },

        /**
         * Popup HTML window
         *
         * @param html
         * @return {mapbender.mbLegend.popup}
         */
        popup: function(html) {
            var widget = this;
            var element = widget.element;

            if(!widget.popupWindow || !widget.popupWindow.$element) {
                widget.popupWindow = new Mapbender.Popup2({
                    title:                  element.attr('title'),
                    draggable:              true,
                    resizable:              true,
                    modal:                  false,
                    closeButton:            false,
                    closeOnPopupCloseClick: true,
                    closeOnESC:             false,
                    destroyOnClose:         true,
                    content:                (html),
                    width:                  350,
                    height:                 500,
                    buttons:                {
                        'ok': {
                            label:    Mapbender.trans('mb.core.legend.popup.btn.ok'),
                            cssClass: 'button right',
                            callback: function() {
                                widget.close();
                            }
                        }
                    }
                });
            } else {
                widget.popupWindow.open((html));
            }

            return widget.popupWindow;
        },

        /**
         *
         * @return {Array}
         * @private
         */
        _getSources: function() {
            var widget = this;
            var allLayers = [];
            var sources = Mapbender.Model.getSources();
            for (var i = (sources.length - 1); i > -1; i--) {
                allLayers.push(widget._getSource(sources[i], sources[i].configuration.children[0], 1));
            }
            return allLayers;
        },

        /**
         *
         * @param source
         * @param layer
         * @param level
         * @return {{sourceId, id, visible, title, level: *, children: *, childrenLegend: boolean}}
         * @private
         */
        _getSource: function(source, layer, level) {
            var widget = this;
            var children_ = widget._getSubLayers(source, layer, level + 1, []);
            var childrenLeg = false;
            for (var i = 0; i < children_.length; i++) {
                if(children_[i].childrenLegend || (children_[i].legend && children_[i].legend.url)) {
                    childrenLeg = true;
                }
            }
            return {
                sourceId:       source.id,
                id:             layer.options.id,
                visible:        layer.state.visibility,
                title:          layer.options.title,
                level:          level,
                children:       children_,
                childrenLegend: childrenLeg
            };
        },

        /**
         * Get sub layers
         * @param source
         * @param layer
         * @param level
         * @param children
         *
         * @return {*} Children
         * @private
         */
        _getSubLayers: function(source, layer, level, children) {
            var widget = this;
            (layer.children || []).map(function(childLayer) {
                children = children.concat(widget._getSubLayer(source, childLayer, "wms", level, []));
            });
            return children;
        },

        /**
         * Get legend
         *
         * @param layer
         * @param generate
         * @return {*}
         */
        getLegend: function(layer, generate) {
            var legend = null;
            if(layer.options.legend) {
                legend = layer.options.legend;
                if(!legend.url && generate && legend.graphic) {
                    legend['url'] = legend.graphic;
                }
            }
            return legend;
        },

        /**
         *
         * @param source
         * @param sublayer
         * @param type
         * @param level
         * @param children
         * @return {*}
         * @private
         */
        _getSubLayer: function(source, sublayer, type, level, children) {
            var widget = this;
            var sublayerLeg = {
                sourceId: source.id,
                id:       sublayer.options.id,
                visible:  sublayer.state.visibility,
                title:    sublayer.options.title,
                level:    level,
                isNode:   sublayer.children && sublayer.children.length
            };

            sublayerLeg["legend"] = widget.getLegend(sublayer, widget.options.generateLegendGraphicUrl);

            if(!sublayerLeg.isNode) {
                children.push(sublayerLeg);
            }

            if(sublayer.children) {
                if(widget.options.showGroupedTitle) {
                    children.push(sublayerLeg);
                }

                var childrenLegend = false;
                _.chain(sublayer.children).each(function(subLayerChild) {
                    var legendLayer = widget.getLegend(subLayerChild, widget.options.generateLegendGraphicUrl);
                    var hasLegendUrl = legendLayer && legendLayer.url;

                    if(hasLegendUrl) {
                        childrenLegend = true;
                    }

                    children = children.concat(widget._getSubLayer(source, subLayerChild, type, level, []));//children
                });

                sublayerLeg['childrenLegend'] = childrenLegend;
            }

            return children;
        },

        /**
         *
         * @param layer
         * @private
         */
        createSourceTitle: function(layer) {
            return $("<li/>")
                .text(layer.title)
                .addClass('ebene' + layer.level)
                .addClass('title');
        },

        /**
         *
         * @param layer
         * @private
         */
        createNodeTitle: function(layer) {
            return $("<li/>")
                .text(layer.title)
                .addClass('ebene' + layer.level)
                .addClass(layer.visible)
                .addClass('subTitle')
                .data({id: layer.id});
        },

        /**
         *
         * @param layer
         * @private
         */
        createTitle: function(layer) {
            return $("<div/>")
                .text(layer.title)
                // .addClass(layer.visible)
                .addClass('subTitle')
                .data({id: layer.id});
        },
        /**
         * Create Image
         *
         * @param layer
         * @private
         */
        createImage: function(layer) {
            return $('<img/>')
                .css({'display': 'block'})
                .data({id: layer.id})
                .attr('src', layer.legend.url);
        },

        /**
         * Create Legend Container
         * @param layer
         */
        createLegendContainer: function(layer) {
            return $('<ul/>')
                .addClass('ebene' + layer.level)
                .data({
                    sourceid: layer.sourceId,
                    id:       layer.id
                });
        },

        _createLayerHtml: function(layer) {
            var widget = this;
            var options = widget.options;
            var html = null;

            if(layer.children) {
                var visibleChildLayers = _.chain(layer.children).where({visible: true});
                var ul = widget.createLegendContainer(layer);

                if(options.hideEmptyLayers && visibleChildLayers.size() < 1) {
                    return null;
                }

                if(options.showSourceTitle) {
                    ul.append(widget.createSourceTitle(layer));
                }

                visibleChildLayers.reverse().each(function(childLayer) {
                    ul.append(widget._createLayerHtml(childLayer));
                });

                html = ul;
            } else {
                if(layer.isNode) {
                    if(layer.childrenLegend && options.showGroupedTitle) {
                        html = widget.createNodeTitle(layer);
                    }
                } else if(layer.visible && layer.legend && layer.legend.url) {
                    html = $('<li/>').addClass('ebene' + layer.level);

                    if(options.showLayerTitle) {
                        html.append(widget.createTitle(layer));
                    }
                    html.append(widget.createImage(layer));
                }
            }

            return html;
        },

        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.open(callback);
        },

        /**
         * Render HTML
         *
         * @return strgin HTML jQuery object
         */
        render: function() {
            var widget = this;
            var sources = widget._getSources();
            var html = $("<ul/>");
            _.each(sources, function(source) {
                html.append(widget._createLayerHtml(source));
            });
            return html;
        },

        /**
         * On open handler
         */
        open: function(callback) {
            var widget = this;

            widget.callback = callback;

            if(widget.isPopUpDialog) {
                widget.popup(widget.htmlContainer.html());
            }
        },

        /**
         * On close
         */
        close: function() {
            var widget = this;

            if (widget.isPopUpDialog) {

                    if (widget.popupWindow && widget.popupWindow.$element) {
                        widget.popupWindow.destroy();
                        widget.popupWindow = null;
                    }

            }
            if (widget.callback) {
                widget.callback.call();
                widget.callback = null;
            }
        },

        /**
         * On ready handler
         */
        ready: function(callback) {
            var widget = this;
            if(widget.readyState) {
                if(typeof(callback ) === 'function') {
                    callback();
                }
            } else {
                widget.readyCallbacks.push(callback);
            }
        },

        /**
         * On ready handler
         */
        _ready: function() {
            var widget = this;

            _.each(widget.readyCallbacks, function(readyCallback){
                if(typeof(readyCallback ) === 'function') {
                    readyCallback();
                }
            })

            // Mark as ready
            widget.readyState = true;
            
            // Remove handlers
            widget.readyCallbacks.splice(0, widget.readyCallbacks.length);

        }

    });

})(jQuery);

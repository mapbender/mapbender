(function($) {

    $.widget("mapbender.mbLegend", {
        options: {
            autoOpen:                 true,
            target:                   null,
            elementType:              "dialog",
            displayType:              "list",
            hideEmptyLayers:          true,
            generateLegendGraphicUrl: false,
            showSourceTitle:          true,
            showLayerTitle:           true,
            showGroupedTitle:         true
        },

        callback:       null,

        /**
         * Widget constructor
         *
         * @private
         */
        _create: function() {
            if(!Mapbender.checkTarget("mbLegend", this.options.target)) {
                return;
            }
            this.htmlContainer = $('> .legends', this.element);
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        /**
         * Setup widget
         *
         * @private
         */
        _setup: function() {
            $(document).one('mbmapsourceloadend', $.proxy(this.onMapLoaded, this));
            this._trigger('ready');
        },

        /**
         * On map loaded
         *
         * @param e
         */
        onMapLoaded: function(e) {
            this.onMapLayerChanges();

            if (this.options.elementType === 'dialog') {
                this.element.hide(0);
                if (this.options.autoOpen) {
                    this.open();
                }
            }

            $(document)
                .bind('mbmapsourceadded mbmapsourcechanged mbmapsourcemoved mbmapsourcesreordered', $.proxy(this.onMapLayerChanges, this))
        },

        /**
         * On map layer changes handler
         *
         * @param e
         */
        onMapLayerChanges: function(e) {
            var html = this.render();

            this.htmlContainer.html(html);

            if (this.popupWindow) {
                this.popupWindow.open(html);
            }
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
                if(children_[i].childrenLegend || (children_[i].legend)) {
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
            var childLayers = layer.children || [];
            for (var i = 0; i < childLayers.length; ++i) {
                children = children.concat(this._getSubLayer(source, childLayers[i], "wms", level, []));
            }
            return children;
        },

        /**
         * Get legend
         *
         * @param layer
         * @return {*}
         */
        getLegendUrl: function(layer) {
            if (layer.options.legend) {
                var legend = layer.options.legend;
                if (this.options.generateLegendGraphicUrl && legend.graphic && !legend.url) {
                    return legend.graphic;
                }
                return legend.url || null;
            }
            return null;
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

            sublayerLeg["legend"] = this.getLegendUrl(sublayer);

            if (sublayer.children && sublayer.children.length) {
                if (widget.options.showGroupedTitle) {
                    children.push(sublayerLeg);
                }

                var childrenLegend = false;
                for (var i = 0; i < sublayer.children.length; ++i) {
                    var childLayer = sublayer.children[i];
                    if (this.getLegendUrl(childLayer)) {
                        childrenLegend = true;
                    }
                    children = children.concat(widget._getSubLayer(source, childLayer, type, level, []));
                }

                sublayerLeg['childrenLegend'] = childrenLegend;
            } else {
                children.push(sublayerLeg);
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
                .attr('src', layer.legend);
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

            if (layer.children) {
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
                if (layer.isNode) {
                    if(layer.childrenLegend && options.showGroupedTitle) {
                        html = widget.createNodeTitle(layer);
                    }
                } else if (layer.visible && layer.legend) {
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
            this.callback = callback;

            if (this.options.elementType === 'dialog') {
                if (!this.popupWindow) {
                    this.popupWindow = new Mapbender.Popup(this.getPopupOptions());
                    this.popupWindow.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popupWindow.open(this.htmlContainer.html());
                }
            }
        },

        /**
         * On close
         */
        close: function() {
            if (this.popupWindow) {
                this.popupWindow.destroy();
                this.popupWindow = null;
            }
            if (this.callback) {
                this.callback.call();
                this.callback = null;
            }
        },
        getPopupOptions: function() {
            var self = this;
            return {
                title: this.element.attr('title'),
                draggable: true,
                resizable: true,
                modal: false,
                closeOnESC: false,
                destroyOnClose: true,
                content: this.htmlContainer.html(),
                //width: 350,
                height: 500,
                buttons: [
                    {
                        label:    Mapbender.trans('mb.core.legend.popup.btn.ok'),
                        cssClass: 'button right',
                        callback: function() {
                            self.close();
                        }
                    }
                ]
            };
        }
    });

})(jQuery);

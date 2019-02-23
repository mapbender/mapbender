(function($) {

    $.widget("mapbender.mbLegend", {
        options: {
            autoOpen:                 true,
            target:                   null,
            elementType:              "dialog",
            displayType:              "list",
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
                this.popupWindow.open(this.element);
            }
        },

        /**
         *
         * @return {Array}
         * @private
         */
        _getSources: function() {
            var allLayers = [];
            var sources = Mapbender.Model.getSources();
            for (var i = (sources.length - 1); i > -1; i--) {
                var rootLayer = sources[i].configuration.children[0];
                if (rootLayer.state.visibility) {
                    allLayers.push(this._getSource(sources[i]));
                }
            }
            return allLayers;
        },

        /**
         *
         * @param source
         * @return {{sourceId, id, visible, title, level: *, children: *, childrenLegend: boolean}}
         * @private
         */
        _getSource: function(source) {
            var i;
            var rootLayer = source.configuration.children[0];
            var sourceData = {
                sourceId:       source.id,
                id:             rootLayer.options.id,
                title:          rootLayer.options.title,
                level:          1,
                childrenLegend: false
            };
            var childLegends = [];
            childLegends = childLegends.concat(this._getSubLayer(source, rootLayer, 2));
            for (i = 0; i < childLegends.length; i++) {
                if (childLegends[i].childrenLegend || (childLegends[i].legend)) {
                    sourceData.childrenLegend = true;
                }
            }
            sourceData.children = childLegends;
            return sourceData;
        },

        /**
         * Get legend
         *
         * @param layer
         * @return {*}
         */
        getLegendUrl: function(layer) {
            if (layer.options.legend) {
                return layer.options.legend.url || null;
            }
            return null;
        },

        /**
         *
         * @param source
         * @param sublayer
         * @param level
         * @return {*}
         * @private
         */
        _getSubLayer: function(source, sublayer, level) {
            var widget = this;
            var childLegends = [];
            var sublayerLeg = {
                sourceId: source.id,
                id:       sublayer.options.id,
                title:    sublayer.options.title,
                level:    level,
                isNode:   sublayer.children && sublayer.children.length,
                childrenLegend: false,
                legend: this.getLegendUrl(sublayer)
            };

            if (sublayer.children && sublayer.children.length) {
                if (widget.options.showGroupedTitle) {
                    childLegends.push(sublayerLeg);
                }

                for (var i = 0; i < sublayer.children.length; ++i) {
                    var childLayer = sublayer.children[i];
                    if (!childLayer.state.visibility) {
                        continue;
                    }
                    this._getSubLayer(source, childLayer, level + 1).map(function(childLegendData) {
                        if (childLegendData.legend || childLegendData.childrenLegend) {
                            sublayerLeg.childrenLegend = true;
                            childLegends.push(childLegendData);
                        }
                    });
                }
            } else {
                childLegends.push(sublayerLeg);
            }

            return childLegends;
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
                .addClass('subTitle')
            ;
        },

        /**
         *
         * @param layer
         * @private
         */
        createTitle: function(layer) {
            return $("<div/>")
                .text(layer.title)
                .addClass('subTitle')
            ;
        },
        /**
         * Create Image
         *
         * @param layer
         * @private
         */
        createImage: function(layer) {
            return $('<img/>')
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
                var visibleChildLayers = layer.children;
                var ul = widget.createLegendContainer(layer);

                if (!visibleChildLayers.length) {
                    return null;
                }

                if(options.showSourceTitle) {
                    ul.append(widget.createSourceTitle(layer));
                }

                visibleChildLayers.slice().reverse().map(function(childLayer) {
                    ul.append(widget._createLayerHtml(childLayer));
                });

                html = ul;
            } else {
                if (layer.isNode) {
                    if(layer.childrenLegend && options.showGroupedTitle) {
                        html = widget.createNodeTitle(layer);
                    }
                } else if (layer.legend) {
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
                    this.popupWindow = new Mapbender.Popup2(this.getPopupOptions());
                    this.popupWindow.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popupWindow.open();
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
                detachOnClose: true,
                content: [this.element],
                width: 350,
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

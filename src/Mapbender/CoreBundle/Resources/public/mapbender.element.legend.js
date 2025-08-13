(function ($) {

    $.widget("mapbender.mbLegend", $.mapbender.mbDialogElement, {
        options: {
            showSourceTitle: true,
            showLayerTitle: true,
            showGroupedLayerTitle: true
        },

        callback: null,
        mbMap: null,

        /**
         * Widget constructor
         *
         * @private
         */
        _create: function () {
            this.useDialog_ = !this.element.closest('.sideContent, .mobilePane').length;
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self._setup(mbMap);
            }, function () {
                Mapbender.checkTarget("mbLegend");
            });
        },

        /**
         * Setup widget
         *
         * @private
         */
        _setup: function (mbMap) {
            this.mbMap = mbMap;
            this.onMapLoaded();
            this._trigger('ready');
        },

        /**
         * On map loaded
         *
         * @param e
         */
        onMapLoaded: function (e) {
            this.onMapLayerChanges();
            if (this.checkAutoOpen()) {
                this.open();
            }
            var rerenderOn = [
                'mbmapsourceadded',
                'mbmapsourcechanged',
                'mbmapsourceremoved',
                'mbmapsourcelayersreordered',
                'mbmapsourcesreordered',
                'mbmapsourcelayerremoved'
            ];

            $(document).bind(rerenderOn.join(' '), $.proxy(this.onMapLayerChanges, this));
        },

        /**
         * On map layer changes handler
         *
         * @param e
         */
        onMapLayerChanges: function (e) {
            var html = this.render();

            this.element.html(html);
        },

        /**
         *
         * @return {Array}
         * @private
         */
        _getSources: function () {
            var sourceDataList = [];
            var sources = this.mbMap.getModel().getSources();
            for (var i = 0; i < sources.length; ++i) {
                var rootLayer = sources[i].getRootLayer();
                if (rootLayer.state.visibility && (!rootLayer.source || !rootLayer.source.layerset || rootLayer.source.layerset.selected)) {
                    // display in reverse map order
                    sourceDataList.unshift(this._getLayerData(sources[i], rootLayer, 1));
                }
            }
            return sourceDataList;
        },
        /**
         *
         * @param {Mapbender.Source} source
         * @param {Mapbender.SourceLayer} layer
         * @param {number} level
         * @return {Object}
         * @private
         */
        _getLayerData: function (source, layer, level) {
            var layerData = {
                id: layer.options.id,
                title: layer.options.title,
                level: level,
                legend: layer.getLegend(),
                children: []
            };

            if (layer.children && layer.children.length) {
                for (var i = 0; i < layer.children.length; ++i) {
                    var childLayer = layer.children[i];
                    if (!childLayer.state.visibility) {
                        continue;
                    }
                    var childLayerData = this._getLayerData(source, childLayer, level + 1);
                    if (childLayerData.legend || childLayerData.children.length) {
                        // display in reverse map order
                        layerData.children.unshift(childLayerData);
                    }
                }
            }
            return layerData;
        },

        /**
         *
         * @param layer
         * @private
         */
        createSourceTitle: function (layer) {
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
        createTitle: function (layer) {
            return $("<div/>")
                .text(layer.title)
                .addClass('subTitle')
                ;
        },
        createLegendForLayer: async function (layer) {
            switch (layer.legend.type) {
                case 'url':
                    return this.createImage(layer);
                case 'style':
                case 'canvas':
                    return this.createLegendForStyle(layer);
            }
        },
        createImage: function (layer) {
            return $('<img/>').attr('src', layer.legend.url);
        },

        createLegendForStyle: async function (layer) {
            layer.legend.layers = await Promise.resolve(layer.legend.layers);
            return (new LegendEntry(layer.legend)).getContainer();
        },

        /**
         * Create Legend Container
         * @param layer
         */
        createLegendContainer: function (layer) {
            return $(document.createElement('ul')).addClass('list-unstyled');
        },
        _createSourceHtml: function (sourceData) {
            const visibleChildLayers = sourceData.children;
            const ul = this.createLegendContainer(sourceData);

            if (!visibleChildLayers.length && (!sourceData.legend || !sourceData.legend.topLevel)) {
                return null;
            }

            if (this.options.showSourceTitle && sourceData.legend?.type !== "style") {
                ul.append(this.createSourceTitle(sourceData));
            }

            if (sourceData.legend && sourceData.legend.topLevel) {
                ul.append(this._createLayerHtml(sourceData));
                return ul;
            }

            for (var i = 0; i < visibleChildLayers.length; ++i) {
                var childLayer = visibleChildLayers[i];
                ul.append(this._createLayerHtml(childLayer));
            }

            return ul;
        },
        _createLayerHtml: function (layer) {
            var options = this.options;
            var $li = $('<li/>').addClass('ebene' + layer.level);

            if (layer.children.length) {
                if (this.options.showGroupedLayerTitle) {
                    $li.append(this.createTitle(layer));
                }
                var $ul = $('<ul/>').addClass('ebene' + layer.level);
                for (var i = 0; i < layer.children.length; ++i) {
                    $ul.append(this._createLayerHtml(layer.children[i]));
                }
                $li.append($ul);
                return $li;
            } else if (layer.legend) {
                if (options.showLayerTitle && layer.legend.type === "url") {
                    $li.append(this.createTitle(layer));
                }
                this.createLegendForLayer(layer).then((result) => $li.append(result));
            }
            return $li;
        },

        /**
         * Default action for mapbender element
         */
        defaultAction: function (callback) {
            this.open(callback);
        },

        /**
         * Render HTML
         *
         * @return strgin HTML jQuery object
         */
        render: function () {
            var widget = this;
            var sources = widget._getSources();
            var html = $('<ul/>');

            sources.forEach((source) => html.append(widget._createSourceHtml(source)));
            // strip top-level dummy <ul>
            return $(' > *', html);
        },

        /**
         * On open handler
         */
        open: function (callback) {
            this.callback = callback;

            if (this.useDialog_) {
                if (!this.popupWindow) {
                    this.popupWindow = new Mapbender.Popup(this.getPopupOptions());
                    this.popupWindow.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popupWindow.open();
                }
            }

            this.notifyWidgetActivated();

        },

        /**
         * On close
         */
        close: function () {
            this.notifyWidgetDeactivated();

            if (this.popupWindow) {
                this.popupWindow.destroy();
                this.popupWindow = null;
            }
            if (this.callback) {
                this.callback.call();
                this.callback = null;
            }
        },
        getPopupOptions: function () {
            return {
                title: this.element.attr('data-title'),
                draggable: true,
                resizable: true,
                modal: false,
                closeOnESC: false,
                detachOnClose: true,
                content: [this.element],
                cssClass: 'legend-dialog',
                width: 350,
                height: 500,
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.close'),
                        cssClass: 'btn btn-sm btn-light popupClose',
                        attrDataTest: 'mb-legend-btn-close'
                    }
                ]
            };
        }
    });

})(jQuery);

(function($) {

    $.widget("mapbender.mbLegend", {
        options: {
            autoOpen:                 true,
            target:                   null,
            noLegend:                 "No legend available",
            elementType:              "dialog",
            displayType:              "list",
            checkGraphic:             false,
            hideEmptyLayers:          true,         // @todo: currently not evaluated (behavior is always hide empty layers)
            generateLegendGraphicUrl: false,
            showSourceTitle:          true,
            showLayerTitle:           true,
            showGroupedTitle:         true,
            maxImgWidth:              0,
            maxImgHeight:             0
        },

        readyCallbacks: [],
        callback:       null,
        htmlContainer: null,
        $viewport: null,

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

            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            this.map.model.map.once('postrender', function(e) {
                widget.onMapLoaded(e);
            });
            /*
            $(document)
                .bind('mbmapsourceloadend', $.proxy(widget.onMapLoaded, widget))
                .bind('mbmapsourceloaderror', function(e) {
                    $.notify("Legend image element(#" + widget.uuid + ") couldn't not be initialized. No map - no legend.");
                });
                */

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
                .bind('mbmapsourceadded mbmapsourcechanged mbmapsourcemoved', $.proxy(widget.onMapLayerChanges, widget))
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
            if (!this.$viewport) {
                this.$viewport = $("<ul/>");
            }
            var sources = this.map.model.getActiveSources();
            // filter out "activated" sources that have their layers disabled or
            // currently only have feature info enabled
            sources = sources.filter(function(source) {
                return source.isVisible();
            });
            this._syncActiveSourceOrder(this.$viewport, sources.reverse());

            return this.$viewport;
        },
        _syncActiveSourceOrder: function($target, sources) {
            var i;
            var sourcesRendered = {};
            var $source;
            $('li.-fn-source', $target).each(function() {
                $source = $(this);
                var sourceId = $source.attr('data-sourceid');
                if (!sourceId) {
                    console.warn("Detaching source node without id", $source);
                } else {
                    // HACK: skip reuse of rendered sources
                    //   until we figure out how to reorder
                    //   the dom on layer order changes
                    // sourcesRendered[sourceId] = $source;
                }
                $source.detach();
            });
            for (i = 0; i < sources.length; ++i) {
                var source = sources[i];
                var sourceId = source.id;
                if (sourcesRendered[sourceId]) {
                    // reuse already rendered source DOM
                    $source = sourcesRendered[sourceId];
                    delete(sourcesRendered[sourceId]);
                } else {
                    // Render a new source node
                    $source = $('<li class="-fn-source">');
                    $source.attr('data-sourceid', sourceId);
                    var $layerTarget = $('<ul class=".-fn-source-layers">');
                    var $title = this.createSourceTitle({
                        title: source.getTitle(),
                        level: 0
                    });
                    if (!this.options.showSourceTitle) {
                        $title.addClass('hidden');
                    }
                    $layerTarget.append($title);
                    this._renderSource($layerTarget, source);
                    $layerTarget.appendTo($source);
                }
                $source.removeClass('hidden');
                $target.append($source);
            }
            // Hide already rendered legend nodes for currently disabled sources
            var remainingSourceIds = Object.keys(sourcesRendered);
            for (i = 0; i < remainingSourceIds.length; ++i) {
                var inactiveSourceId = remainingSourceIds[i];
                $source = sourcesRendered[inactiveSourceId];
                $source.addClass('hidden');
                $target.append($source);
            }
        },
        _hasLegend: function(layerDef) {
            var legendObj = this.getLegend(layerDef, this.options.generateLegendGraphicUrl);
            return legendObj && legendObj.url;
        },
        /**
         *
         * @param {jQuery} target to append to
         * @param {Mapbender.SourceModelOl4} source
         * @private
         * @returns {jQuery} generated node collection
         */
        _renderSource: function($target, source) {
            source.iterateActiveLayerDefs(
                this._renderRootLayer.bind(this, $target),
                this._hasLegend.bind(this)
            );
            if (!$('.-fn-layer', $target).get().length) {
                $target.addClass('hidden empty');
            }
        },
        _renderRootLayer: function($target, layerDef, siblingIndex, parents) {
            var $currentTarget = $target;
            for (var i = 0; i < (parents || []).length; ++i) {
                // render nodes bottom-up
                var parentLayerDef = parents[parents.length - i - 1];
                var parentLayerId = parentLayerDef.options.id;
                var $parent = $('ul.-fn-layergroup[data-layerid="' + parentLayerId + '"]', $currentTarget);
                if (!$parent.length) {
                    // parent node not rendered yet, do it
                    $parent = this._renderGroupNode($currentTarget, parentLayerDef, i);
                }
                $currentTarget = $parent;
            }
            var leafId = layerDef.options.id;
            var $leaf = $('>li.-fn-layer[data-layerid="' + leafId + '"]', $currentTarget);
            if (!$leaf.get().length) {
                // render a new node
                $leaf = this._renderLeaf(layerDef);
                // ... this._renderLeaf(layerDef, parents)
                $currentTarget.append($leaf);
            } else {
                // reveal already rendered node
                $leaf.removeClass('hidden');
            }
        },
        _renderGroupNode: function($target, layerDef, level) {
            //var $title = this.createNodeTitle('<h3>');
            //$title.text("GR " + layerDef.options.id + " / " + layerDef.options.name);
            var $title = this.createNodeTitle({
                title: layerDef.options.title,
                visible: (!this.options.showGroupedTitle && 'hidden') || null,
                id: layerDef.options.id,
                level: (level || 0) + 1
            });

            var $node = $('<li class="-fn-layergroup">');
            var $list = $('<ul>');
            $node.attr('data-layerid', layerDef.options.id);
            $list.attr('data-layerid', layerDef.options.id);
            $list.append($title);
            $node.append($list);
            $target.append($node);
            return $list;
        },
        _renderLeaf: function(layerDef, parents) {
            var leafId = layerDef.options.id;
            var $leaf = $('<li class="-fn-layer">');
            var $title = this.createTitle({
                title: layerDef.options.title,
                id: layerDef.options.id
            });
            if (!this.options.showLayerTitle) {
                $title.addClass('hidden');
            }
            $leaf.attr('data-layerid', leafId);
            $leaf.append($title);
            $leaf.append(this.createImage({
                id: leafId,
                legend: this.getLegend(layerDef, this.options.generateLegendGraphicUrl)
            }));
            return $leaf;
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

                if (widget.popup) {

                    if (widget.popupWindow.$element) {
                        widget.popupWindow.destroy();
                        widget.popupWindow = null;
                    }
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

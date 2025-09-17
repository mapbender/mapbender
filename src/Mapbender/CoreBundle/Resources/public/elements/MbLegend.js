(function () {
    class MbLegend extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            this.useDialog_ = !this.$element.closest('.sideContent, .mobilePane').length;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, () => {
                Mapbender.checkTarget('mbLegend');
            });
        }
        _setup(mbMap) {
            this.mbMap = mbMap;
            this.onMapLoaded();
            Mapbender.elementRegistry.markReady(this);
        }

        onMapLoaded(e) {
            this.onMapLayerChanges();
            if (this.checkAutoOpen()) {
                this.activateByButton();
            }
            const rerenderOn = [
                'mbmapsourceadded',
                'mbmapsourcechanged',
                'mbmapsourceremoved',
                'mbmapsourcelayersreordered',
                'mbmapsourcesreordered',
                'mbmapsourcelayerremoved'
            ];

            $(document).bind(rerenderOn.join(' '), $.proxy(this.onMapLayerChanges, this));
        }

        onMapLayerChanges(e) {
            const html = this.render();
            this.$element.html(html);
        }

        _getSources() {
            let sourceDataList = [];
            const sources = this.mbMap.getModel().getSources();
            for (let i = 0; i < sources.length; ++i) {
                let rootLayer = sources[i].getRootLayer();
                if (rootLayer.state.visibility && (!rootLayer.source || !rootLayer.source.layerset || rootLayer.source.layerset.selected)) {
                    // display in reverse map order
                    sourceDataList.unshift(this._getLayerData(sources[i], rootLayer, 1));
                }
            }
            return sourceDataList;
        }

        _getLayerData(source, layer, level) {
            let layerData = {
                id: layer.options.id,
                title: layer.options.title,
                level: level,
                legend: layer.getLegend(),
                children: []
            };

            if (layer.children && layer.children.length) {
                for (let i = 0; i < layer.children.length; ++i) {
                    const childLayer = layer.children[i];
                    if (!childLayer.state.visibility) {
                        continue;
                    }
                    let childLayerData = this._getLayerData(source, childLayer, level + 1);
                    if (childLayerData.legend || childLayerData.children.length) {
                        // display in reverse map order
                        layerData.children.unshift(childLayerData);
                    }
                }
            }
            return layerData;
        }

        createSourceTitle(layer) {
            return $('<li/>')
                .text(layer.title)
                .addClass('ebene' + layer.level)
                .addClass('title');
        }

        createTitle(layer) {
            return $('<div/>')
                .text(layer.title)
                .addClass('subTitle')
                ;
        }

        createLegendForLayer(layer) {
            switch (layer.legend.type) {
                case 'url':
                    return this.createImage(layer);
                case 'style':
                case 'canvas':
                    return this.createLegendForStyle(layer);
            }
        }

        createImage(layer) {
            return $('<img/>').attr('src', layer.legend.url);
        }

        async createLegendForStyle(layer) {
            layer.legend.layers = await Promise.resolve(layer.legend.layers);
            return (new LegendEntry(layer.legend)).getContainer();
        }

        createLegendContainer(layer) {
            return $(document.createElement('ul')).addClass('list-unstyled');
        }

        _createSourceHtml(sourceData) {
            const visibleChildLayers = sourceData.children;
            const ul = this.createLegendContainer(sourceData);

            if (!visibleChildLayers.length && (!sourceData.legend || !sourceData.legend.topLevel)) {
                return null;
            }

            if (this.options.showSourceTitle && sourceData.legend?.type !== 'style') {
                ul.append(this.createSourceTitle(sourceData));
            }

            if (sourceData.legend && sourceData.legend.topLevel) {
                ul.append(this._createLayerHtml(sourceData));
                return ul;
            }

            for (let i = 0; i < visibleChildLayers.length; ++i) {
                const childLayer = visibleChildLayers[i];
                ul.append(this._createLayerHtml(childLayer));
            }

            return ul;
        }

        _createLayerHtml(layer) {
            const widget = this;
            const options = widget.options;
            let $li = $('<li/>').addClass('ebene' + layer.level);

            if (layer.children.length) {
                if (this.options.showGroupedLayerTitle) {
                    $li.append(this.createTitle(layer));
                }
                let $ul = $('<ul/>').addClass('ebene' + layer.level);
                for (let i = 0; i < layer.children.length; ++i) {
                    $ul.append(this._createLayerHtml(layer.children[i]));
                }
                $li.append($ul);
                return $li;
            } else if (layer.legend) {
                if (options.showLayerTitle && layer.legend.type === 'url') {
                    $li.append(widget.createTitle(layer));
                }
                Promise.resolve(this.createLegendForLayer(layer)).then((result) => $li.append(result));
            }
            return $li;
        }

        render() {
            const widget = this;
            let sources = widget._getSources();
            let html = $('<ul/>');

            sources.forEach((source) => html.append(widget._createSourceHtml(source)));
            // strip top-level dummy <ul>
            return $(' > *', html);
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                draggable: true,
                resizable: true,
                modal: false,
                closeOnESC: false,
                detachOnClose: false,
                content: [this.$element],
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

        activateByButton(callback) {
            if (this.useDialog_) {
                super.activateByButton(callback);
            }
            this.notifyWidgetActivated();
        }

        closeByButton() {
            this.notifyWidgetDeactivated();
            super.closeByButton();
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbLegend = MbLegend;
})();

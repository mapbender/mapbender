window.Mapbender = window.Mapbender || {}
window.Mapbender.OgcApiFeature = window.Mapbender.OgcApiFeature || {}

window.Mapbender.OgcApiFeature.TooltipManager = class {
    static instance;

    /**
     * @param {ol.layer.Vector} nativeLayer
     * @param {OgcApiSourceLayer} mbLayer
     */
    static initialize(nativeLayer, mbLayer) {
        // TooltipManager should only be initialized once across sources
        if (!this.instance) {
            this.instance = new this();
        }
        this.instance.addVectorLayer(nativeLayer, mbLayer)
    }

    constructor() {
        // Mapbender.Model is not set until map element is ready
        Mapbender.elementRegistry.waitReady('.mb-element-map').then(() => this.initialize());
        this.hitTolerance = 7;
        this.debounceTime = 100;
        /** @type {{native: ol.layer.Vector, mb: OgcApiSourceLayer}[]} **/
        this.layers = [];
    }

    /**
     * @param {ol.layer.Vector} nativeLayer
     * @param {OgcApiSourceLayer} mbLayer
     */
    addVectorLayer(nativeLayer, mbLayer) {
        if (!this.layers.some((dict) => dict.mb === mbLayer)) {
            this.layers.push({native: nativeLayer, mb: mbLayer});
        }
    }

    initialize() {
        const olMap = Mapbender.Model.olMap;
        this.element = document.createElement('div');
        this.element.className = 'ogc-api-tooltip';
        this.element.style.display = 'none';
        this.hoverLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0).getNativeLayer();
        document.body.appendChild(this.element);

        this.overlay = new ol.Overlay({
            element: this.element,
            offset: [12, 0],
            positioning: 'center-left',
            stopEvent: false,
        });
        olMap.addOverlay(this.overlay);

        this._tooltipDebounce = null;
        olMap.on('pointermove', (evt) => {
            if (evt.dragging) {
                this._hideTooltip(true);
                return;
            }

            if (this._tooltipDebounce) {
                clearTimeout(this._tooltipDebounce);
            } else {
                this._hideTooltip(true);
            }

            this._tooltipDebounce = setTimeout(() => {
                this._tooltipDebounce = null;
                this._handlePointerMove(olMap, evt.pixel, evt.coordinate);
            }, this.debounceTime);
        });

        olMap.getViewport().addEventListener('mouseout', () => {
            this._hideTooltip(true);
        });
    }

    _handlePointerMove(olMap, pixel, coordinate) {
        let tooltips = [];
        let hoverFeatures = [];

        for (const layerDict of this.layers) {
            if (!layerDict.native.getVisible()) continue;

            const features = olMap.getFeaturesAtPixel(pixel, {
                layerFilter: (l) => l === layerDict.native,
                hitTolerance: this.hitTolerance,
            });

            for (const feature of features) {
                const tooltip = layerDict.mb.tooltipForFeature(feature);
                if (tooltip) tooltips.push(tooltip);

                if (layerDict.mb.hasHoverStyle()) {
                    const hoverFeature = new ol.Feature(feature.getGeometry());
                    hoverFeature.setStyle(layerDict.mb.getHoverStyle());
                    hoverFeatures.push(hoverFeature);
                }
            }
        }

        this.hoverLayer.getSource().clear();
        this.hoverLayer.getSource().addFeatures(hoverFeatures);

        if (!tooltips.length) {
            this._hideTooltip(false);
            olMap.getTargetElement().classList.remove('tooltip-active');
            return;
        }

        olMap.getTargetElement().classList.add('tooltip-active');
        this.element.innerHTML = '';
        let first = true;
        for (const fragment of tooltips) {
            if (!first) {
                this.element.appendChild(this.createDivider());
            }
            first = false;
            this.element.appendChild(fragment);
        }
        this.element.style.display = '';
        this.overlay.setPosition(coordinate);
    }

    createDivider() {
        const hrElement = document.createElement('hr');
        hrElement.className = 'ogc-api-tooltip-divider';
        return hrElement;
    }

    _hideTooltip(clearHoverLayer) {
        if (clearHoverLayer) {
            this.hoverLayer.getSource().clear();
        }
        if (this.element) {
            this.element.style.display = 'none';
        }
    }
}

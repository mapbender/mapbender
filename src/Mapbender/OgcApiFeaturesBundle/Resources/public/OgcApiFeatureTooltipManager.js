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
                this._hideTooltip();
                return;
            }

            clearTimeout(this._tooltipDebounce);
            this._tooltipDebounce = setTimeout(() => {
                this._handlePointerMove(olMap, evt.pixel, evt.coordinate);
            }, this.debounceTime);
        });

        olMap.getViewport().addEventListener('mouseout', () => {
            this._hideTooltip();
        });
    }

    _handlePointerMove(olMap, pixel, coordinate) {
        let hit = null;
        let hitChild = null;

        let tooltips = [];
        for (const layerDict of this.layers) {
            if (!layerDict.native.getVisible()) continue;

            const features = olMap.getFeaturesAtPixel(pixel, {
                layerFilter: (l) => l === layerDict.native,
                hitTolerance: this.hitTolerance,
            });

            for (const feature of features) {
                const tooltip = layerDict.mb.tooltipForFeature(feature);
                if (tooltip) tooltips.push(tooltip);
            }
        }

        if (!tooltips.length) {
            this._hideTooltip();
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

    _hideTooltip() {
        if (this.element) {
            this.element.style.display = 'none';
        }
    }
}

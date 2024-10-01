/**
 * @typedef {Object} LegendDefinitionStyle
 * @property {string} [fillColor] - Optional fill color.
 * @property {number} [fillOpacity] - Optional fill opacity (0-1).
 * @property {string} [strokeColor] - Optional stroke color.
 * @property {number} [strokeOpacity] - Optional stroke opacity (0-1).
 * @property {number} [strokeWidth] - Optional stroke width (in pixels).
 * @property {string} [fontFamily] - Optional font family.
 * @property {string} [fontColor] - Optional font color.
 * @property {string | number} [fontWeight] - Optional font weight.
 * @property {number} [labelOutlineWidth] - Optional label outline width (in pixels).
 * @property {string} [labelOutlineColor] - Optional label outline color.
 */

/**
 * @typedef {Object} LegendDefinitionLayer
 * @property {string} title
 * @property {LegendDefinitionStyle} style
 */

/**
 * @typedef {Object} LegendDefinition
 * @property {'style'} type - Indicates the type is a style (and not an url)
 * @property {string} title - Heading for the layer group
 * @property {LegendDefinitionLayer} layers
 */

/**
 * This class is used for displaying legend entries that are rendered by the browser,
 * e.g. for layers that are created from digitizer entries
 */
class LegendEntry {
    /**
     * @param {LegendDefinition} legendDefinition
     */
    constructor(legendDefinition) {
        this.legendDefinition = legendDefinition;
        this.container = this._createContainer();

        this._addHeading();
        legendDefinition.layers.forEach((layer) => {
            const subContainer = document.createElement("div");
            subContainer.append(this._createCanvasForLayer("Label", 35, 15, layer.style));
            subContainer.append(this._createLayerHeading(layer.title));
            this.container.append(subContainer);
        });
    }

    getContainer() {
        return this.container;
    }

    _createContainer() {
        const container = document.createElement("div");
        container.className = "legend-custom";
        return container;
    }

    _addHeading() {
        const heading = document.createElement("h3");
        heading.innerText = this.legendDefinition.title;
        heading.className = "legend-custom__heading";
        this.container.append(heading);
    }

    _createLayerHeading(title) {
        const heading = document.createElement("h4");
        heading.innerText = title;
        heading.className = "legend-custom__layer";
        return heading;
    }

    /**
     *
     * @param {String} label
     * @param {number} width
     * @param {number} height
     * @param {LegendDefinitionStyle} style
     * @returns {HTMLCanvasElement}
     * @private
     */
    _createCanvasForLayer(label, width, height, style) {

        const canvas = document.createElement('canvas');
        canvas.className = "legend-custom__canvas";
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');

        // Fill the shape
        if (style.fillColor) {
            ctx.fillStyle = this.hexToRgba(style.fillColor, style.fillOpacity);
            ctx.fillRect(0, 0, width, height);
        }

        // Stroke the shape
        if (style.strokeColor && style.strokeWidth > 0) {
            ctx.strokeStyle = this.hexToRgba(style.strokeColor, style.strokeOpacity);
            ctx.lineWidth = style.strokeWidth;
            ctx.strokeRect(0, 0, width, height);
        }

        // Draw the label
        if (label) {
            ctx.font = `${style.fontWeight} 9px ${style.fontFamily}`;
            ctx.fillStyle = style.fontColor;

            // Measure the text to find the center position
            const textMetrics = ctx.measureText(label);
            const textX = (width - textMetrics.width) / 2;
            const textY = (height + 9) / 2;

            if (style.labelOutlineWidth > 0) {
                ctx.lineWidth = style.labelOutlineWidth;
                ctx.strokeStyle = style.labelOutlineColor;
                ctx.strokeText(label, textX, textY);
            }

            ctx.fillText(label, textX, textY);
        }
        return canvas;
    }

    hexToRgba(hex, opacity = 1) {
        let r = 0, g = 0, b = 0;
        if (hex.length === 4) {
            r = parseInt(hex[1] + hex[1], 16);
            g = parseInt(hex[2] + hex[2], 16);
            b = parseInt(hex[3] + hex[3], 16);
        } else if (hex.length === 7) {
            r = parseInt(hex[1] + hex[2], 16);
            g = parseInt(hex[3] + hex[4], 16);
            b = parseInt(hex[5] + hex[6], 16);
        }
        return `rgba(${r},${g},${b},${opacity})`;
    }
}

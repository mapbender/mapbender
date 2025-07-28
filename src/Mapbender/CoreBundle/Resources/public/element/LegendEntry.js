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
 * @property {boolean} [circle] - Optional indicator if a circle should be drawn. Requires radius to be set.
 * @property {number} [radius] - Optional radius for a circle (in pixels).
 * @property {string} [image] - Optional url to an image. If set, all stroke*, font* and fill* properties are ignored. Use imageX, imageY, imageWidth and imageHeight when using a sprite.
 * @property {number} [imageX] - Optional x-offset for the image (in pixels).
 * @property {number} [imageY] - Optional y-offset for the image (in pixels).
 * @property {number} [imageWidth] - Optional width of the image for sprites (in pixels).
 * @property {number} [imageHeight] - Optional height of the image for sprites (in pixels).
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
        this.canvas = legendDefinition.type === "canvas";

        this._addHeading();
        legendDefinition.layers.forEach((layer) => {
            const subContainer = document.createElement("div");
            subContainer.append(
                this.canvas ? layer.canvas : this._createCanvasForLayer(layer.style.label ? "Label" : null, 35, 15, layer.style)
            );
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

        if (style.image) {
            this.drawExternalImage(style, width, height, ctx);
            return canvas;
        }
        // Fill the shape
        if (style.fillColor) {
            ctx.fillStyle = this.colorToRgba(style.fillColor, style.fillOpacity);
            if (!style.circle) ctx.fillRect(0, 0, width, height);
        }

        // Stroke the shape
        if (style.strokeColor && style.strokeWidth > 0) {
            ctx.strokeStyle = this.colorToRgba(style.strokeColor, style.strokeOpacity);
            ctx.lineWidth = style.strokeWidth;

            if (style.fillColor) {
                ctx.strokeRect(0, 0, width, height);
            } else if (!style.circle) {
                // If no fill color, draw a line in the middle of the canvas
                ctx.strokeRect(0, height / 2 - style.strokeWidth / 2, width, style.strokeWidth);
            }
        }

        if (style.circle) {
            // Draw a circle in the center of the canvas
            ctx.beginPath();
            ctx.arc(width / 2, height / 2, style.radius || 5, 0, Math.PI * 2);
            ctx.fill();
            if (style.strokeColor && style.strokeWidth > 0) {
                ctx.stroke();
            }
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

    drawExternalImage(style, width, height, ctx) {
        const image = new Image();
        image.src = style.image;
        image.onload = () => {
            const sw = style.imageWidth || image.width;
            const sh = style.imageHeight || image.height;

            // Scale down to target size while maintaining aspect ratio
            const scale = Math.min(1, Math.min(width / sw, height / sh));
            const dw = sw * scale;
            const dh = sh * scale;

            // center the image in the canvas
            const dx = (width - dw) / 2;
            const dy = (height - dh) / 2;
            ctx.drawImage(image, style.imageX, style.imageY, style.imageWidth, style.imageHeight, dx, dy, dw, dh);
        }
    }

    colorToRgba(hex, opacity = 1) {
        if (typeof hex !== 'string') {
            return `rgba(0, 0, 0, ${opacity || 0})`; // Default to black if input is not a string
        }

        if (hex.startsWith("rgba(")) {
            return hex;
        }
        if (hex.startsWith("rgb(")) {
            const rgb = hex.slice(4, -1).split(',').map(Number);
            return `rgba(${rgb[0]},${rgb[1]},${rgb[2]},${opacity})`;
        }

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

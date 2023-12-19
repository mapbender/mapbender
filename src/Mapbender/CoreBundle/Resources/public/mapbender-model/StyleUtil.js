window.Mapbender = Mapbender || {};
window.Mapbender.StyleUtil = (function() {
    var _svgStyleDefaults, cssKeywordColors, _svgCallbackDefaultProps;
    var stripAssetUrlRxp = /^.*?(\/)(bundles\/.*)/;

    var methods = {
        /**
         * @param {Object} style
         * @param {String} colorProp
         * @param {String} opacityProp
         * @return {Array<Number>} four entries: r,g,b (integer in [0..255]), then alpha (float in [0...1])
         */
        parseSvgColor: function(style, colorProp, opacityProp) {
            var colorRule = this._resolveSvgDefault(style, colorProp);
            var alphaRule = this._resolveSvgDefault(style, opacityProp);
            var components = this._parseCssColor(colorRule);
            if (components.length < 4) {
                var parsedAlpha = parseFloat(alphaRule);
                if (!isNaN(parsedAlpha)) {
                    components.push(parsedAlpha);
                } else {
                    components.push(1.0);
                }
            }
            return components;
        },
        /**
         * @param {String} cssColor
         * @return {Array<Number>} four entries: r,g,b (integer in [0..255]), then alpha (float in [0...1])
         */
        parseCssColor: function(cssColor) {
            var components = this._parseCssColor(cssColor);
            if (components.length < 4) {
                components.push(1.0);
            }
            return components;
        },
        /**
         * @param {String} cssColor
         * @param {String} colorProp
         * @param {String} [opacityProp]
         * @return {Object}
         */
        cssColorToSvgRules: function(cssColor, colorProp, opacityProp) {
            var components = this.parseCssColor(cssColor);
            return this._componentsToSvgRules(components, colorProp, opacityProp);
        },
        /**
         * @param {Object} style
         * @param {String} colorProp
         * @param {String} opacityProp
         * @return {String}
         */
        svgToCssColorRule: function(style, colorProp, opacityProp) {
            var components = this.parseSvgColor(style, colorProp, opacityProp);
            return this._componentsToRgbaRule(components);
        },
        /**
         * @param {Object} style
         * @return {Object}
         */
        addSvgDefaults: function(style) {
            var withDefaults = Object.assign({}, _svgStyleDefaults, style);
            _svgCallbackDefaultProps.forEach(function(prop) {
                var value = withDefaults[prop];
                if (typeof value === 'function') {
                    withDefaults['prop'] = (value)(withDefaults);
                }
            });
            return withDefaults;
        },
        fixSvgStyleAssetUrls: function(style) {
            if (style && style.externalGraphic) {
                style.externalGraphic = this._fixAssetPath(style.externalGraphic);
            }
        },
        _fixAssetPath: function(url) {
            var urlOut = url.replace(stripAssetUrlRxp, '$2');
            if (urlOut === url && (urlOut || '').indexOf('bundles/') !== 0) {
                console.warn("Asset path could not be resolved to local bundles reference", url);
                return false;
            } else {
                return urlOut;
            }
        },
        /**
         * @param {Array<Number>} components
         * @param {String} colorProp
         * @param {String} [opacityProp]
         * @return {Object}
         */
        _componentsToSvgRules: function(components, colorProp, opacityProp) {
            var ruleObject = {};
            ruleObject[colorProp] = this._componentsToHexRgbRule(components);
            if (opacityProp) {
                if (typeof (components[3]) !== undefined) {
                    ruleObject[opacityProp] = components[3];
                } else {
                    ruleObject[opacityProp] = 1.0;
                }
            }
            return ruleObject;
        },
        /**
         * @param {Array<Number>} components
         * @return {String}
         */
        _componentsToHexRgbRule: function(components) {
            var digitPairs = components.slice(0, 3).map(function(component) {
                return ('0' + component.toString(16)).slice(-2);
            });
            return ['#', digitPairs.join('')].join('');
        },
        /**
         * @param {Array<Number>} components
         * @return {string}
         * @private
         */
        _componentsToRgbaRule: function(components) {
            var alpha;
            if (typeof (components[3]) !== undefined) {
                alpha = components[3];
            } else {
                alpha = 1.0;
            }
            var parts = [
                'rgba(',
                components[0], ',',
                components[1], ',',
                components[2], ',',
                alpha,
                ')'
            ];
            return parts.join('')
        },
        _parseCssColor: function(rule) {
            var hexPairs;
            var keywordRule = cssKeywordColors[rule];
            if (typeof keywordRule !== 'undefined') {
                hexPairs = [keywordRule.slice(1, 3), keywordRule.slice(3, 5), keywordRule.slice(5, 7)];
            } else if (/^#[a-fA-F0-9]{3,8}$/i.test(rule)) {
                if (rule.length === 4 || rule.length === 5) {
                    // short form, e.g. '#9cf', #9cf4, expand to six/eight digits
                    hexPairs = [
                        '' + rule[1] + rule[1],
                        '' + rule[2] + rule[2],
                        '' + rule[3] + rule[3],
                    ];
                    if (rule.length === 5) hexPairs.push('' + rule[4] + rule[4]);
                } else if (rule.length === 7) {
                    hexPairs = [rule.slice(1, 3), rule.slice(3, 5), rule.slice(5, 7)];
                } else if (rule.length === 9) {
                    hexPairs = [rule.slice(1, 3), rule.slice(3, 5), rule.slice(5, 7), rule.slice(7, 9)];
                }
            }
            if (hexPairs) {
                const mapped = hexPairs.map(function(hexPair) {
                    return parseInt(hexPair, 16);
                });
                if (mapped.length === 4) mapped[3] /= 255;
                return mapped;
            } else {
                var matches = (rule || '').match(/^rgba\((\d+),\s*(\d+),\s*(\d+),\s*((?:\d*\.\d+)|(?:\d+(?:\.\d*)?))\)$/);
                if (!matches) {
                    matches = (rule || '').match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
                }
                if (matches) {
                    var components = [matches[1], matches[2], matches[3]].map(function(component) {
                        return parseInt(component);
                    });
                    if (typeof (matches[4]) !== 'undefined') {
                        components.push(parseFloat(matches[4]));
                    }
                    return components;
                }
            }
            throw new Error("Could not parse css color input " + rule);
        },
        _resolveSvgDefault: function(styleObject, propName) {
            var styleValue = styleObject[propName];
            if (typeof styleValue !== 'undefined') {
                return styleValue;
            }
            var withDefaults = Object.assign({}, _svgStyleDefaults, styleObject);
            var value = withDefaults[propName];
            while (typeof value === 'function') {
                value = (value)(withDefaults);
            }
            return value;
        }
    };

    _svgStyleDefaults = {
        /** @see http://dev.openlayers.org/releases/OpenLayers-2.13.1/docs/files/OpenLayers/Feature/Vector-js.html#OpenLayers.Feature.Vector.Constants */
        /** @see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Feature/Vector.js#L373 */
        // @todo: separate hoverFillColor etc?
        fill: true,
        fillColor: '#ee9900',
        fillOpacity: 0.4,
        strokeColor: '#ee9900',
        strokeOpacity: 1,
        strokeWidth: 1,
        strokeLinecap: 'round',
        strokeDashstyle: 'solid',
        pointRadius: 6,
        fontColor: '#000000',
        labelAlign: 'cm',
        labelOutlineColor: 'white',
        labelOutlineWidth: 3,
        fontOpacity: 1.0,
        labelXOffset: 0,
        labelYOffset: 0,
        labelOutlineOpacity: function(styleObject) {
            var fallback = styleObject['fontOpacity'];
            if (typeof fallback !== 'undefined') {
                return fallback;
            } else {
                return 1.0;
            }
        }
    };
    _svgCallbackDefaultProps = [
        'labelOutlineOpacity'
    ];
    cssKeywordColors = {
        /** @see https://developer.mozilla.org/en-US/docs/Web/CSS/color_value */
        // Automated extraction hint (needs some post cleanups)
        // Array.prototype.map.call($('#colors_table tbody').children, function(tr) { return Array.prototype.slice.call(tr.children, -3, -1); }).map(function(trA) { return [trA[0].textContent, "'" + trA[1].textContent + "'"].join(': '); }).join(",\n");
        black: '#000000',
        silver: '#c0c0c0',
        gray: '#808080',
        white: '#ffffff',
        maroon: '#800000',
        red: '#ff0000',
        purple: '#800080',
        fuchsia: '#ff00ff',
        green: '#008000',
        lime: '#00ff00',
        olive: '#808000',
        yellow: '#ffff00',
        navy: '#000080',
        blue: '#0000ff',
        teal: '#008080',
        aqua: '#00ffff',
        orange: '#ffa500',
        aliceblue: '#f0f8ff',
        antiquewhite: '#faebd7',
        aquamarine: '#7fffd4',
        azure: '#f0ffff',
        beige: '#f5f5dc',
        bisque: '#ffe4c4',
        blanchedalmond: '#ffebcd',
        blueviolet: '#8a2be2',
        brown: '#a52a2a',
        burlywood: '#deb887',
        cadetblue: '#5f9ea0',
        chartreuse: '#7fff00',
        chocolate: '#d2691e',
        coral: '#ff7f50',
        cornflowerblue: '#6495ed',
        cornsilk: '#fff8dc',
        crimson: '#dc143c',
        cyan: '#00ffff',
        darkblue: '#00008b',
        darkcyan: '#008b8b',
        darkgoldenrod: '#b8860b',
        darkgray: '#a9a9a9',
        darkgreen: '#006400',
        darkgrey: '#a9a9a9',
        darkkhaki: '#bdb76b',
        darkmagenta: '#8b008b',
        darkolivegreen: '#556b2f',
        darkorange: '#ff8c00',
        darkorchid: '#9932cc',
        darkred: '#8b0000',
        darksalmon: '#e9967a',
        darkseagreen: '#8fbc8f',
        darkslateblue: '#483d8b',
        darkslategray: '#2f4f4f',
        darkslategrey: '#2f4f4f',
        darkturquoise: '#00ced1',
        darkviolet: '#9400d3',
        deeppink: '#ff1493',
        deepskyblue: '#00bfff',
        dimgray: '#696969',
        dimgrey: '#696969',
        dodgerblue: '#1e90ff',
        firebrick: '#b22222',
        floralwhite: '#fffaf0',
        forestgreen: '#228b22',
        gainsboro: '#dcdcdc',
        ghostwhite: '#f8f8ff',
        gold: '#ffd700',
        goldenrod: '#daa520',
        greenyellow: '#adff2f',
        grey: '#808080',
        honeydew: '#f0fff0',
        hotpink: '#ff69b4',
        indianred: '#cd5c5c',
        indigo: '#4b0082',
        ivory: '#fffff0',
        khaki: '#f0e68c',
        lavender: '#e6e6fa',
        lavenderblush: '#fff0f5',
        lawngreen: '#7cfc00',
        lemonchiffon: '#fffacd',
        lightblue: '#add8e6',
        lightcoral: '#f08080',
        lightcyan: '#e0ffff',
        lightgoldenrodyellow: '#fafad2',
        lightgray: '#d3d3d3',
        lightgreen: '#90ee90',
        lightgrey: '#d3d3d3',
        lightpink: '#ffb6c1',
        lightsalmon: '#ffa07a',
        lightseagreen: '#20b2aa',
        lightskyblue: '#87cefa',
        lightslategray: '#778899',
        lightslategrey: '#778899',
        lightsteelblue: '#b0c4de',
        lightyellow: '#ffffe0',
        limegreen: '#32cd32',
        linen: '#faf0e6',
        magenta: '#ff00ff',
        mediumaquamarine: '#66cdaa',
        mediumblue: '#0000cd',
        mediumorchid: '#ba55d3',
        mediumpurple: '#9370db',
        mediumseagreen: '#3cb371',
        mediumslateblue: '#7b68ee',
        mediumspringgreen: '#00fa9a',
        mediumturquoise: '#48d1cc',
        mediumvioletred: '#c71585',
        midnightblue: '#191970',
        mintcream: '#f5fffa',
        mistyrose: '#ffe4e1',
        moccasin: '#ffe4b5',
        navajowhite: '#ffdead',
        oldlace: '#fdf5e6',
        olivedrab: '#6b8e23',
        orangered: '#ff4500',
        orchid: '#da70d6',
        palegoldenrod: '#eee8aa',
        palegreen: '#98fb98',
        paleturquoise: '#afeeee',
        palevioletred: '#db7093',
        papayawhip: '#ffefd5',
        peachpuff: '#ffdab9',
        peru: '#cd853f',
        pink: '#ffc0cb',
        plum: '#dda0dd',
        powderblue: '#b0e0e6',
        rosybrown: '#bc8f8f',
        royalblue: '#4169e1',
        saddlebrown: '#8b4513',
        salmon: '#fa8072',
        sandybrown: '#f4a460',
        seagreen: '#2e8b57',
        seashell: '#fff5ee',
        sienna: '#a0522d',
        skyblue: '#87ceeb',
        slateblue: '#6a5acd',
        slategray: '#708090',
        slategrey: '#708090',
        snow: '#fffafa',
        springgreen: '#00ff7f',
        steelblue: '#4682b4',
        tan: '#d2b48c',
        thistle: '#d8bfd8',
        tomato: '#ff6347',
        turquoise: '#40e0d0',
        violet: '#ee82ee',
        wheat: '#f5deb3',
        whitesmoke: '#f5f5f5',
        yellowgreen: '#9acd32',
        rebeccapurple: '#663399'
    };
    return methods;
}());


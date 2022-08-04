(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        /** one of 'WKT', 'GeoJSON' */
        geom_format: null,
        token_regex: null,
        token_regex_in: null,
        token_regex_out: null,
        label_attribute: null,
        geom_attribute: null,
        result_buffer: null,
        result_minscale: null,
        result_maxscale: null,
        result_icon_url: null,
        result_icon_offset: null,
        sourceSrs: 'EPSG:4326',
        delay: 0
    },

    layer: null,
    mbMap: null,
    iconUrl_: null,

    _create: function() {
        this.iconUrl_ = this.options.result_icon_url || null;
        this.initializeAutocompletePosition_()
        if (this.options.result_icon_url && !/^(\w+:)?\/\//.test(this.options.result_icon_url)) {
            // Local, asset-relative
            var parts = [
                Mapbender.configuration.application.urls.asset.replace(/\/$/, ''),
                this.options.result_icon_url.replace(/^\//, '')
            ];
            this.iconUrl_ = parts.join('/');
        }
        var self = this;
        Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
            self.mbMap = mbMap;
            self._setup();
        });
    },
    initializeAutocompletePosition_: function() {
        /** @see https://api.jqueryui.com/autocomplete/#option-position */
        var vertical = this.element.closest('.toolBar.bottom,.anchored-element-wrap-lb,.anchored-element-wrap-rb').length ? 'up' : 'down';
        var horizontal = 'right';
        if (this.element.closest('.toolBar,.anchored-element-wrap').length) {
            var windowWidth = $('html').get(0).clientWidth;
            var node = this.element.get(0);
            var ownWidth = node.clientWidth;
            var distanceLeft = 0;
            do {
                distanceLeft += node.offsetLeft;
                node = node.offsetParent;
            } while (node);
            var distanceRight = windowWidth - distanceLeft - ownWidth;
            if (windowWidth && distanceRight >= windowWidth / 2) {
                horizontal = 'right';
            } else {
                horizontal = 'left';
            }
        }
        // Adds .left-up / .left-down / .right-up / .right-down
        $('.autocompleteWrapper', this.element).addClass([horizontal, vertical].join('-'));
    },
    _setup: function() {
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';
        this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
        if (this.iconUrl_) {
            var offset = (this.options.result_icon_offset || '').split(new RegExp('[, ;]')).map(function(x) {
                return parseInt(x) || 0;
            });
            this.layer.addCustomIconMarkerStyle('simplesearch', this.iconUrl_, offset[0], offset[1]);
        }

        // @todo: this has never been customizable. Always used FOM Autcomplete default 2.
        var minLength = 2;

        searchInput.autocomplete({
            appendTo: searchInput.closest('.autocompleteWrapper').get(0),
            delay: self.options.delay,
            minLength: minLength,
            /** @see https://api.jqueryui.com/autocomplete/#option-source */
            source: function(request, responseCallback) {
                var term = self._tokenize(request.term);
                if (!term || term.length < minLength) {
                    responseCallback([]);
                    return;
                }
                $.getJSON(url, {term: term})
                    .then(function(response) {
                        var formatted =  (response || []).map(function(item) {
                            return Object.assign(item, {
                                label: self._formatLabel(item)
                            });
                        }).filter(function(item) {
                            var geomEmpty = !item[self.options.geom_attribute];
                            if (geomEmpty) {
                                console.warn("Missing geometry in SimpleSearch item", item);
                            }
                            return item.label && !geomEmpty;
                        });
                        responseCallback(formatted);

                    }, function() {
                        responseCallback([]);
                    })
                ;
            },
            position: {
                of: false
            },
            select: function(event, ui) {
                self._onAutocompleteSelected(ui.item);
            }
        });
        // On manual submit (enter key, submit button), trigger autocomplete manually
        this.element.on('submit', function(evt) {
            evt.preventDefault();
            searchInput.autocomplete("search");
        });
        this.element.on('click', '.-fn-search', function() {
            searchInput.autocomplete('search');
        });
        this.mbMap.element.on('mbmapsrschanged', function(event, data) {
            self.layer.retransform(data.from, data.to);
        });
    },
    _parseFeature: function(doc) {
        switch ((this.options.geom_format || '').toUpperCase()) {
            case 'WKT':
                return this.mbMap.getModel().parseWktFeature(doc, this.options.sourceSrs);
            case 'GEOJSON':
                return this.mbMap.getModel().parseGeoJsonFeature(doc, this.options.sourceSrs);
            default:
                throw new Error("Invalid geom_format " + this.options.geom_format);
        }
    },
    /**
     * @param {Object} obj
     * @param {String} path
     * @return {string|null}
     */
    _extractAttribute: function(obj, path) {
        var props = obj;
        var parts = path.split('.');
        var last = parts.pop();
        for (var i = 0; i < parts.length; ++i) {
            props = props && props[parts[i]];
            if (!props) {
                break;
            }
        }
        if (props && (props[last] || (typeof props[last] === 'number'))) {
            return [props[last]].join('');  // force to string
        } else {
            return null;
        }
    },
    _formatLabel: function(doc) {
        // Find / match '${attribute_name}' / '${nested.attribute.path}' placeholders
        var templateParts = this.options.label_attribute.split(/\${([^}]+)}/g);
        if (templateParts.length > 1) {
            var parts = [];
            for (var i = 0; i < templateParts.length; i += 2) {
                var fixedText = templateParts[i];
                // NOTE: attributePath is undefined (index >= length of list) if label_attribute defines static text after last placeholder
                var attributePath = templateParts[i + 1];
                var attributeValue = attributePath && this._extractAttribute(doc, attributePath);
                if (attributeValue) {
                    parts.push(fixedText);
                    parts.push(attributeValue);
                } else {
                    // Show text before label component only if attribute data was non-empty
                    if (!attributePath) {
                        parts.push(fixedText);
                    }
                }
            }
            return parts.join('').replace(/(^[\s.,:]+)|([\s.,:]+$)/g, '');
        } else {
            return this._extractAttribute(doc, this.options.label_attribute);
        }
    },
    _onAutocompleteSelected: function(item) {
        var feature = this._parseFeature(item[this.options.geom_attribute]);

        var zoomToFeatureOptions = {
            maxScale: parseInt(this.options.result_maxscale) || null,
            minScale: parseInt(this.options.result_minscale) || null,
            buffer: parseInt(this.options.result_buffer) || null
        };
        this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        this._hideMobile();
        this._setFeatureMarker(feature);
    },
    _setFeatureMarker: function(feature) {
        this.layer.clear();
        Mapbender.vectorLayerPool.raiseElementLayers(this);
        var layer = this.layer;
        // @todo: add feature center / centroid api
        var bounds = Mapbender.mapEngine.getFeatureBounds(feature);
        var center = {
            lon: .5 * (bounds.left + bounds.right),
            lat: .5 * (bounds.top + bounds.bottom)
        };
        // fallback for broken icon: render a simple point geometry
        var onMissingIcon = function() {
            layer.addMarker(center.lon, center.lat);
        };
        if (this.iconUrl_) {
            layer.addIconMarker('simplesearch', center.lon, center.lat).then(null, onMissingIcon);
        } else {
            onMissingIcon();
        }
    },

    _hideMobile: function() {
        $('.mobileClose', $(this.element).closest('.mobilePane')).click();
    },

    _tokenize: function(string) {
        if (!(this.options.token_regex_in && this.options.token_regex_out)) return string;

        if (this.options.token_regex) {
            var regexp = new RegExp(this.options.token_regex, 'g');
            string = string.replace(regexp, " ");
        }

        var tokens = string.split(' ');
        var regex = new RegExp(this.options.token_regex_in);
        for(var i = 0; i < tokens.length; i++) {
            tokens[i] = tokens[i].replace(regex, this.options.token_regex_out);
        }

        return tokens.join(' ');
    }
});

})(jQuery);

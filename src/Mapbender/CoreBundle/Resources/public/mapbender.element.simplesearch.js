(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        url: null,
        /** one of 'WKT', 'GeoJSON' */
        token_regex: null,
        token_regex_in: null,
        token_regex_out: null,
        label_attribute: null,
        geom_attribute: null,
        geom_format: null,
        result_buffer: null,
        result_minscale: null,
        result_maxscale: null,
        result_icon_url: null,
        result_icon_offset: null,
        delay: 0
    },

    marker: null,
    layer: null,
    iconUrl_: null,
    acPosition_: undefined,

    _create: function() {
        this.iconUrl_ = this.options.result_icon_url || null;
        if (this.element.closest('.toolBar.bottom').length) {
            /** @see https://api.jqueryui.com/autocomplete/#option-position */
            this.acPosition_ = {my: 'left bottom', at: 'left top', collision: 'none'};
        }
        if (this.options.result_icon_url && !/^(\w+:)?\/\//.test(this.options.result_icon_url)) {
            // Local, asset-relative
            var parts = [
                Mapbender.configuration.application.urls.asset.replace(/\/$/, ''),
                this.options.result_icon_url.replace(/^\//, '')
            ];
            this.iconUrl_ = parts.join('/');
        }
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';
        // @todo: this has never been customizable. Always used FOM Autcomplete default 2.
        var minLength = 2;

        searchInput.autocomplete({
            appendTo: searchInput.parent().get(0),
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
            position: this.acPosition_,
            select: function(event, ui) {
                // Adapt data format
                self._onAutocompleteSelected(event, {data: ui.item});
            },
            classes: {
                'ui-autocomplete': 'ui-autocomplete autocompleteList'
            }
        });
        // On manual submit (enter key, submit button), trigger autocomplete manually
        this.element.on('submit', function(evt) {
            evt.preventDefault();
            searchInput.autocomplete("search");
        });

        // On item selection in autocomplete, parse data and set map bbox
        searchInput.on('mbautocomplete.selected', $.proxy(this._onAutocompleteSelected, this));
    },
    _getMbMap: function() {
        // @todo: SimpleSearch should have a 'target' for this, like virtually every other element
        return (Mapbender.elementRegistry.listWidgets())['mapbenderMbMap'];
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
                    parts.push(this._extractAttribute(doc, attributePath));
                } else {
                    // @todo: filter separator texts between empty attribute lookups
                    parts.push(fixedText);
                }
            }
            return parts.join('');
        } else {
            return this._extractAttribute(doc, this.options.label_attribute);
        }
    },
    _onAutocompleteSelected: function(evt, evtData) {
        var format = new OpenLayers.Format[this.options.geom_format]();
        var feature = format.read(evtData.data[this.options.geom_attribute]);
        // Unpack GeoJSON parsing result list => single feature
        while (feature && Array.isArray(feature)) { feature = feature[0]; }
        var mbMap = this._getMbMap();

        var zoomToFeatureOptions = {
            maxScale: parseInt(this.options.result_maxscale) || null,
            minScale: parseInt(this.options.result_minscale) || null,
            buffer: parseInt(this.options.result_buffer) || null
        };
        mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        this._hideMobile();
        this._setFeatureMarker(feature);
    },
    _setFeatureMarker: function(feature) {
        var olMap = this._getMbMap().getModel().map.olMap;
        var self = this;

        var bounds = feature.geometry.getBounds();

        // Add marker
        if (this.iconUrl_) {
            if(!self.marker) {
                var addMarker = function() {
                    var offset = (self.options.result_icon_offset || '').split(new RegExp('[, ;]'));
                    var x = parseInt(offset[0]);

                    var size = {
                        'w': image.naturalWidth,
                        'h': image.naturalHeight
                    };

                    var y = parseInt(offset[1]);

                    offset = {
                        'x': !isNaN(x) ? x : 0,
                        'y': !isNaN(y) ? y : 0
                    };

                    var icon = new OpenLayers.Icon(image.src, size, offset);
                    self.marker = new OpenLayers.Marker(bounds.getCenterLonLat(), icon);
                    self.layer = new OpenLayers.Layer.Markers();
                    olMap.addLayer(self.layer);
                    self.layer.addMarker(self.marker);
                };

                var image = new Image();
                image.src = this.iconUrl_;
                image.onload = addMarker;
                image.onerror = addMarker;
            } else {
                var newPx = olMap.getLayerPxFromLonLat(bounds.getCenterLonLat());
                self.marker.moveTo(newPx);
            }
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

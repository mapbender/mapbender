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
    iconStyle: null,

    _create: function() {
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';

        // Set up autocomplete
        this.autocomplete = new Mapbender.Autocomplete(searchInput, {
            url: url,
            delay: this.options.delay,
            dataTitle: this.options.label_attribute,
            dataIdx: null,
            preProcessor: $.proxy(this._tokenize, this)
        });

        // On manual submit (enter key, submit button), trigger autocomplete manually
        this.element.on('submit', function(evt) {
            var searchTerm = searchInput.val();
            if(searchTerm.length >= self.autocomplete.options.minLength) {
                self.autocomplete.find(searchTerm);
            }
            evt.preventDefault();
        });

        // On item selection in autocomplete, parse data and set map bbox
        searchInput.on('mbautocomplete.selected', $.proxy(this._onAutocompleteSelected, this));
    },
    _getMbMap: function() {
        // @todo: SimpleSearch should have a 'target' for this, like virtually every other element
        return (Mapbender.elementRegistry.listWidgets())['mapbenderMbMap'];
    },
    _onAutocompleteSelected: function(evt, evtData) {
        var format = new OpenLayers.Format[this.options.geom_format]();
        var self = this;
        if(!evtData.data[self.options.geom_attribute]) {
            $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
            return;
        }

        var feature = format.read(evtData.data[self.options.geom_attribute]);
        var mbMap = this._getMbMap();
        var olMap = mbMap.getModel().map.olMap;

        // ??? center = bounds?
        var center = self.model.getBoundsFromBinaryUsingFormat(evtData.data[self.options.geom_attribute], self.options.geom_format);
        var bounds = $.extend(center);
        if(self.options.result.buffer > 0) {
            bounds.top += self.options.result.buffer;
            bounds.right += self.options.result.buffer;
            bounds.bottom -= self.options.result.buffer;
            bounds.left -= self.options.result.buffer;
        }

        var zoom = self.model.getZoomForExtent(bounds);

        // Add marker
        if(self.options.result.icon_url) {
            if(!self.layer) {
                var addMarker = function() {
                    var offset = (self.options.result.icon_offset || '').split(new RegExp('[, ;]'));
                    var x = parseInt(offset[0]);

                    var size = [
                        image.naturalWidth,
                        image.naturalHeight
                    ];

                    var y = parseInt(offset[1]);

                    offset = [
                        !isNaN(x) ? x : 0,
                        !isNaN(y) ? y : 0
                    ];

                    // 4
                    self.iconStyle = self.model.createIconStyle({src: image.src, size: size, offset: offset});
                    self.layer = self.model.setMarkerOnCoordinates(center, self.element.attr('id'), self.layer, self.iconStyle);
                    // 2
                    var icon = new OpenLayers.Icon(image.src, size, offset);
                    self.marker = new OpenLayers.Marker(bounds.getCenterLonLat(), icon);
                    self.layer = new OpenLayers.Layer.Markers();
                    olMap.addLayer(self.layer);
                    self.layer.addMarker(self.marker);
                };

                var image = new Image();
                image.src = self.options.result.icon_url;
                image.onload = addMarker;
                image.onerror = addMarker;
            } else {
                self.model.removeAllFeaturesFromLayer(self.element.attr('id'), self.layer);
                self.layer = self.model.setMarkerOnCoordinates(center, self.element.attr('id'), self.layer, self.iconStyle);
            }
        }
        var centerLonLat = bounds.getCenterLonLat();
        var x = centerLonLat.x, y = centerLonLat.y;

        var centerOptions = {
            zoom: zoom
        };
        if (self.options.result) {
            centerOptions.maxScale = parseInt(self.options.result.maxScale) || null;
            centerOptions.minScale = parseInt(self.options.result.minScale) || null;
        }

        // Add marker
        if(self.options.result.icon_url) {
            if(!self.marker) {
                var addMarker = function() {
                    var offset = (self.options.result.icon_offset || '').split(new RegExp('[, ;]'));
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
                image.src = self.options.result.icon_url;
                image.onload = addMarker;
                image.onerror = addMarker;
            } else {
                var newPx = olMap.getLayerPxFromLonLat(bounds.getCenterLonLat());
                self.marker.moveTo(newPx);
            }
        }
        self._hideMobile();

        // finally, zoom
        mbMap.getModel().centerXy(x, y, centerOptions);

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

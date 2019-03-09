(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        url: null,
        delay: 0
    },

    marker: null,
    layer: null,

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
        var format = new OpenLayers.Format[this.options.geom_format]();
        searchInput.on('mbautocomplete.selected', function(evt, evtData) {

            if(!evtData.data[self.options.geom_attribute]) {
                $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
                return;
            }

            var feature = format.read(evtData.data[self.options.geom_attribute]);
            var olMap = Mapbender.Model.map.olMap;
            var bounds = feature.geometry.getBounds();

            if(self.options.result.buffer > 0) {
                bounds.top += self.options.result.buffer;
                bounds.right += self.options.result.buffer;
                bounds.bottom -= self.options.result.buffer;
                bounds.left -= self.options.result.buffer;
            }

            var zoom = olMap.getZoomForExtent(bounds);

            // restrict zoom if needed
            if(self.options.result && (self.options.result.maxscale || self.options.result.minscale)){
                var res = olMap.getResolutionForZoom(zoom);
                var units = olMap.baseLayer.units;

                if(self.options.result.maxscale) {
                    var maxRes = OpenLayers.Util.getResolutionFromScale(
                        self.options.result.maxscale, olMap.baseLayer.units);
                    if(Math.round(res) < maxRes) {
                        zoom = olMap.getZoomForResolution(maxRes);
                    }
                }

                if(self.options.result.minscale) {
                    var minRes = OpenLayers.Util.getResolutionFromScale(
                        self.options.result.minscale, olMap.baseLayer.units);
                    if(Math.round(res) > minRes) {
                        zoom = olMap.getZoomForResolution(minRes);
                    }
                }
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

            // finally, zoom
            Mapbender.Model.center(bounds.getCenterLonLat, zoom);
        });
    },

    _tokenize: function(string) {
        if('' == this.options.token_regex_in || '' == this.options.token_regex_out) return string;

        if (this.options.token_regex != "") {
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

(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        url: null,
        delay: 0
    },

    marker: null,
    layer: null,
    iconStyle: null,

    /**
     * @var {Mapbender.Model}
     */
    model: null,

    _create: function() {
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';

        var mbMap = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
        this.model = mbMap.model;

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
        searchInput.on('mbautocomplete.selected', function(evt, evtData) {

            if(!evtData.data[self.options.geom_attribute]) {
                $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
                return;
            }

            var coordinates = self.model.getBoundsFromBinaryUsingFormat(evtData.data[self.options.geom_attribute], self.options.geom_format);
            var bounds = $.extend(coordinates);

            if(self.options.result.buffer > 0) {
                bounds.top += self.options.result.buffer;
                bounds.right += self.options.result.buffer;
                bounds.bottom -= self.options.result.buffer;
                bounds.left -= self.options.result.buffer;
            }

            var extentResolution = self.model.getResolutionForExtent(bounds);
            var zoom = self.model.getZoomForResolution(extentResolution);

            // restrict zoom if needed
            if(self.options.result && (self.options.result.maxscale || self.options.result.minscale)){

                if(self.options.result.maxscale) {
                    var maxRes = self.model.scaleToResolution(self.options.result.maxscale);

                    if(Math.round(extentResolution) < maxRes) {
                        zoom = self.model.getZoomForResolution(maxRes);
                    }
                }

                if(self.options.result.minscale) {
                    var minRes = self.model.scaleToResolution(self.options.result.minscale);

                    if(Math.round(extentResolution) > minRes) {
                        zoom = self.model.getZoomForResolution(minRes);
                    }
                }
            }

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

                        self.iconStyle = self.model.createIconStyle({src: image.src, size: size, offset: offset});
                        self.layer = self.model.setMarkerOnCoordinates(coordinates, self.element.attr('id'), self.layer, self.iconStyle);
                    };

                    var image = new Image();
                    image.src = self.options.result.icon_url;
                    image.onload = addMarker;
                    image.onerror = addMarker;
                } else {
                    self.model.removeAllFeaturesFromLayer(self.element.attr('id'), self.layer);
                    self.layer = self.model.setMarkerOnCoordinates(coordinates, self.element.attr('id'), self.layer, self.iconStyle);
                }
            }

            // finally, zoom
            self.model.zoomToExtent(bounds);
            self.model.setZoom(zoom);
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

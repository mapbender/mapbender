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

    _create: function() {
        this.iconUrl_ = this.options.result_icon_url || null;
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


        // Work around FOM Autocomplete widget broken constructor, where all instance end up sharing the
        // same options object
        // @todo: drop the FOM Autocomplete widget usage entirely (SimpleSearch is the only user)
        var acOptions = Object.assign({}, Mapbender.Autocomplete.prototype.options, {
                url: url,
                delay: this.options.delay,
                dataTitle: this.options.label_attribute,
                dataIdx: null,
                preProcessor: $.proxy(this._tokenize, this)
        });
        this.autocomplete = new Mapbender.Autocomplete(searchInput, {
            url: acOptions.url,
            delay: acOptions.delay
        });
        this.autocomplete.options = acOptions;

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
        if(!evtData.data[this.options.geom_attribute]) {
            $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
            return;
        }

        var feature = format.read(evtData.data[this.options.geom_attribute]);
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

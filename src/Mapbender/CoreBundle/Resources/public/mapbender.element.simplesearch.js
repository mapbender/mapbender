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
        geom_srs: null,
        result: {
            buffer: null,
            minscale: null,
            maxscale: null,
            icon_url: null,
            icon_offset: null
        },
        delay: 0
    },

    marker: null,
    layer: null,

    _create: function() {
        var self = outerSelf = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';

        // Set up autocomplete
        self.autocomplete = new Mapbender.Autocomplete(searchInput, {
            url: url,
            delay: self.options.delay,
            dataIdx: null,
            preProcessor: $.proxy(self._tokenize, self),
            open: function(data){
                this.selected = null;
                if(data.length > 0){
                    var self = this;
                    self.data = data;
                    var res = "<ul>";
                    $.each(data, function(idx, item){
                        var itemIndex = self.options.dataIdx ? item[outerSelf.options.dataIdx] : idx;
                        res += '<li data-idx="' + itemIndex + '">' + outerSelf._labelize(outerSelf.options.label_attribute,item) + '</li>';
                    });
                    res += "</ul>";
                    this.autocompleteList.html(res).show();
                    this.autocompleteList.find('li').on('click', $.proxy(self.select, self));
                }
            },
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
        var self = this,
            format = new OpenLayers.Format[self.options.geom_format](),
            srs = self.options.geom_srs,
            requestProj = null,
            feature = null,
            zoomToFeatureOptions = null,
            mbMap = self._getMbMap();

        var olMap = mbMap.map.olMap;
        var mapProj = olMap.getProjectionObject();

        if(!evtData.data[this.options.geom_attribute]) {
            $.notify( Mapbender.trans("mb.core.simplesearch.error.geometry.missing"));
            return;
        }

        if(! srs) {
            console.warn("srs from search value hav not a srs reference. Now set default EPSG:4326", "EPSG:"+srs);
            requestProj = new OpenLayers.Projection('EPSG:4326');
        }else{
            requestProj = new OpenLayers.Projection('EPSG:' + srs);
        }

        if (self.options.geom_format === 'GeoJSON'){
            feature = format.read(evtData.data,'Feature');
        }else{
            feature = format.read(evtData.data[this.options.geom_attribute]);
        }

        // transform request features
        if (requestProj.projCode !== mapProj.projCode) {
            var transCoord = self._transformCoordinates(
                [feature.geometry.x, feature.geometry.y],
                requestProj,
                mapProj);

            feature.geometry.x = transCoord[0];
            feature.geometry.y = transCoord[1];
        }

        zoomToFeatureOptions = self.options.result && {
            maxScale: parseInt(self.options.result.maxscale) || null,
            minScale: parseInt(self.options.result.minscale) || null,
            buffer: parseInt(self.options.result.buffer) || null
        };
        mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        self._hideMobile();
        self._setFeatureMarker(feature);
    },
    _setFeatureMarker: function(feature) {
        var olMap = this._getMbMap().getModel().map.olMap;
        var self = this;

        var bounds = feature.geometry.getBounds();

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
    },

    /**
     * Transforms coordinate pair
     * @param coordinatePair Array [lon, lat] (from point array)
     * @param fromProj String olMap.getProjectionObject() "EPSG:xxxx"
     * @param toProj String olMap.getProjectionObject() "EPSG:xxxx"
     * @returns Array new projected point
     * @private
     */
    _transformCoordinates: function(coordinatePair, fromProj, toProj) {
        var olLonLat = new OpenLayers.LonLat(coordinatePair[0], coordinatePair[1]);
        var olPoint =  olLonLat.transform(fromProj, toProj);
        return [olPoint.lon, olPoint.lat];
    },


});

})(jQuery);

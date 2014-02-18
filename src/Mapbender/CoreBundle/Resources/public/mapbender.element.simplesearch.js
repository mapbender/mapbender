(function($) {

$.widget('mapbender.mbSimpleSearch', {
    options: {
        url: null,
        delay: 0
    },

    _create: function() {
        var self = this;
        var searchInput = $('.searchterm', this.element);
        var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/search';

        // Set up autocomplete
        this.autocomplete = new Mapbender.Autocomplete(searchInput, {
            url: url,
            delay: this.options.delay,
            dataTitle: this.options.label_attribute,
            dataIdx: null
        });

        // On manual submit (enter key, submit button), trigger autocomplete manually
        this.element.on('submit', function(evt) {
            var searchTerm = searchInput.val();
            if(searchTerm.length >= self.autocomplete.options.minLength) {
                self.autocomplete.find(searchTerm);
            }
            event.preventDefault();
        });

        // On item selection in autocomplete, parse data and set map bbox
        var format = new OpenLayers.Format[this.options.geom_format]();
        searchInput.on('mbautocomplete.selected', function(evt, evtData) {
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
                var scale = OpenLayers.Util.getScaleFromResolution(res, units);

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

            // finally, zoom
            Mapbender.Model.center({
                position: [bounds.getCenterLonLat().lon, bounds.getCenterLonLat().lat],
                zoom: zoom
            });
        });
    }
});

})(jQuery);

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
            Mapbender.Model.center({
                box: feature.geometry.getBounds().toArray()
            });
        });
    }
});

})(jQuery);

var Mapbender = Mapbender || {};
Mapbender.setup = function() {
    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data) {
        var ns = data.ns || 'mapbender';
        if(typeof($[ns][data.init]) == 'function') {
            $('#' + id)[data.init](data.options);
        }
    });
    $(document).trigger('mapbender.setupfinished');
};

$(Mapbender.setup);

var Mapbender = Mapbender || {};
Mapbender.setup = function() {
    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data) {
        // Split for namespace and widget name
        var widget = data.init.split('.');
        // This way we call by namespace and widget name
        // The namespace is kinda useless, as $.widget creates a function with
        // the widget name directly in the jQuery object, too. Still, let's be
        // futureproof.
        $[widget[0]][widget[1]](data.configuration, '#' + id);
    });

    // Tell the world that all widgets have been set up. Some elements will
    // need this to make calls to other element's widgets
    $(document).trigger('mapbender.setupfinished');
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);

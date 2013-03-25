var Mapbender = Mapbender || {};

Mapbender.ElementRegistry = function() {
    this.readyElements = {};
    this.readyCallbacks = {};

    this.onElementReady = function(targetId, callback) {
        if(true === callback) {
            // Register as ready
            this.readyElements[targetId] = true;

            // Execute all callbacks registered so far
            if('undefined' !== this.readyCallbacks[targetId]) {
                for(var idx in this.readyCallbacks[targetId]) {
                    this.readyCallbacks[targetId][idx]();
                }

                // Finally, remove readyCallback list, so they may be garbage
                // collected if no one else is keeping them
                delete this.readyCallbacks[targetId];
            }
        } else if('function' === typeof callback) {
            if(true === this.readyElements[targetId]) {
                // If target is ready already, execute callback right away
                callback();
            } else {
                // Register callback for targetId for later execution
                this.readyCallbacks[targetId] = this.readyCallbacks[targetId] || [];
                this.readyCallbacks[targetId].push(callback);
            }
        } else {
            throw 'ElementRegistry.onElementReady callback must be function or undefined!';
        }
    }
};
Mapbender.elementRegistry = new Mapbender.ElementRegistry();

Mapbender.setup = function() {
    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data) {
        // Split for namespace and widget name
        var widget = data.init.split('.');

        // Register for ready event to operate ElementRegistry
        var readyEvent = widget[1].toLowerCase() + 'ready';
        $('#' + id).one(readyEvent, function(event) {
            for(var i in Mapbender.configuration.elements) {
                var conf = Mapbender.configuration.elements[i],
                    widget = data.init.split('.'),
                    readyEvent = widget[1].toLowerCase() + 'ready';
                if(readyEvent === event.type) {
                    Mapbender.elementRegistry.onElementReady(i, true);
                }
            }
        });

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

Mapbender.error = function(message){
    alert(message);
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);

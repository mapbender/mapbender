var Mapbender = Mapbender || {};

Mapbender.ElementRegistry = function() {
    this.readyElements = {};
    this.readyCallbacks = {};

    this.onElementReady = function(targetId, callback) {
        if(true === callback) {
            // Register as ready
            this.readyElements[targetId] = true;

            // Execute all callbacks registered so far
            if('undefined' !== typeof this.readyCallbacks[targetId]) {
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
    };

    this.listWidgets = function () {
        var list = {};
        var elements = $(".mb-element");
        $.each(elements, function (idx, el) {
            var data = $(el).data();
            if(!data ){
                return;
            }
            for (var id in data) {
                list[id] = data[id];
            }
        });
        return list;
    };

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
                widget = conf.init.split('.'),
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

Mapbender.info = function(message){
    alert(message);
};
Mapbender.confirm = function(message){
    var res = confirm(message);
    return res;
};

Mapbender.checkTarget = function(widgetName, target, targetname){
    if(target === null || typeof(target) === 'undefined'
        || new String(target).replace(/^\s+|\s+$/g, '') === ""
        || $('#' + target).length === 0){
        Mapbender.error(widgetName + ': a target element ' + (targetname ? '"' + targetname + '"' : '') + ' is not defined.');
        return false;
    } else {
        return true;
    }
};

Mapbender.urlParam = function(key) {
    var results = new RegExp('[\\?&]' + key + '=([^&#]*)').exec(window.location.href) || [];
    return results[1] || undefined;
};

Mapbender.UUID = function(){
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x7|0x8)).toString(16);
    });
    return uuid;
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);

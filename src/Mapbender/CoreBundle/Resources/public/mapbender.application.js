$.ajaxPrefilter(function(options) {
    if(options.crossDomain) {
        options.url = Mapbender.configuration.application.urls.proxy + '?url=' + encodeURIComponent(encodeURIComponent(options.url));
        options.crossDomain = false;
    }
});

var Mapbender = Mapbender || {};

Mapbender.ElementRegistry = function(){
    this.readyElements = {};
    this.readyCallbacks = {};

    this.onElementReady = function(targetId, callback){
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

    this.listWidgets = function(){
        var list = {};
        var elements = $(".mb-element");
        $.each(elements, function(idx, el){
            var data = $(el).data();
            if(!data) {
                return;
            }
            for(var id in data) {
                list[id] = data[id];
            }
        });
        return list;
    };

};
Mapbender.elementRegistry = new Mapbender.ElementRegistry();

/**
 * Initialize mapbender element
 *
 * @param id
 * @param data elemenent configurations data object
 */
Mapbender.initElement = function(id, data) {
    // Split for namespace and widget name
    var widget = data.init.split('.');

    // Register for ready event to operate ElementRegistry
    var readyEvent = widget[1].toLowerCase() + 'ready';
    $('#' + id).one(readyEvent, function(event) {
        for (var i in Mapbender.configuration.elements) {
            var conf = Mapbender.configuration.elements[i], widget = conf.init.split('.'), readyEvent = widget[1].toLowerCase() + 'ready';
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
};

Mapbender.source = Mapbender.source || {};
Mapbender.setup = function(){

    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data){
        var defaultStackTraceLimit = Error.stackTraceLimit;
        Error.stackTraceLimit = undefined;
        try {       
            Mapbender.initElement(id,data);
        } catch(e) {
            $.notify('Your element with id ' + id + ' (widget ' + data.init + ') failed to initialize properly.', 'error');
            console.error(e);            
        }
        Error.stackTraceLimit = defaultStackTraceLimit;
    });

    // Tell the world that all widgets have been set up. Some elements will
    // need this to make calls to other element's widgets
    $(document).trigger('mapbender.setupfinished');
};

Mapbender.error = function(errorObject,delayTimeout){
    var errorMessage = errorObject;
    if(typeof errorObject != "string"){
        errorMessage = JSON.stringify(errorObject);
    }
    $.notify(errorMessage,{autoHideDelay: delayTimeout?delayTimeout:5000}, 'error');
    console.error("Mapbender Error: ",errorObject);
};

Mapbender.info = function(infoObject,delayTimeout){
    var message = infoObject;
    if(typeof infoObject != "string"){
        message = JSON.stringify(infoObject);
    }
    $.notify(message,{autoHideDelay: delayTimeout?delayTimeout:5000,className: 'info'});
    console.log("Mapbender Info: ",infoObject);
};
Mapbender.confirm = function(message){
    var res = confirm(message);
    return res;
};

Mapbender.checkTarget = function(widgetName, target, targetname){
    if(target === null || typeof (target) === 'undefined'
            || new String(target).replace(/^\s+|\s+$/g, '') === ""
            || $('#' + target).length === 0) {
        Mapbender.error(widgetName + ': a target element ' + (targetname ? '"' + targetname + '"'
                : '') + ' is not defined.');
        return false;
    } else {
        return true;
    }
};

Mapbender.Util = Mapbender.Util || {};

Mapbender.Util.UUID = function(){
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c){
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == 'x' ? r : (r & 0x7 | 0x8)).toString(16);
    });
    return uuid;
};
/* deprecated */
Mapbender.urlParam = function(key){
    window.console && console.warn(
            'The function "Mapbender.urlParam" is deprecated, use instead it the "new Mapbender.Util.Url().getParameter(key)"');
    return new Mapbender.Util.Url(window.location.href).getParameter(key);
};

/* deprecated */
Mapbender.UUID = function(){
    window.console && console.warn(
            'The function "Mapbender.UUID" is deprecated, use instead it the "Mapbender.Util.UUID"');
    return Mapbender.Util.UUID();
}

/**
 * Creates an url object from a giving url string
 * @param {String} urlString
 */
Mapbender.Util.Url = function(urlString){
    if(!urlString.replace(/^\s+|\s+$/g, ''))// trim
        return;
    var self = this;
    var tmp = document.createElement("a");
    tmp.href = urlString;
    this.protocol = tmp.protocol;
    this.username = tmp.username;
    this.password = tmp.password;
    this.host = tmp.host;
    this.port = tmp.port;
    this.pathname = tmp.pathname;
    this.parameters = OpenLayers.Util.getParameters(urlString);
    this.hash = tmp.hash;
    /**
     * Checks if a url object is valid.
     * @returns {Boolean} true if url valid
     */
    this.isValid = function(){
        return  !(!self.host || !self.protocol);// TODO ?
    };
    /**
     * Gets an url object as string.
     * @returns {String} url as string
     */
    this.asString = function(withoutUser){
        var str = self.protocol + (self.protocol === 'http:' || self.protocol === 'https:' || self.protocol === 'ftp:'
                ? '//' : (self.protocol === 'file:' ? '///' : ''));// TODO for other protocols
        str += (!withoutUser && self.username ? self.username + ':' + (self.password ? self.password : '') + '@' : '');
        str += self.host + (self.port ? ':' + self.port : '') + self.pathname;
        var params = '';
        if(typeof (self.parameters) === 'object') {
            for(key in self.parameters) {
                params += '&' + key + '=' + self.parameters[key];
            }
        }
        return str + (params.length ? '?' + params.substr(1) : '') + (self.hash ? self.hash : '');
    };
    /**
     * Gets a GET parameter value from a giving parameter name.
     * @param {String} name parameter name
     * @param {Boolean} ignoreCase
     * @returns parameter value or null
     */
    this.getParameter = function(name, ignoreCase){
        for(key in self.parameters) {
            if(key === name || (ignoreCase && key.toLowerCase() === name.toLowerCase())) {
                return self.parameters[key];
            }
        }
        return null;
    };
};

Mapbender.Util.isInScale = function(scale, min_scale, max_scale){
    return (min_scale ? min_scale <= scale : true) && (max_scale ? max_scale >= scale : true);
};

Mapbender.Util.addProxy = function(url) {
    return OpenLayers.ProxyHost + encodeURIComponent(url);
};

Mapbender.Util.removeProxy = function(url) {
    if (url.indexOf(OpenLayers.ProxyHost) === 0) {
        return decodeURIComponent(url.substring(OpenLayers.ProxyHost.length));
    }
    return url;
};

Mapbender.Util.removeSignature = function(url) {
    var pos = -1;
    pos = url.indexOf("_signature");
    if (pos !== -1) {
        var url_new = url.substring(0, pos);
        if (url_new.lastIndexOf('&') === url_new.length - 1) {
            url_new = url_new.substring(0, url_new.lastIndexOf('&'));
        }
        if (url_new.lastIndexOf('?') === url_new.length - 1) {
            url_new = url_new.substring(0, url_new.lastIndexOf('?'));
        }
        return url_new;
    }
    return url;
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);

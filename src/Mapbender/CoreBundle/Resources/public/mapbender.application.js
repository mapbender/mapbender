"use strict";

window.Mapbender = Mapbender || {};

Mapbender.ElementRegistry = (function($){
    'use strict';

    /**
     * @typedef {Object} ElementRegistry~promisesBundle
     * @property {Promise} created
     * @property {Promise} readyEvent
     * @property {Promise} ready
     * @property {int} offset
     * @property {*} instance
     */

    function ElementRegistry() {
        this.classIndex = {};
        this.idIndex = {};
        this.promisesBundles = [];
        // Prepare a prerejected Promise. This will be returned by waitCreated / waitReady
        // if no tracked element matches
        this.rejected_ = $.Deferred();
        this.rejected_.reject();
    }

    /**
     * Legacy API for registering callbacks on widget ready event.
     * @param {string|int} targetId
     * @param {function} callback
     * @returns {Promise<any | never>}
     */
    ElementRegistry.prototype.onElementReady = function(targetId, callback) {
        return this.waitReady(targetId).then(callback);
    };
    /**
     * Return an object mapping widget name (string; e.g. 'mapbenderMbMap') to widget instance.
     * NOTE: cannot differentiate between multiple instances of the same widget type
     * @returns {*}
     */
    ElementRegistry.prototype.listWidgets = function() {
        var data = {};
        $('.mb-element').each(function(i, el) {
            _.assign(data, $(el).data());
        });
        return data;
    };
    /**
     * Return a promise that will resolve once the widget with given ident has been
     * created (=immediately after return from widget constructor call).
     * This promise will reject if the widget constructor throws an error.
     *
     * @param {string} ident id (leading hash optional) or class name (leading '.' required)
     * @returns {Promise}
     */
    ElementRegistry.prototype.waitCreated = function(ident) {
        return this.wait_(ident, 'created');
    };
    /**
     * Return a promise that will resolve once the widget with given ident has fired
     * its ready event.
     * This promise will reject if the widget constructor throws an error.
     *
     * @param {string} ident id (leading hash optional) or class name (leading '.' required)
     * @returns {Promise}
     */
    ElementRegistry.prototype.waitReady = function(ident) {
        return this.wait_(ident, 'ready');
    };
    /**
     * Returns the Promise at bundleKey in the promises bundle matched by given ident.
     * If no tracked element matches ident, returns an already rejected Promise.
     *
     * @param {string} ident id (leading hash optional) or class name (leading '.' required)
     * @param {string} bundleKey 'created' or 'ready'
     * @returns {Promise}
     * @private
     */
    ElementRegistry.prototype.wait_ = function(ident, bundleKey) {
        var matches = this.match_(ident);
        if (matches.length > 1) {
            console.warn("Matched multiple elements, waiting on first", ident, matches);
        }
        if (matches.length) {
            return matches[0][bundleKey].promise();
        } else {
            console.error("No element match for ident", ident);
            return this.rejected_.promise();
        }
    };
    /**
     * Adds given dom node to tracking, assuming it will be the target of an element widget
     * constructor.
     *
     * @param {HTMLElement|jQuery} node
     * @param {string} readyEventName
     */
    ElementRegistry.prototype.trackElementNode = function(node, readyEventName) {
        var nodeId = $(node).attr('id');
        if (!nodeId) {
            console.error('Cannot track element node without id', node);
            throw new Error('Cannot track element node without id');
        }
        var bundle = this.trackId_(nodeId);
        this.addTrackingByClass_(bundle, node);
        // register one-time listener for ready event, which will trigger promise
        // resolution
        $(node).one(readyEventName, this.markReady.bind(this, nodeId));
    };
    /**
     * @param {string|int} id
     * @returns {ElementRegistry~promisesBundle}
     * @private
     */
    ElementRegistry.prototype.trackId_ = function(id) {
        // Force id to string, strip leading hash character if any
        var id_ = ('' + id).replace(/^#*/, '');
        // Create a new promises bundle: one promise resolving after widget
        // creation; another promise resolving when widget fires its ready event.
        var bundle = {
            offset: this.promisesBundles.length,
            created: $.Deferred(),
            readyEvent: $.Deferred(),
            ready: $.Deferred()
        };
        // Ready promise will resolve after both create and readyEvent. We do this
        // because the widget instance is delivered at the time of create resolution,
        // and we want to resolve the ready promise WITH that instance.
        $.when(bundle.created, bundle.readyEvent).then(function() {
            bundle.ready.resolveWith(null, [bundle.instance]);
        }, function() {
            // Also reject ready promise if created promise rejects
            bundle.ready.reject();
        });
        // record promises bundle in id index for id based lookups
        if (this.idIndex[id_]) {
            console.error('Cannot track same dom id twice', id_);
            throw new Error('Cannot track same dom id twice');
        }
        this.idIndex[id_] = bundle.offset;
        this.promisesBundles.push(bundle);
        return bundle;
    };
    /**
     * @param {ElementRegistry~promisesBundle} bundle
     * @param {HTMLElement|jQuery} node
     */
    ElementRegistry.prototype.addTrackingByClass_ = function(bundle, node) {
        var classNames = ($(node).attr('class') || '').split(/\s+/);
        var mbClasses = _.uniq(classNames.filter(function(c) {
            return !!c.match(/^mb-element-/);
        }));
        // record same promises bundle in class index for class-name based lookups
        if (mbClasses.length) {
            for (var i = 0; i < mbClasses.length; ++i) {
                var nextClass = mbClasses[i];
                if (!this.classIndex[nextClass]) {
                    this.classIndex[nextClass] = [];
                }
                this.classIndex[nextClass].push(bundle.offset);
            }
        } else {
            // Warn; suppress warning for super-common .mb-button class
            if (-1 === classNames.indexOf('mb-button')) {
                console.warn("Tracking dom node in registry that doesn't have any mb-element-* class name", node);
            }
        }
    };
    /**
     * Look for tracked element(s) matching given ident (id or .class-name) and
     * return the corresponding promise bundles (as Array).
     *
     * @param {string|int} ident
     * @returns {Array<ElementRegistry~promisesBundle>}
     * @private
     */
    ElementRegistry.prototype.match_ = function(ident) {
        if (ident.length > 1 && ident[0] === '.') {
            // Leading dot: treat ident as a class name
            var candidateOffsets = this.classIndex[ident.slice(1)];
            if (candidateOffsets && candidateOffsets.length) {
                var bundles = this.promisesBundles;
                return candidateOffsets.map(function(offset) {
                    return bundles[offset];
                });
            }
        } else {
            // Treat ident as a DOM id
            // Force string, no leading hash
            var id = ('' + ident).replace(/^#*/, '');
            if (typeof this.idIndex[id] !== 'undefined') {
                return [this.promisesBundles[this.idIndex[id]]];
            }
        }
        // No matches
        return [];
    };
    /**
     * Look for a single tracked element matching given ident (id or .class-name).
     * Unlike basic match_, this method throws an Error unless we match exactly one
     * element.
     *
     * @param {string|int} ident
     * @returns ElementRegistry~promisesBundle
     * @private
     */
    ElementRegistry.prototype.matchSingle_ = function(ident) {
        var matches = this.match_(ident);
        if (!matches.length) {
            throw new Error("No matching element for " + ident);
        }
        if (matches.length > 1) {
            throw new Error("Unexpected multiple matches for " + ident);
        }
        return matches[0];
    };
    /**
     * @param {string|int} ident can be id or .class-name, but must be unique in the DOM.
     * @param instance
     */
    ElementRegistry.prototype.markCreated = function(ident, instance) {
        var bundle = this.matchSingle_(ident);
        bundle.instance = instance;
        bundle.created.resolveWith(null, [instance]);
    };
    /**
     * @param {string|int} ident can be id or .class-name, but must be unique in the DOM.
     */
    ElementRegistry.prototype.markReady = function(ident) {
        var bundle = this.matchSingle_(ident);
        bundle.readyEvent.resolve();
    };
    /**
     * Notify all dependents on widget with given ident that creation has failed and ready event
     * will never fire.
     *
     * @param {string|int} ident can be id or .class-name, but must be unique in the DOM.
     */
    ElementRegistry.prototype.markFailed = function(ident) {
        var bundle = this.matchSingle_(ident);
        bundle.ready.reject();
        bundle.created.reject();
    };
    return ElementRegistry;
}(jQuery));

Mapbender.elementRegistry = new Mapbender.ElementRegistry();

/**
 * Initialize mapbender element
 *
 * @param id
 * @param data elemenent configurations data object
 */
Mapbender.initElement = function(id, data) {
    var widgetId = '#' + id;
    var widgetElement = $(widgetId);
    var hasElement = widgetElement.size() > 0;

    if(!hasElement) {
        console.log("Element '" + widgetId + "' isn't available.");
        return;
    }

    var widgetInfo = data.init.split('.');
    var widgetName = widgetInfo[1];
    var nameSpace = widgetInfo[0];
    var readyEvent = widgetName.toLowerCase() + 'ready';

    var mapbenderWidgets = $[nameSpace];
    if (!mapbenderWidgets) {
        if (!mapbenderWidget) {
            throw new Error("No such widget namespace" + nameSpace);
        }
    }

    var mapbenderWidget = mapbenderWidgets[widgetName];
    if (!mapbenderWidget) {
        Mapbender.elementRegistry.markFailed(id);
        throw new Error("No such widget " + data.init);
    }
    return mapbenderWidget(data.configuration, widgetId);
};

Mapbender.source = Mapbender.source || {};
Mapbender.setup = function(){
    $.each(Mapbender.configuration.elements, function(id, data) {
        // Mark all elements for elementRegistry tracking before calling the constructors.
        // This is necessary to correctly record ready events of elements that become
        // ready immediately in their widget constructor / _create method, most notably
        // mapbenderMbMap.
        // @todo: fold copy&paste between this method and initElement
        var widgetId = '#' + id;
        var $node = $(widgetId);
        var widgetInfo = data.init.split('.');
        var widgetName = widgetInfo[1];
        var readyEventName = widgetName.toLowerCase() + 'ready';
        if ($node.length) {
            Mapbender.elementRegistry.trackElementNode($node, readyEventName);
        } else {
            console.error("No matching dom node for configured element", id, data);
        }
    });

    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data){
        var defaultStackTraceLimit = Error.stackTraceLimit;
        // NOTE: do not set undefined; undefined captures NO STACK TRACE AT ALL in some browsers
        Error.stackTraceLimit = 100;
        try {
            var instance = Mapbender.initElement(id, data);
            Mapbender.elementRegistry.markCreated(id, instance);
        } catch(e) {
            Mapbender.elementRegistry.markFailed(id);
            // NOTE: console.error produces a NEW stack trace that ends right here, and as such
            //       won't point to the origin of the Error at all.
            console.error("Element " + id + " failed to initialize:", e.message, data);
            if (Mapbender.configuration.application.debug) {
                // Log original stack trace (clickable in Chrome, unfortunately not in Firefox) separately
                console.log(e.stack);
            }
            $.notify('Your element with id ' + id + ' (widget ' + data.init + ') failed to initialize properly.', 'error');
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
    this.hostname = tmp.hostname;
    this.port = tmp.port;
    this.pathname = tmp.pathname.charAt(0) === '/' ? tmp.pathname : '/' + tmp.pathname;
    this.parameters = OpenLayers.Util.getParameters(urlString);
    this.hash = tmp.hash;
    /**
     * Checks if a url object is valid.
     * @returns {Boolean} true if url valid
     */
    this.isValid = function(){
        return  !(!self.hostname || !self.protocol);// TODO ?
    };
    /**
     * Gets an url object as string.
     * @returns {String} url as string
     */
    this.asString = function(withoutUser){
        var str = self.protocol + (self.protocol === 'http:' || self.protocol === 'https:' || self.protocol === 'ftp:'
                ? '//' : (self.protocol === 'file:' ? '///' : ''));// TODO for other protocols
        str += (!withoutUser && self.username ? self.username + ':' + (self.password ? self.password : '') + '@' : '');
        str += self.hostname + (self.port ? ':' + self.port : '') + self.pathname;
        var params = '';
        if(typeof (self.parameters) === 'object') {
            for(var key in self.parameters) {
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
        for(var key in self.parameters) {
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

Mapbender.Util.isSameSchemeAndHost = function(urlA, urlB) {
    var a = document.createElement('a');
    var b = document.createElement('a');
    a.href = urlA;
    b.href = urlB;
    return a.host === b.host && a.protocol && b.protocol;
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

/* load application configuration see application.config.loader.js.twig */

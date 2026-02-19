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
            Object.extend(data, $(el).data());
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
     * @param {string} [readyEventName]
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
        var self = this;
        if (readyEventName) {
            // Listen on parent. For uncacheable elements, original node may be replaced with
            // new content, losing directly bound listeners.
            $(node).parent().one(readyEventName, (function(nodeId) {
                return function(evt) {
                    if (evt.target && evt.target.id === nodeId) {
                        self.markReady(nodeId);
                        return false;
                    } else {
                        return true;
                    }
                };
            }(nodeId)));
        }
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
        var mbClasses = Mapbender.Util.array_unique(classNames.filter(function(c) {
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

$.extend(Mapbender, (function($) {
    'use strict';
    function _initLayersets(configs, layersetTitleMap) {
        var layersets = [];
        for (var i = 0; i < configs.length; ++i) {
            var lsConfig = configs[i];
            var layerset = new Mapbender.Layerset(lsConfig.title, lsConfig.id, lsConfig.selected);
            layerset.siblings = layersets;

            var instanceConfigs = lsConfig.instances;
            for (var j = 0; j < instanceConfigs.length; ++j) {
                var instanceDef = instanceConfigs[j];
                var instance = Mapbender.Source.factory(instanceDef);
                // replace original list entry
                instanceConfigs[j] = instance;
                instance.layerset = layerset;
                layerset.children.push(instance);
            }
            layersets.push(layerset);
        }
        return layersets;
    }

    function _getElementInitInfo(initName) {
        var initParts = initName.split('.');
        var methodNamespace, innerName;

        if (initParts.length > 0) {
            methodNamespace = $[initParts[0]] || null;
            innerName = initParts[1];
        } else {
            methodNamespace = $;
            innerName = initParts[0];
        }
        return {
            initMethod: (methodNamespace || {})[innerName] || null,
            eventPrefix: innerName.toLowerCase()
        }
    }

    /**
     * @param {String} id
     * @param {Object} data
     * @return {*}
     */
    function initElement(id, data) {
        var instance;
        var $node = $(document.getElementById(id));
        if (data.errors) {
            throw new Error('Configuration error: ' + data.errors.join('\n'));
        }
        if (!$node.length) {
            throw new Error('Element #' + id + ' not found in DOM');
        }
        if (data.init) {
            var initInfo = _getElementInitInfo(data.init);
            if (!initInfo.initMethod) {
                Mapbender.elementRegistry.markFailed(id);
                throw new Error("No such widget " + data.init);
            }
            instance = (initInfo.initMethod)(data.configuration, $node);
            Mapbender.elementRegistry.markCreated(id, instance);
        } else {
            // no widget constructor, do a little thing that at least has the element property in the
            // same place as a jquery ui widget
            instance = {
                element: $node
            };
            Mapbender.elementRegistry.markCreated(id, instance);
            Mapbender.elementRegistry.markReady(id);
        }
    }
    function _trackElements(config) {
        // @todo: fold copy&paste between this method and initElement
        var elementIds = Object.keys(config);
        for (var i = 0; i < elementIds.length; ++i) {
            var id = elementIds[i];
            var data = config[id];
            var $node = $('#' + id);
            // Note: config may reference Elements that have been suppressed in markup (grants)
            if ($node.length) {
                var initInfo = data.init && _getElementInitInfo(data.init);
                var readyEventName = initInfo && initInfo.eventPrefix && [initInfo.eventPrefix, 'ready'].join('') || null;
                Mapbender.elementRegistry.trackElementNode($node, readyEventName);
            }
        }
    }
    function _initElements(config) {
        var elementIds = Object.keys(config);
        var uncacheable = {};
        var node;
        for (var i = 0; i < elementIds.length; ++i) {
            var elementId = elementIds[i];
            // Note: config may reference Elements that have been suppressed in markup (grants)
            node = document.getElementById(elementId);
            if (!node) {
                continue;
            }
            var elementData = config[elementId];
            if ($(node).hasClass('-js-reload-uncacheable')) {
                uncacheable[elementId] = elementData;
            } else {
                _initElement(elementId, elementData);
            }
        }
        var uncacheableIds = Object.keys(uncacheable);
        if (uncacheableIds.length) {
            var reloadUrl = ['.', Mapbender.configuration.application.slug, 'elements'].join('/');
            const loadUrls = (reloadUrl) => {
                $.getJSON(reloadUrl, {
                    ids: uncacheableIds.join(',')
                }).then(function (response) {
                    var selectors = Object.keys(response);
                    for (var s = 0; s < selectors.length; ++s) {
                        var selector = selectors[s];
                        $(selectors[s]).replaceWith(response[selector]);
                    }
                    for (i = 0; i < uncacheableIds.length; ++i) {
                        var id = uncacheableIds[i];
                        _initElement(id, uncacheable[id]);
                    }
                }).fail((e) => Mapbender.handleAjaxError(e, () => loadUrls(reloadUrl)));
            }
            loadUrls(reloadUrl);
        }
    }
    function _initElement(id, elementData) {
        try {
            initElement(id, elementData);
        } catch(e) {
            Mapbender.elementRegistry.markFailed(id);
            // NOTE: console.error produces a NEW stack trace that ends right here, and as such
            //       won't point to the origin of the Error at all.
            console.error("Element " + id + " failed to initialize:", e.message, elementData);
            if (window.Mapbender.configuration.application.debug) {
                // Log original stack trace (clickable in Chrome, unfortunately not in Firefox) separately
                console.log(e.stack);
            }
            $.notify('Your element with id ' + id + ' (widget ' + elementData.init + ') failed to initialize properly: ' + e.message, 'error');
        }
    }

    function setup() {
        // Disable ~"functional" a href="#" links from opening new tab / breaking fragment-based navigation history
        // This is equivalent to what the (missing) base Bootstrap script does.
        $(document).on('click', 'a[href="#"]', function(e) {
            e.preventDefault();
            // Allow other event handlers to continue processing
            return true;
        });
        window.Mapbender.mapEngine = Mapbender.MapEngine.factory(Mapbender.configuration.application.mapEngineCode);
        var layersets = _initLayersets(Mapbender.configuration.layersets || {}, Mapbender.configuration.layersetmap);
        window.Mapbender.layersets = layersets;

        // Mark all elements for elementRegistry tracking before calling the constructors.
        // This is necessary to correctly record ready events of elements that become
        // ready immediately in their widget constructor / _create method, most notably
        // mapbenderMbMap.
        _trackElements(Mapbender.configuration.elements);

        var defaultStackTraceLimit = Error.stackTraceLimit;
        // NOTE: do not set undefined; undefined captures NO STACK TRACE AT ALL in some browsers
        Error.stackTraceLimit = 100;
        _initElements(Mapbender.configuration.elements);

        Error.stackTraceLimit = defaultStackTraceLimit;

        // Tell the world that all widgets have been set up. Some elements will
        // need this to make calls to other element's widgets
        $(document).trigger('mapbender.setupfinished');
        $('.js-splashscreen-autohide').addClass("removed").one("transitionend", (e) => $(e.target).remove());
    }

    return {
        source: {},
        initElement: initElement,
        setup: setup,
        /**
         * @typedef {Object} mbUserInfo
         * @property {String|null} name
         * @property {boolean} isAnonymous
         * @property {Array<String>} roles
         */
        /**
         * @returns {Promise<mbUserInfo>}
         * @since v3.2.2
         */
        loadUserInfo: (function() {
            // Fetch once, reuse response
            var promise = null;
            return function() {
                promise = promise || $.getJSON('../userinfo.json').promise();
                return promise;
            }
        }())
    }
}(jQuery)));


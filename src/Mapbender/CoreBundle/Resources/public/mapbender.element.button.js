(function ($) {
    'use strict';
    /**
     * Just a button that toggles its own visual highlighting state on / off on every click.
     * If it has a group option, it will try to find other buttons with the same group and
     * deactivate them before activating itself.
     *
     * Override activate and deactivate to add functionality.
     */
    $.widget("mapbender.mbBaseButton", {
        options: {
            label: undefined,
            icon: undefined,
            /**
             * @var {String|null|undefined}
             */
            // Buttons in the same group have mutual exclusion. Only one of them
            // can be active at a time.
            group: undefined
        },
        active: false,
        $toolBarItem: null,

        _create: function() {
            this.$toolBarItem = $(this.element).closest('.toolBarItem');
            $(this.element)
                .on('click', $.proxy(this._onClick, this))
                .on('mbButtonDeactivate', $.proxy(this.deactivate, this));
            // child widget may have initialized highlight state to a non-default
            this._setActive(this.isActive());
        },
        /**
         * Toggles the button state active / inactive.
         * Deactivates same-group siblings, if any, when activated.
         * Updates own visual highlighting state.
         * @private
         */
        _onClick: function () {
            if (this.isActive()) {
                this.deactivate();
            } else {
                // If we're part of a group, deactivate all other buttons in the same group
                // Do this BEFORE updating
                if (this.options.group) {
                    var $others = $('.mb-button[data-group="' + this.options.group + '"]')
                        .not(this);
                    $others.each(function() {
                        try {
                            $(this).trigger('mbButtonDeactivate');
                        } catch (e) {
                            console.error("Suppressing error from sibling button deactivate handler", e);
                        }
                    });
                }
                this.activate();
            }
        },
        /**
         * Turns on highlight, remembers active state for next click.
         * Override this if you need more stuff to happen
         */
        activate: function() {
            this._setActive(true);
        },
        /**
         * Turns off highlight, remembers inactive state for next click.
         * Override this if you need more stuff to happen
         */
        deactivate: function() {
            this._setActive(false);
        },
        /**
         * Alias of deactivate in base implementation. Commonly used as an on-close callback for popups,
         * invoked when they close, thus informing the button to clear its highlight, and that the next
         * click should be another activation.
         *
         * For this reason, you should not override this to trigger external cleanup logic. Override
         * deactivate instead.
         */
        reset: function () {
            this._setActive(false);
        },
        /**
         * Stores a new .active value and updates the highlighting with no further side effects
         * (no events etc).
         *
         * @param isActive
         * @private
         */
        _setActive: function(isActive) {
            this.active = !!isActive;
            this.$toolBarItem.toggleClass("toolBarItemActive", this.active);
        },
        /**
         * Trivial base implementation. Override this if you're not really sure ahead of time
         * what the button state is. Evaluated in click event handler.
         *
         * @return {boolean}
         */
        isActive: function() {
            return this.active;
        }
    });
    /**
     * Just a button that toggles its own highlighting state on / off on every click.
     * This is how the control button was called. We provide this alias name because
     * many Element widgets inherit from the mapbender.mbButton, despite having nothing
     * at all to do with controlling other Element widgets. Extended control logic
     * in proper control button has made it somewhat incompatible with this
     * non-controlling button usage.
     */
    $.widget("mapbender.mbButton", $.mapbender.mbBaseButton, {
        options: {
            // legacy options, not required for operation of base / control buttons
            label: undefined,
            icon: undefined
        }
    });

    /**
     * A button that controls other Element widgets. Never, ever inherit from this unless
     * your intention is to control other Element widgets.
     */
    $.widget("mapbender.mbControlButton", $.mapbender.mbBaseButton, {
        options: {
            target: undefined,
            click: undefined
        },

        targetWidget: null,
        actionMethods: null,

        _create: function () {
            if (this.options.click) {
                // this widget instance is superfluous, we rendered a link
                // we can't really deactivate mapbender.initElement machinery
                // so we still need to load the JS asset, and we still end up right here
                return;
            }
            var self = this;
            if (this.options.target) {
                $(document).on('mapbender.setupfinished', function() {
                    self._initializeHighlightState();
                });
                $(document).on('mapbender.elementactivated mapbender.elementdeactivated', function(e, data) {
                    if (data.sender !== self && data.widget === self._initializeTarget()) {
                        // Our target element has been activated or deactivated, but not by us
                        // Remember new target state and update our own highlighting
                        self._setActive(data.active);
                    }
                });
            }
            this._super();
            // Amenity for special snowflake mobile.js, which expects to see us under
            // the 'mapbenderMbButton' data key
            this.element.data('mapbenderMbButton', this);
        },
        /**
         *
         * @param {*} object
         * @param {string[]} names
         * @return {Array<function>}
         * @private
         */
        _extractCallableMethods: function(object, names) {
            return names.map(function(name) {
                var method = name && object[name];
                return typeof method === 'function' ? method: null;
            }).filter(function(x) {
                // throw out anything emptyish (including the nulls just produced)
                return !!x;
            });
        },
        /**
         *
         * @param targetWidget
         * @return {{activate: function|null, deactivate: function|null}}
         * @private
         */
        _extractActionMethods: function(targetWidget) {
            var methodPair = {
                activate: null,
                deactivate: null
            };
            var activateCandidateNames = [this.options.action, 'defaultAction', 'open', 'activate'];
            var deactivateCandidateNames = [this.options.deactivate, 'close', 'deactivate'];
            var activateCandidates = this._extractCallableMethods(
                targetWidget, activateCandidateNames);
            var deactivateCandidates = this._extractCallableMethods(
                targetWidget, deactivateCandidateNames);
            if (activateCandidates.length) {
                methodPair.activate = activateCandidates[0]
                    .bind(targetWidget, this.reset.bind(this));
            } else {
                console.error("Target widget", targetWidget,
                              "does not seem to have any potential activation method.",
                              "Tried: ",  activateCandidateNames);
            }
            if (deactivateCandidates.length) {
                methodPair.deactivate = deactivateCandidates[0]
                    .bind(targetWidget, this.reset.bind(this));
            } else {
                console.error("Target widget", targetWidget,
                              "does not seem to have any potential deactivation method.",
                              "Tried: ",  deactivateCandidateNames);
            }
            return methodPair;
        },
        /**
         * @returns {null|object} the target widget object (NOT the DOM node; NOT a jQuery selection)
         * @private
         */
        _initializeTarget: function() {
            // Initialize only once, remember the result forever.
            // This makes elements work that move around in / completely out of the DOM, either
            // by themselves, or because they let certain popups mangle their DOM nodes.
            if (this.targetWidget === null && this.options.target) {
                var targetConf = Mapbender.configuration.elements[this.options.target];
                if (!targetConf || !targetConf.init) {
                    console.error("Button target element not found in element configuration", targetConf, this.options);
                    this.targetWidget = false;
                    return null;
                }
                var targetInit = targetConf.init;
                var $target = $('#' + this.options.target);
                var nameParts = targetInit.split('.');
                if (nameParts.length === 1) {
                    // This is a BC construct currently without known or conceivable use cases
                    this.targetWidget = $target[nameParts];
                } else {
                    var namespace = nameParts[0];
                    var innerName = nameParts[1];
                    // widget data ends up in a key composed of
                    // namespace, no dot, innerName with upper-cased first letter
                    var dataKey = [namespace, innerName.charAt(0).toUpperCase(), innerName.slice(1)].join('');
                    this.targetWidget = $target.data(dataKey);
                }
                if (!this.targetWidget) {
                    console.warn("Could not identify target element", this.options.target, targetInit);
                    // Avoid attempting this again
                    // null: target widget not initialized; false: looked for target widget but got nothing
                    this.targetWidget = false;
                }
            }
            return this.targetWidget || null;
        },
        _initializeActionMethods: function() {
            if (this.actionMethods === null) {
                if (this._initializeTarget()) {
                    this.actionMethods = this._extractActionMethods(this.targetWidget);
                } else {
                    this.actionMethods = {};
                }
            }
        },
        _initializeHighlightState: function() {
            // skip logic if already active
            if (this.active) {
                return;
            }
            if (this._initializeTarget() && this.targetWidget.options) {
                var targetOptions = this.targetWidget.options;
                var state = targetOptions.autoActivate;         // FeatureInfo style
                state = state || targetOptions.autoStart;       // GpsPosition style
                // Copyright, Legend, Layertree, WmsLoader all use have this option
                if (targetOptions.autoOpen) {
                    var isDialog = true;        // WmsLoader: always a dialog
                    if (typeof targetOptions.type !== 'undefined') {
                        // Layertree, FeatureInfo
                        isDialog = targetOptions.type === 'dialog';
                    } else if (typeof targetOptions.displayType !== 'undefined') {
                        // Legend
                        isDialog = targetOptions.displayType === 'dialog';
                    }
                    state = isDialog;
                }
                if (targetOptions.auto_activate) {              // Redlining
                    state = targetOptions.display_type === 'dialog';
                }
                this._setActive(!!state);
            }
        },
        /**
         * Calls 'activate' method on target if defined, and if in group, sets a visual highlight
         */
        activate: function () {
            // defensive activation to prevent redundant state transitions
            if (this.active) {
                return;
            }
            this._initializeActionMethods();
            if (this.actionMethods.activate) {
                (this.actionMethods.activate)();
                // Inform other control buttons (and whoever else is listening) that the
                // target Element has just been activated
                $(document).trigger('mapbender.elementactivated', {
                    widget: this.targetWidget,
                    sender: this,
                    active: true
                });
            }
            this._super();
        },
        /**
         * Clears visual highlighting, marks inactive state and
         * calls 'deactivate' method on target (if defined)
         */
        deactivate: function () {
            if (!this.active) {
                // defensive deactivation to prevent unneeded state transitions
                return;
            }
            this._initializeActionMethods();
            if (this.actionMethods.deactivate) {
                (this.actionMethods.deactivate)();
                // Inform other control buttons (and whoever else is listening) that the
                // target Element has just been deactivated
                $(document).trigger('mapbender.elementdeactivated', {
                    widget: this.targetWidget,
                    sender: this,
                    active: false
                });
            }
            this._super();
        }
    });
})(jQuery);

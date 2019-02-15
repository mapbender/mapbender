(function ($) {
    'use strict';

    $.widget("mapbender.mbButton", {
        options: {
            target: undefined,
            click: undefined,
            icon: undefined,
            label: true,
            group: undefined
        },

        active: false,
        targetWidget: null,
        $toolBarItem: null,
        actionMethods: null,

        _create: function () {
            if (this.options.click) {
                // this widget instance is superfluous, we rendered a link
                // we can't really deactivate mapbender.initElement machinery
                // so we still need to load the JS asset, and we still end up right here
                return;
            }
            var self = this,
                option = {};

            this.$toolBarItem = $(this.element).closest('.toolBarItem');

            if (this.options.icon) {
                $.extend(option, {
                    icons: {
                        primary: this.options.icon
                    },
                    text: this.options.label
                });
            }

            if (this.options.target) {
                $(document).on('mapbender.setupfinished', function() {
                    self._initializeHighlightState();
                });
            }
            $(this.element)
                .on('click', $.proxy(self._onClick, self))
                .on('mbButtonDeactivate', $.proxy(self.deactivate, self));
        },

        _onClick: function () {
            var $me = $(this.element);

            // If we're part of a group, deactivate all other actions in this group
            if (this.options.group) {
                var others = $('.mb-button[data-group="' + this.options.group + '"]')
                    .not($me);

                try {
                    others.trigger('mbButtonDeactivate');
                } catch (e) {
                    console.error("Suppressing error from sibling button deactivate handlers", e);
                }
            }

            if (!this.active) {
                this.activate();
            } else {
                this.deactivate();
            }
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
            var activateCandidateNames = [this.options.action, 'defaultAction', 'activate', 'open'];
            var deactivateCandidateNames = [this.options.deactivate, 'deactivate', 'close'];
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
                this._highlightState = state;
                this.active = this.active || state;
            } else {
                this._highlightState = false;
            }

            this.updateHighlight();
        },
        /**
         * Calls 'activate' method on target if defined, and if in group, sets a visual highlight
         */
        activate: function () {
            if (this.active) {
                return;
            }
            this._initializeActionMethods();
            if (this.actionMethods.activate) {
                (this.actionMethods.activate)();
                this.active = true;
            }
            this._highlightState = this.active || !!this.options.group;
            this.updateHighlight();
        },
        /**
         * Clears visual highlighting, marks inactive state and
         * calls 'deactivate' method on target (if defined)
         */
        deactivate: function () {
            this._initializeActionMethods();
            if (this.actionMethods.deactivate) {
                (this.actionMethods.deactivate)();
            }
            this.reset();
        },
        /**
         * Clears visual highlighting, marks inactive state
         */
        reset: function () {
            this.active = false;
            this._highlightState = false;
            this.updateHighlight();
        },
        updateHighlight: function() {
            this.$toolBarItem.toggleClass("toolBarItemActive", !!this._highlightState);
        }
    });

})(jQuery);

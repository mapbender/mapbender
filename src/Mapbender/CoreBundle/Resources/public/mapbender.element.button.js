(function ($) {
    'use strict';

    $.widget("mapbender.mbButton", $.mapbender.mbBaseElement, {
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
        /** initialized to true if button can determine target's active state and remember its own active state */
        stateful: null,
        actionMethods: {},

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

            if (!this.stateful || !this.active) {
                this.activate();
            } else {
                this.deactivate();
            }
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
                if (this.targetWidget) {
                    this.actionMethods = {
                        activate: null,
                        deactivate: null
                    };
                    var activateAction = this.options.action || 'defaultAction';
                    var deactivateAction = this.options.deactivate;
                    if (activateAction) {
                        if (typeof this.targetWidget[activateAction] === 'function') {
                            this.actionMethods.activate = this.targetWidget[activateAction].bind(this.targetWidget, this.reset.bind(this));
                        } else {
                            console.error("Target widget", this.options.target, this.targetWidget,
                                          "does not have a callable method", activateAction);
                        }
                    }
                    if (deactivateAction) {
                        if (typeof this.targetWidget[deactivateAction] === 'function') {
                            this.actionMethods.deactivate = this.targetWidget[deactivateAction].bind(this.targetWidget);
                        } else{
                            console.error("Target widget", this.options.target, this.targetWidget,
                                          "does not have a callable method", deactivateAction);
                        }
                    }
                    // If we do not have a deactivate method we have no way to turn the target off, even if we
                    // can turn it on. This makes internal active state tracking becomes meaningless. We should treat
                    // every click as an 'activate' action.
                    this.stateful = !!this.actionMethods.deactivate;
                } else {
                    console.warn("Could not identify target element", this.options.target, targetInit);
                    // Avoid attempting this again
                    // null: target widget not initialized; false: looked for target widget but got nothing
                    this.targetWidget = false;
                    this.stateful = false;
                }
            }
            return this.targetWidget || null;
        },
        /**
         * Calls 'activate' method on target if defined, and if in group, sets a visual highlight
         */
        activate: function () {
            if (this.stateful && this.active) {
                return;
            }
            this._initializeTarget();
            if (this.actionMethods.activate) {
                (this.actionMethods.activate)();
                this.active = this.stateful;
            }
            if (this.options.group) {
                this.$toolBarItem.addClass("toolBarItemActive");
            }
        },
        /**
         * Clears visual highlighting, marks inactive state and
         * calls 'deactivate' method on target (if defined)
         */
        deactivate: function () {
            this.reset();
            this._initializeTarget();
            if (this.actionMethods.deactivate) {
                (this.actionMethods.deactivate)();
                this.active = false;
            }
        },
        /**
         * Clears visual highlighting, marks inactive state
         */
        reset: function () {
            this.$toolBarItem.removeClass("toolBarItemActive");
            this.active = false;
        }
    });

})(jQuery);

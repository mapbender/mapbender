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
        button : null,
        targetWidget: null,

        _create: function () {
            var self = this,
                option = {};

            this.button = this.element[0];

            if (this.options.icon) {
                $.extend(option, {
                    icons: {
                        primary: this.options.icon
                    },
                    text: this.options.label
                });
            }

            if (this.options.group) {
                this.button.checked = false;
            }

            $(this.button)
                .on('click', $.proxy(self._onClick, self))
                .on('mbButtonDeactivate', $.proxy(self.deactivate, self));
        },

        _onClick: function () {
            var $me = $(this.element);

            if (this.options.click) {
                window.open(this.options.click, '_blank');
                return;
            }

            // If we're part of a group, deactivate all other actions in this group
            if (this.options.group) {
                var others = $('input[type="checkbox"]')
                    .filter('[name="mb-button-group[' + this.options.group + ']"]')
                    .not($me);

                others.trigger('mbButtonDeactivate');
            }

            if (this.active) {
                this.deactivate();
            } else {
                this.activate();
            }
        },
        /**
         * @returns {null|object} the target widget object (NOT the DOM node; NOT a jQuery selection)
         * @private
         */
        _getTargetWidget: function() {
            // Initialize only once, remember the result forever.
            // This makes elements work that move around in / completely out of the DOM, either
            // by themselves, or because they let certain popups mangle their DOM nodes.
            if (this.targetWidget === null && this.options.target) {
                var $target = $('#' + this.options.target);
                var targetInit = Mapbender.configuration.elements[this.options.target].init;
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

        _callTarget: function(methodName, args) {
            var target = this._getTargetWidget();
            if (target) {
                if (typeof target[methodName] === 'function') {
                    target[methodName].apply(target, args || []);
                    return true;
                } else {
                    console.error("Target widget", this.options.target, target, "does not have a callable method", methodName);
                    return false;
                }
            } else {
                return true;
            }
        },
        /**
         * Calls 'activate' method on target if defined, and if in group, sets a visual highlight
         */
        activate: function () {
            this.active = true;

            if (this.options.target) {
                this._callTarget(this.options.action || 'defaultAction', [this.reset.bind(this)]);

            }
            if (this.options.group) {
                this.button.checked = true;
                $(this.button).parent().addClass("toolBarItemActive");
            }
        },
        /**
         * Clears visual highlighting, marks inactive state and
         * calls 'deactivate' method on target (if defined)
         */
        deactivate: function () {
            this.reset();
            if (this.options.target && this.options.deactivate) {
                this._callTarget(this.options.deactivate);
            }
        },
        /**
         * Clears visual highlighting, marks inactive state
         */
        reset: function () {
            $(this.button).parent().removeClass("toolBarItemActive");

            if (this.active) {
                this.active = false;
            }

            if (this.options.group) {
                this.button.checked = false;
            }
        }
    });

})(jQuery);

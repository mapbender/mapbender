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

            if (this.options.click && (this.options.click.length > 0) && (this.options.click.charAt(0) === '#')) {
                return;
            }

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

        activate: function () {
            this.active = true;

            if (this.options.target) {
                var target = $('#' + this.options.target),
                    widget = Mapbender.configuration.elements[this.options.target].init.split('.'),
                    action = this.options.action;

                $(this.button).parent().addClass("toolBarItemActive");

                if (!this.options.action) {
                    action = "defaultAction";
                }

                if (widget.length === 1) {
                    target[widget[0]](action, $.proxy(this.reset, this));
                } else {
                    var dataKey = widget[0] + widget[1].charAt(0).toUpperCase() + widget[1].slice(1);

                    if (typeof target.data(dataKey)[action] === 'function') {
                        target[widget[1]](action, $.proxy(this.reset, this));
                    }
                }
            }

            if (!this.options.group) {
                this.deactivate();
            } else {
                this.button.checked = true;
            }
        },

        deactivate: function () {
            $(this.button).parent().removeClass("toolBarItemActive");

            if (this.options.target && this.options.deactivate) {
                var target = $('#' + this.options.target),
                    widget = Mapbender.configuration.elements[this.options.target].init.split('.');

                if (widget.length === 1) {
                    target[widget[0]](this.options.deactivate);
                } else {
                    target[widget[1]](this.options.deactivate);
                }
            }

            if (this.active) {
                this.active = false;
            }

            if (this.options.group) {
                this.button.checked = false;
            }
        },

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

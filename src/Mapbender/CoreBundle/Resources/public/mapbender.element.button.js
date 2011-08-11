(function($) {

$.widget("mapbender.mbButton", {
    options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined
    },

    active: false,
    button : null,

    _create: function() {
        var self = this;
        var me = $(this.element);
        
        this.button = this.element[0];

        var o = {};
        if(this.options.icon) {
            $.extend(o, {
                icons: {
                    primary: this.options.icon,
                },
                text: this.options.label
            });
        }

        if(this.options.group) {
            this.button.checked = false;
        }

        $(this.button).button(o)
            .bind('click', $.proxy(self._onClick, self))
            .bind('mbButtonDeactivate', $.proxy(self.deactivate, self));
    },

    _setOption: function(key, value) {
    },

    _onClick: function() {
        var me = $(this.element);
 
        // If we're part of a group, deactivate all other actions in this group
        if(this.options.group) {
            var others = $('input[type="checkbox"]')
                .filter('[name="mb-button-group[' + this.options.group + ']"]')
                .not(me);
            others.trigger('mbButtonDeactivate');
        }

        this.active ? this.deactivate() : this.activate();
    },

    activate: function() {
        this.active = true;
        if(this.options.target && this.options.action) {
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init;
            target[widget](this.options.action);
        }
        if(!this.options.group) {
            this.deactivate();
        } else {
            this.button.checked = true;
            $(this.button).button('refresh');
        }
    },

    deactivate: function() {
        if(this.active) {
            this.active = false;
        }
        if(this.options.group) {
            this.button.checked = false;
            $(this.button).button('refresh');
        }
    }
});

})(jQuery);


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

    _create: function() {
        var self = this;
        var me = $(this.element);

        var o = {};
        if(this.options.icon) {
            $.extend(o, {
                icons: {
                    primary: this.options.icon,
                },
                text: this.options.label
            });
        }

        me.button(o)
            .bind('click', $.proxy(self._onClick, self))
            .bind('mbButtonDeactivate', $.proxy(self.deactivate, self));
    },

    _setOption: function(key, value) {
    },

    _onClick: function() {
        var me = $(this.element);
        
        // If we're part of a group, deactivate all other actions in this group
        if(this.options.group) {
            var others = $('.mb-element-button.mb-button-group-' + this.options.group)
                .not(me);
            others.trigger('mbButtonDeactivate');
        }

        this.active ? this.deactivate() : this.activate();

        return false;
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
        }
    },

    deactivate: function() {
        if(this.active) {
            this.active = false;
            $(this.element).removeClass('ui-state-focus');
        }
    }
});

})(jQuery);


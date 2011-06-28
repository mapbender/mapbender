(function($) {

$.widget("mapbender.mb_button", {
	options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined
    },

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

        // Radios are inside a div, so we need to button'ize the right element
        this.options.group ? me.find('input').button(o) : me.button(o);
        
        me.bind('click', $.proxy(self._onClick, self));
        me.bind('deactivate', $.proxy(self.deactivate, self));
	},

	_setOption: function(key, value) {
	},

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
	},

    _onClick: function() {
        // If we're part of a group, deactivate all other actions in this group
        if(this.options.group) {
            var others = $('input[type="radio"][name="' + this.options.group + '"]').parent();
            others.trigger('deactivate');
        }
        this.activate();
    },

    _onChange: function() {
        if($(this.element).filter(':checked')) {
            console.log('c', $(this.element).attr('id'));
        } else {
            console.log('n', $(this.element).attr('id'));
        }
    },

    activate: function() {
        if(this.options.target && this.options.action) {
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init;
            target[widget](this.options.action);
        }
    },

    deactivate: function() {
    }
});

})(jQuery);


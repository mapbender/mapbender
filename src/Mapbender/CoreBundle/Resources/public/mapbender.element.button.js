(function($) {

$.widget("mapbender.mb_button", {
	options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true
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
        me.button(o);
        me.click(function() { 
            self._onClick.call(self);
        });
	},

	_setOption: function(key, value) {
	},

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
	},

    _onClick: function() {
		if(this.options.target && this.options.action) {
			var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init;
            target[widget](this.options.action);
		}
    }
});

})(jQuery);

(function($) {

$.widget("mapbender.mb_button", {
	options: {},

	_create: function() {
		var self = this;
		var me = $(this.element);
		me.button();
		
		if(this.options.target && this.options.click) {
			var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init;
            me.click(function() {
                    target[widget](self.options.click);
            });
		}
	},

	_setOption: function(key, value) {
	},

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
	}
});

})(jQuery);

(function($) {

$.widget("mapbender.mb_about_dialog", {
	options: {},

    dlg: null,
    elementUrl: null,

	_create: function() {
		var self = this;
		var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
		me.button();
        me.click(function() { self._onClick.call(self); });
    },

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
	},

    _onClick: function() {
        if(!this.dlg) {
            this._initDialog();
        }
        this.dlg.dialog('open');
    },

    _initDialog: function() {
        var self = this;
        this.dlg = $('<div></div>')
            .attr('id', 'mb-about-dialog')
            .html('Loading...')
            .appendTo($('body'))
            .dialog({
                title: 'About Mapbender',
                autoOpen: false,
                modal: true
            });

        $.ajax(this.elementUrl + 'about', {
            dataType: 'json',
            context: self,
            success: self._onAjaxSuccess,
            error: self._onAjaxError
        });
    },

    _onAjaxSuccess: function(data) {
        var html = '<ul>';
        $.each(data, function(key, val) {
            html += '<li>' + key +  ': ' + val + '</li>';
        });
        html += '</ul>';
        this.dlg.html(html);
    },

    _onAjaxError: function(XHR, d, f) {
        console.log(arguments);
    }
});

})(jQuery);

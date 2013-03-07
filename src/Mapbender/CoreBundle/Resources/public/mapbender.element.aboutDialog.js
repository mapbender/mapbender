(function($) {

    $.widget("mapbender.mbAboutDialog", {
        options: {},

        dlg: null,
        elementUrl: null,

        _create: function() {
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            me.button();
            me.click(function() {
                self._onClick.call(self);
            });
        },

        _onClick: function() {
            if(!this.dlg) {
                this._initDialog();
            }
            this.dlg.dialog('open');
        },

        _initDialog: function() {
            var self = this;
            if(this.dlg === null) {
                this.dlg = $('<div></div>')
                .attr('id', 'mb-about-dialog')
                .appendTo($('body'))
                .dialog({
                    title: 'About Mapbender',
                    autoOpen: false,
                    modal: true
                });
                $.get(this.elementUrl + 'about', function(data) {
                    self.dlg.html(data);
                    self.dlg.dialog('open');
                });
            } else {
                this.dlg.dialog('open');
            }
        }
    });

})(jQuery);

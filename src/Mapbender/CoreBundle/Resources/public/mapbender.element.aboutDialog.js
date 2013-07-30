(function($) {

    $.widget("mapbender.mbAboutDialog", {
        options: {},

        elementUrl: null,

        _create: function() {
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            me.click(function() {
                self._onClick.call(self);
            });
        },

        _onClick: function() {
            if(!$('body').data('mapbenderMbPopup')) {
                $.get(this.elementUrl + 'about', function(data) {
                    $("body").mbPopup();
                    $("body").mbPopup('showHint', {title:"About Mapbender", showHeader:true, content: data, width:350, height:70, draggable:true});
                });
            }
        }
    });

})(jQuery);

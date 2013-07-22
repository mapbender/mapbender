(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},

        _create: function() {
            $('#' + $(this.element).attr("id")).find('.mb-element-copyright-link').click($.proxy(this._showTermsOfUse, this));
        },

        _onClick: function() {
            if(!$('body').data('mapbenderMbPopup')) {
                var source = $('#' + $(this.element).attr("id") + "-dialog");
                var title = source.attr("title");
                var content = source.find(".mb-element-copyright-content").text();
                $("body").mbPopup();
                $("body").mbPopup('showHint', {title:title, showHeader:true, content: content});
            }
        },

        _destroy: $.noop
    });

})(jQuery);


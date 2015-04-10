(function($) {
    $.widget("mapbender.mbHTMLElement", {
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;

            if(options.hasOwnProperty('items')) {
                var items = $.isArray(options.items) ? options.items : [options.items];
                if(items[0].type == "popup") {
                    element = $("<div/>");
                }
                element.generateElements({items: items});
            }
        }
    });
})(jQuery);

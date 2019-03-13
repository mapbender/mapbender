(function($) {
    $.widget("mapbender.mbHTMLElement", {
        /**
         * @todo 3.0.8.0: this widget should do absolutely nothing. Its 'content' is rendered server side.
         *   mapbender/data-source's "BaseElement" is the lowest point where vis-ui may be required, not
         *   core mapbender, not in a simple HTML element.
         */
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

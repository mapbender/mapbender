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
            var hasItems = options.hasOwnProperty('children');
            /**
             * @deprecated, to be removed in 3.0.8.0
             */
            function findItem(type, name, children) {
                var r = [];
                children = children ? children : (hasItems ? options.children : []);

                if(children && !$.isArray(children)) {
                    children = [children];
                }

                $.each(children, function(i, item) {
                    if(!item.hasOwnProperty('type')) {
                        return;
                    }

                    if(item.type == type || type == '*') {
                        r.push(item);
                    }

                    if(item.hasOwnProperty('children')) {
                        $.merge(r, findItem(type, name, item['children']));
                    }
                });
                return r;
            }

            /**
             * @deprecated, to be removed in 3.0.8.0
             */
            function render() {
                if(hasItems) {
                    var children = $.isArray(options.children) ? options.children : [options.children];
                    if(children[0].type == "popup") {
                        element = $("<div/>");
                    }
                    element.generateElements({children: children});
                }
            }

            if(options.hasOwnProperty('js')) {
                $.ajax({
                    url:      Mapbender.configuration.application.urls.asset + options.js,
                    dataType: 'text',
                    success:  function(script) {
                        eval(script);
                        render();
                    }
                });
            } else {
                render();
            }
        }
    });
})(jQuery);

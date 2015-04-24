(function($) {
    $.widget("mapbender.mbHTMLElement", {
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;
            var hasItems = options.hasOwnProperty('children');

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

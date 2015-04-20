(function($) {
    $.widget("mapbender.mbHTMLElement", {
        _create: function() {
            var widget = this;
            var element = $(widget.element);
            var options = widget.options;
            var hasItems = options.hasOwnProperty('items');

            function findItem(type, name, items) {
                var r = [];
                items = items ? items : (hasItems ? options.items : []);

                if(items && !$.isArray(items)) {
                    items = [items];
                }

                $.each(items, function(i, item) {
                    if(!item.hasOwnProperty('type')) {
                        return;
                    }

                    if(item.type == type || type == '*') {
                        r.push(item);
                    }

                    if(item.hasOwnProperty('items')) {
                        $.merge(r, findItem(type, name, item['items']));
                    }
                });
                return r;
            }

            function render(){
                if(hasItems) {
                    var items = $.isArray(options.items) ? options.items : [options.items];
                    if(items[0].type == "popup") {
                        element = $("<div/>");
                    }
                    element.generateElements({items: items});
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
            }else{
                render();
            }
        }
    });
})(jQuery);

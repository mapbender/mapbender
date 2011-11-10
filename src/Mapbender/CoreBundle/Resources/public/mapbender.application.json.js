(function($) {

    var plugin = {
        options: {},
        element: undefined,

        construct: function(options) {
            if(typeof(this.options.jsonUrl) === 'undefined') {
                this.onError(undefined, 'error', 'You must set the jsonUrl option.');
            }

            $.ajax({
                url: options.jsonUrl,
                context: this,
                success: this.onSuccess,
                error: this.onError
            });
        },

        onSuccess: function(json) {
            if(typeof(json.html) !== 'string') {
                throw "No HTML in JSON.";
            }
            this.element.html(json.html);

            var self = this,
                head = $('head'),
                body = $('body');
            if(json.assets && $.isArray(json.assets.css)) {
                $.each(json.assets.css, function(index, path) {
                    $('<link rel="stylesheet" type="text/css" href="' + json.configuration.assetPath + path + '"/>')
                        .appendTo(head);
                });
            }

            $.isReady = false;
            if(json.configuration) {
                Mapbender = {};
                Mapbender.configuration = json.configuration;
            }

            if(json.assets && $.isArray(json.assets.js)) {
                $.each(json.assets.js, function(index, path) {
                    $('<script type="text/javascript" src="' + json.configuration.assetPath + path + '"></script>')
                        .appendTo(head);
                });
            }
            $.isReady = true;

            OpenLayers._getScriptLocation = function() {
                return json.configuration.assetPath + 'bundles/mapbendercore/mapquery/lib/openlayers/';
            };

            Mapbender.setup();
        },

        onError: function(jqXHR, textStatus, errorThrown) {
            throw textStatus + ': ' + errorThrown;
        }
    };

    $.fn.mapbenderload = function(method) {
        // Method dispatcher
        if(typeof(plugin[method]) === 'function') {
            if(method !== 'construct') {
                var args = Array.prototype.slice.call(arguments, 1);
                return plugin[method].apply(this, args);
            }
        } else if(typeof(method) === 'object' || typeof(method) === 'undefined') {
            plugin.options = arguments[0] || {};
            plugin.element = this;
            return plugin.construct.apply(plugin, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.mapbenderload');
        }
    };

})(jQuery);


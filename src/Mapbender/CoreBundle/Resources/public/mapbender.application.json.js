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
            if(typeof(json.html) !== 'undefined') {
                this.element.html(json.html);
            }

            var self = this,
                head = $('head'),
                body = $('body'),
                prefix = '';
            if(json.assets && $.isArray(json.assets.css)) {
                if(json.configuration && json.configuration.assetPath) {
                    prefix = json.configuration.assetPath;
                }

                $.each(json.assets.css, function(index, path) {
                    $('<link rel="stylesheet" type="text/css" href="' + prefix + path + '"/>')
                        .appendTo(head);
                });
            }

            $.isReady = false;
            if(json.configuration) {
                Mapbender = {};
                Mapbender.configuration = json.configuration;
            }

            if(json.assets && $.isArray(json.assets.js)) {
                if(json.configuration && json.configuration.assetPath) {
                    prefix = json.configuration.assetPath;
                }
                $.each(json.assets.js, function(index, path) {
                    $('<script type="text/javascript" src="' + prefix + path + '"></script>')
                        .appendTo(head);
                });
            }
            $.isReady = true;

            OpenLayers._getScriptLocation = function() {
                return json.configuration.assetPath + 'bundles/mapbendercore/mapquery/lib/openlayers/';
            };

            if(typeof(Mapbender) !== 'undefined' && typeof(Mapbender.setup) === 'function') {
                Mapbender.setup();
            }
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


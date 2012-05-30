(function($) {
    var Plugin = function() {};

    $.extend(Plugin.prototype, {
        options: {num: -1},
        element: undefined,
        loadCounter: -1,
        checkCounter: -1,
        cssToLoad: {},
        json: {},

        construct: function(options) {
            if(typeof(this.options.jsonUrl) === 'undefined') {
                this.onError(undefined, 'error', 'You must set the jsonUrl option.');
            }

            $.ajax({
                url: options.jsonUrl,
                data: this.getUrlParameters(),
                dataType: 'json',
                context: this,
                success: this.onSuccess,
                error: this.onError
            });
        },

        getUrlParameters: function() {
            var map = {};
            var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                map[key] = unescape(value);
            });
            return map;
        },

        onSuccess: function(json) {
            this.loadHtml(json);
            this.loadCss(json);
            this.loadConfiguration(json);
            this.checkCounter = 100;
            this.json = json;
            this.loadJs();
        },

        loadHtml: function(json) {
            if(typeof(json.html) !== 'undefined') {
                this.element.html(json.html);
            }
        },

        loadCss: function(json) {
            var that = this;
            if(json.assets && json.assets.css) {
                that.cssToLoad = {};
                var head = $('head');
                if(!$.isArray(json.assets.css)) {
                    json.assets.css = [json.assets.css];
                }
                
                $.each(json.assets.css, function(k, v) {
                    // !!! IE7 important: typeof(v[0]) !== 'undefined'
                    if(typeof(v[0]) !== 'undefined' && v[0] !== '/' && json.configuration && json.configuration.assetPath) {
                        v = json.configuration.assetPath + '/' + v;
                    }
                    var css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.type = 'text/css';
                    css.href = v;
                    var path = v.split("/");
                    that.cssToLoad[path[path.length - 1]] = css;
                    document.getElementsByTagName('head')[0].appendChild(css);
                    // do not use jquery to load it, will not work in IE!
                    // $('<link rel="stylesheet" type="text/css" href="' + v + '"/>')
                    //     .appendTo(head);
                });
            }
        },

        loadJs: function() {
            var self = this;
            window.console && console.log("loadJs:"+self.checkCounter);
            try {
                if(self.checkCounter > -1){
                    var sheets = document.styleSheets;
                    for(var j = 0; j < sheets.length; j++) {
                        if(sheets[j].href && sheets[j].href != null){
                            var path = sheets[j].href.split("/");
                            if(self.cssToLoad[path[path.length - 1]]){
                                sheets[j].cssRules;
                            }
                        }
                    }
                    window.console && console.log("css loaded");
                    window.console && console.log(self.cssToLoad);
                    self.checkCounter = -1;
                    window.setTimeout($.proxy(self.loadJs, self), 200);
                } else {
                    window.console && console.log("css load timeout");
                    window.console && console.log(self.cssToLoad);
                }
                var json = self.json;
                if(json.assets && json.assets.js) {
                    if(!$.isArray(json.assets.js)) {
                        json.assets.js = [json.assets.js];
                    }
                    $.each(json.assets.js, function(k, v) {
                        // !!! IE7 important: typeof(v[0]) !== 'undefined'
                        if(typeof(v[0]) !== 'undefined' && v[0] !== '/' && json.configuration && json.configuration.assetPath) {
                            v = json.configuration.assetPath + '/' + v;
                        }
                        // do not use jquery to load scripts, will fail on IE!
                        // $.getScript(v, $.proxy(function(data){
                        //                            $.globalEval(data);
                        //                            alert(Mapbender.configuration);
                        //                          }, self));
                        var script = document.createElement('script');
                        script.type = 'text/javascript';
                        script.src = v;
                        // $.getScript(v);
                        window.console && console.log("js load "+v);
                        document.body.appendChild(script);
                        // $('<script type="text/javascript"></script')
                        //     .attr('src', v)
                        //     .appendTo($('body'));
                    });

                }
            } catch(e) {
                self.checkCounter--;
                window.console && console.log("wait for css");
//                var sheets = document.styleSheets;
//                for(var j = 0; j < sheets.length; j++) {
//                    if(sheets[j].href && sheets[j].href != null){
//                        var path = sheets[j].href.split("/");
//                        if(self.cssToLoad[path[path.length - 1]]){
////                                sheets[j].cssRules;
//                            window.console && console.log(sheets[j]);
//                        }
//                    }
//                }
                window.setTimeout($.proxy(self.loadJs, self), 50);
            }
        },

        loadConfiguration: function(json) {
            if(json.configuration) {
                Mapbender = {};
                Mapbender.configuration = json.configuration;
            }
        },

        onError: function(jqXHR, textStatus, errorThrown) {
            throw textStatus + ': ' + errorThrown;
        }

    });

    $.fn.mapbenderload = function(method) {
        var plugin = $(this).data("mapbenderload") || new Plugin();
        if(typeof(plugin[method]) === 'function') {
            if(method !== 'construct') {
                var args = Array.prototype.slice.call(arguments, 1);
                return plugin[method].apply(this, args);
            }
        } else if(typeof(method) === 'object' || typeof(method) === 'undefined') {
            $(this).data("mapbenderload",plugin);
            plugin.options = arguments[0] || {};
            plugin.element = this;
            return plugin.construct.apply(plugin, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.mapbenderload');
        }
    };

})(jQuery);


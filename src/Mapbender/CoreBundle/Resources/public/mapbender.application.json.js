(function($) {
    var Plugin = function() {};

    $.extend(Plugin.prototype, {
        options: {num: -1},
        element: undefined,
        loadCounter: -1,
        cssToLoad: {},
        jsonLoaded: false,
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
            this.json = json;
            this.checkCss(); 
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
                    that.cssToLoad[path[path.length - 1]] = {
                        css: css,
                        checkCounter: 100,
                        cssRulesLength: -1,
                        loaded: false
                    };
                    document.getElementsByTagName('head')[0].appendChild(css);
                    // do not use jquery to load it, will not work in IE!
                    // $('<link rel="stylesheet" type="text/css" href="' + v + '"/>')
                    //     .appendTo(head);
                });
            }
        },

        checkCss: function() {
            var self = this;
            for(cssTL in self.cssToLoad){
                try {
                    if(self.cssToLoad[cssTL].checkCounter > -1){
                        var cssRulesLength = 0;
                        var sheets = document.styleSheets;
                        var name = "";
                        for(var j = 0; j < sheets.length; j++) {
                            if(sheets[j].href && sheets[j].href != null){
                                var path = sheets[j].href.split("/");
                                name = path[path.length - 1];
                                if(self.cssToLoad[name]){
                                    sheets[j].cssRules;
                                    cssRulesLength = sheets[j].cssRules.length;
                                    window.console && console.log(cssTL+" -> cssRules:");
                                    window.console && console.log(sheets[j].cssRules);
                                    break;
                                }
                            }
                        }
                        if(self.cssToLoad[cssTL].cssRulesLength != cssRulesLength){
                            window.console && console.log(cssTL+" -> run once more:"+self.cssToLoad[cssTL].cssRulesLength+"|"+cssRulesLength);
                            self.cssToLoad[cssTL].cssRulesLength = cssRulesLength;
                            window.setTimeout($.proxy(self.checkCss, self), 50);
                        } else if(!self.cssToLoad[cssTL].loaded){
                            self.cssToLoad[cssTL].loaded = true;
                            window.console && console.log(cssTL+" -> run final:"+self.cssToLoad[cssTL].cssRulesLength+"|"+cssRulesLength);
                            window.setTimeout($.proxy(self.checkCss, self), 50);
                        }
                    } 
                } catch(e) {
                    self.cssToLoad[cssTL].checkCounter--;
                    window.console && console.log(cssTL+" -> wait for css");
                    window.setTimeout($.proxy(self.checkCss, self), 50);
                }
            }
            var canLoadJs = true;
            for(cssTL in self.cssToLoad){
                if(self.cssToLoad[cssTL].checkCounter > -1 && !self.cssToLoad[cssTL].loaded){
                    canLoadJs = false;
                }
            }
            if(!canLoadJs){
                window.setTimeout($.proxy(self.checkCss, self), 50);
            }
            if(!self.jsonLoaded){
                self.jsonLoaded = true;
                window.setTimeout($.proxy(self.loadJs, self), 50);
            }
        },
        
        loadJs: function() {
            var json = this.json;
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


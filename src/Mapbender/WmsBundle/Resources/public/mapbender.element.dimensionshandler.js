(function($) {

    $.widget("mapbender.mbDimensionsHandler", {
        options: {
            
        },
        elementUrl: null,
        _create: function() {
            var self = this;
            if (!Mapbender.checkTarget("mbDimensionsHandler", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            
            this._trigger('ready');
            this._ready();
        },
        
        ready: function(callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);

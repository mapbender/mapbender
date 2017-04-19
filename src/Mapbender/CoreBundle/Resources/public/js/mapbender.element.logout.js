(function($){
    $.widget("mapbender.mbLogout", {
        options: {
            target: null
        },
        elementUrl: null,

        _create: function() {
            var self = this;
            var me = $(this.element);
            self._setup();
        },

        _setup: function(){
            var self = this;

            if (self.options.confirm !== null) {
                $(this.element).on('click', function(event) {
                    if (!confirm(self.options.confirm)) {
                        event.preventDefault();
                        return false;
                    }
                });
            }

            this._trigger('ready');
            this._ready();
        },

        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },

        _ready: function() {
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },

        _destroy: $.noop
    });
})(jQuery);

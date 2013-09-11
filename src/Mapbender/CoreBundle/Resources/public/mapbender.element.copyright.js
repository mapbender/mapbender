(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        elementUrl: null,
        popup: null,
        _create: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this._trigger('ready');
            this._ready();
        },
        
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if(!this.popup || !this.popup.$element){
               this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: true,
                    closeButton: true,
                    closeOnOutsideClick: true,
                    content: [ $.ajax({url: self.elementUrl + 'content'})],
                    width: 350,
                    height: 170,
                    buttons: {
                        'ok': {
                            label: 'OK',
                            cssClass: 'button right',
                            callback: function(){
                                this.close();
                            }
                        }
                    }
                });
            } else {
                this.popup.open();
            }
        },
        close: function(){
            if(this.popup){
                this.popup.close();
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
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


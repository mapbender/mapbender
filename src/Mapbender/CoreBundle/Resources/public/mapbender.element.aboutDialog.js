(function($){

    $.widget("mapbender.mbAboutDialog", {
        options: {},
        elementUrl: null,
        popup: null,
        _create: function(){
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            me.click(function(){
                self._onClick.call(self);
            });
            this._trigger('ready');
            this._ready();
        },
        _onClick: function(){
            this.open();
            return false;
        },

        defaultAction: function() {
            return this.open();
        },

        open: function(){
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: true,
                    draggable: false,
                    closeOnOutsideClick: true,
                    closeButton: false,
                    closeOnPopupCloseClick: true,
                    content: [ $.ajax({url: self.elementUrl + 'content'})],
                    width: 350,
                    height: 170,
                    buttons: {
                        'ok': {
                            label: 'OK',
                            cssClass: 'button right',
                            callback: function(){
                                self.close();
                            }
                        }
                    }
                });
            } else {
                this.popup.open();
            }
        },
        close: function(){
            if(this.popup && this.popup.$element){
                this.popup.destroy();
            }
            this.popup = null;
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

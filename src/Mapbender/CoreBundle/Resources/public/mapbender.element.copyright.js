(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        elementUrl: null,
        popup: null,
        _create: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if(this.options.autoOpen){
                this.open();
            }
            this._trigger('ready');
            this._ready();
        },
        
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback) {
            var widget = this;
            var options = widget.options;
            var element = widget.element;
            var width = options.popupWidth ? options.popupWidth : 350;
            var height = options.popupHeight ? options.popupHeight : 350;

            widget.callback = callback ? callback : null;
            if(!widget.popup || !widget.popup.$element){
                widget.popup = new Mapbender.Popup2({
                    title:               element.attr('title'),
                    modal:               true,
                    closeButton:         true,
                    closeOnOutsideClick: true,
                    content:             [ $.ajax({url: widget.elementUrl + 'content'})],
                    width:               width,
                    height:              height,
                    buttons:             {
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
                widget.popup.open();
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
            }
        },
        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);


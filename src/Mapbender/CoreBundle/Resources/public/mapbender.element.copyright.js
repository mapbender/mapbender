(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        elementUrl: null,
        popup: null,
        _create: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
        },

        open: function() {
            var self = this;
            if(!this.popup || !this.popup.$element){
               this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: true,
                    destroyOnClose: true,
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
        },

        _destroy: $.noop
    });

})(jQuery);


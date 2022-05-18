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
        },
        
        open: function(callback) {
            var widget = this;
            var options = widget.options;
            var element = widget.element;
            var width = options.popupWidth ? options.popupWidth : 350;
            this.callback = callback ? callback : null;

            if (!this.popup) {
                $.ajax({url: this.elementUrl + 'content'}).then(function(response) {
                    widget.popup = new Mapbender.Popup({
                        title: element.attr('data-title'),
                        modal:               true,
                        detachOnClose: false,
                        closeOnOutsideClick: true,
                        content: response,
                        width:               width,
                        height: options.popupHeight || null,
                        buttons: [
                            {
                                label: Mapbender.trans('mb.actions.accept'),
                                cssClass: 'button popupClose'
                            }
                        ]
                    });
                    widget.popup.$element.on('close', function() {
                        widget.close();
                    });
                });
            } else {
                this.popup.$element.removeClass('hidden');
                this.popup.focus();
            }
        },
        close: function(){
            if (this.popup) {
                this.popup.$element.addClass('hidden');
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            $(document).trigger('mapbender.elementdeactivated', {widget: this, sender: this, active: false});
        },
        _destroy: $.noop
    });

})(jQuery);


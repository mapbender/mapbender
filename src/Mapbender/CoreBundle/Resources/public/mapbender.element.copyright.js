(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        popup: null,
        content_: null,

        _create: function() {
            this.content_ = $('.-js-popup-content', this.element).remove().removeClass('hidden');
            if(this.options.autoOpen){
                this.open();
            }
            this._trigger('ready');
        },
        
        open: function(callback) {
            this.callback = callback ? callback : null;

            if (!this.popup) {
                this.popup = new Mapbender.Popup({
                    title: this.element.attr('data-title'),
                    modal: true,
                    detachOnClose: false,
                    closeOnOutsideClick: true,
                    content: this.content_,
                    width: this.options.popupWidth || 350,
                    height: this.options.popupHeight || null,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.accept'),
                            cssClass: 'button popupClose'
                        }
                    ]
                });
                var self = this;
                this.popup.$element.on('close', function() {
                    self.close();
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


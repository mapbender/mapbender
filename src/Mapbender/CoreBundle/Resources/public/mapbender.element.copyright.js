(function($) {

    $.widget("mapbender.mbCopyright", $.mapbender.mbDialogElement, {
        options: {},
        popup: null,
        content_: null,

        _create: function() {
            this.content_ = $('.-js-popup-content', this.element).remove().removeClass('hidden');
            if (this.checkAutoOpen()) {
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
                    cssClass: 'copyright-dialog',
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.accept'),
                            cssClass: 'button popupClose'
                        }
                    ]
                });
            } else {
                this.popup.open();
            }
            var self = this;
            this.popup.$element.one('close', function() {
                self.close();
            });

            this.notifyWidgetActivated();
        },
        close: function(){
            if (this.popup) {
                this.popup.close();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            this.notifyWidgetDeactivated();
        },
        _destroy: $.noop
    });

})(jQuery);


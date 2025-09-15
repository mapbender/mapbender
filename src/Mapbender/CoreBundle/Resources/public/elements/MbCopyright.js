(function() {

    class MbCopyright extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.content_ = $('.-js-popup-content', this.$element).remove().removeClass('hidden');
            if (this.checkAutoOpen && this.checkAutoOpen()) {
                this.open();
            }
            this._setup();
        }

        _setup() {
            Mapbender.elementRegistry.markReady(this);
        }

        open(callback) {
            this.callback = callback ? callback : null;

            if (!this.popup) {
                this.popup = new Mapbender.Popup({
                    title: this.$element.attr('data-title'),
                    modal: true,
                    detachOnClose: false,
                    closeOnOutsideClick: true,
                    content: this.content_,
                    width: this.options.popupWidth || 350,
                    height: this.options.popupHeight || null,
                    cssClass: 'copyright-dialog',
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'btn btn-sm btn-light popupClose'
                        }
                    ]
                });
            } else {
                this.popup.open();
            }
            const self = this;
            this.popup.$element.one('close', function() {
                self.close();
            });

            if (this.notifyWidgetActivated) {
                this.notifyWidgetActivated();
            }
        }

        close() {
            if (this.popup) {
                this.popup.close();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            if (this.notifyWidgetDeactivated) {
                this.notifyWidgetDeactivated();
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCopyright = MbCopyright;

})();

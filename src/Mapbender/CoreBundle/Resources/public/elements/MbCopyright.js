(function() {

    class MbCopyright extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.content_ = $('.-js-popup-content', this.$element).remove().removeClass('hidden');
            if (this.checkAutoOpen && this.checkAutoOpen()) {
                this.activateByButton();
            }
            this._setup();
        }

        _setup() {
            Mapbender.elementRegistry.markReady(this);
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                modal: true,
                detachOnClose: false,
                closeOnOutsideClick: true,
                content: this.content_,
                width: this.options.popupWidth || 350,
                height: this.options.popupHeight || null,
                cssClass: 'copyright-dialog',
                buttons: []
            };
        }

        activateByButton(callback) {
            super.activateByButton(callback);
            this.popup.open();
            if (this.notifyWidgetActivated) {
                this.notifyWidgetActivated();
            }
        }

        closeByButton() {
            super.closeByButton();
            if (this.notifyWidgetDeactivated) {
                this.notifyWidgetDeactivated();
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCopyright = MbCopyright;

})();

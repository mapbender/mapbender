(function() {

    class MbCopyright extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.content_ = $('.-js-popup-content', this.$element).remove().removeClass('hidden');

            if (this.options.autoOpen) {
                const currentText = this.content_.text().trim();
                const storedText = localStorage.getItem('mbCopyrightText-' + Mapbender.configuration.application.slug);
                this.options.autoOpen = (storedText === null) || (currentText !== storedText);
            }

            if (this.checkAutoOpen(this.element, this.options)) {
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
                modal: this.options.modal,
                draggable: !this.options.modal,
                closeOnOutsideClick: true,
                detachOnClose: false,
                content: this.content_,
                width: this.options.popupWidth || 350,
                height: this.options.popupHeight || null,
                cssClass: 'copyright-dialog',
                buttons: []
            };
        }

        activateByButton(callback, mbButton) {
            super.activateByButton(callback, mbButton);
            this.popup.open();
            if (this.notifyWidgetActivated) {
                this.notifyWidgetActivated();
            }

            if (this.options.dontShowAgain) {
                this.initListeners();
            }
        }

        closeByButton() {
            super.closeByButton();
            if (this.notifyWidgetDeactivated) {
                this.notifyWidgetDeactivated();
            }
        }

        initListeners() {
            const currentText = this.content_.text().trim();
            const localStorageKey = 'mbCopyrightText-' + Mapbender.configuration.application.slug;
            const storedText = localStorage.getItem(localStorageKey);
            const $checkbox = this.content_.find('.copyright-dont-show-again');

            $checkbox.prop('checked', (storedText !== null) && (storedText === currentText));
            $checkbox.off('change.mbCopyright')
                .on('change.mbCopyright', function() {
                    if (this.checked) {
                        localStorage.setItem(localStorageKey, currentText);
                    } else {
                        localStorage.removeItem(localStorageKey);
                    }
                });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCopyright = MbCopyright;

})();

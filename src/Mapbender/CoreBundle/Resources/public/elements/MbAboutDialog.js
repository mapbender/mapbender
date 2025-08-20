(function () {
    class MbAboutDialog extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            this.content_ = $('.-js-popup-content', this.$element).remove().removeClass('hidden');
            this.$element.on('click', () => this.open());
        }

        open() {
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup({
                    title: this.$element.attr('title'),
                    modal: true,
                    draggable: false,
                    closeOnOutsideClick: true,
                    content: this.content_,
                    width: 350,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'btn btn-sm btn-light popupClose'
                        }
                    ]
                });
            } else {
                this.popup.$element.show();
                this.popup.open();
            }
            // request button focus asynchronously to ensure that the popup is fully rendered
            setTimeout(() => {
                this.popup.$element.find('button').focus();
            }, 50);
        }


        close() {
            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbAboutDialog = MbAboutDialog;
})();

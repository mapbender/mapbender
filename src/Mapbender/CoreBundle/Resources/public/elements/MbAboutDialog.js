(function () {
    class MbAboutDialog extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            this.content_ = $('.-js-popup-content', this.$element).remove().removeClass('hidden');
            this.$element.on('click', () => this.activateByButton());
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('title'),
                modal: true,
                draggable: false,
                closeOnOutsideClick: true,
                content: this.content_,
                width: 350,
                buttons: []
            };
        }

        activateByButton() {
            super.activateByButton();
            this.popup.open();
            // request button focus asynchronously to ensure that the popup is fully rendered
            setTimeout(() => {
                this.popup.$element.find('button').focus();
            }, 50);
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbAboutDialog = MbAboutDialog;
})();

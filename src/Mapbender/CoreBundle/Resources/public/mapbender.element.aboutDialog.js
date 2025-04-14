(function ($) {
    'use strict';

    $.widget("mapbender.mbAboutDialog", {
        options: {},
        popup: null,
        content_: null,

        _create: function () {
            var self = this;
            this.content_ = $('.-js-popup-content', this.element).remove().removeClass('hidden');
            this.element.on('click', function () {
                self.open();
            });
        },
        open: function () {
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup({
                    title: this.element.attr('title'),
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
                this.popup.$element.removeClass('hidden')
                this.popup.open();
            }
        },

        close: function () {
            if (this.popup && this.popup.$element) {
                this.popup.$element.addClass('hidden')
            }
        }
    });

})(jQuery);

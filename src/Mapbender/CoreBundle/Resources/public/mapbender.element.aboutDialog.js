(function ($) {
    'use strict';

    $.widget("mapbender.mbAboutDialog", {
        options: {},
        elementUrl: null,
        popup: null,

        _create: function () {
            var self = this,
                $me = $(this.element);

            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + $me.attr('id') + '/';

            $me.on('click', function () {
                self.open();
            });
        },

        defaultAction: function () {
            return this.open();
        },

        open: function () {
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title: this.element.attr('title'),
                    modal: true,
                    draggable: false,
                    closeOnOutsideClick: true,
                    content: [ $.ajax({url: this.elementUrl + 'content'})],
                    width: 350,
                    height: 170,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.accept'),
                            cssClass: 'button popupClose'
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

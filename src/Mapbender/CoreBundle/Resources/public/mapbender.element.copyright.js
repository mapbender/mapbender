(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        popup: null,
        content_: null,

        _create: function() {
            this.content_ = $('.-js-popup-content', this.element).remove().removeClass('hidden');
            if(this.options.autoOpen){
                let key = Mapbender.Model.getLocalStoragePersistenceKey_('hide_copyright_popup_'+this.getContentHash());
                let item = window.localStorage.getItem(key);
                if (!item) {
                    this.open();
                } else {
                    console.log("Don't show copyright popup")
                }
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
                                label: 'OK, ich habe verstanden',
                                cssClass: 'button right popupClose'
                            },
                            {
                                label: 'OK, diese Meldung nicht mehr anzeigen',
                                cssClass: 'button right popupClose',
                                callback: function() {
                                    let key = Mapbender.Model.getLocalStoragePersistenceKey_('hide_copyright_popup_'+widget.getContentHash());
                                    window.localStorage.setItem(key, '1');
                                    this.close();
                                }
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
        },

        getContentHash() {
            const stringHashCode = str => {
                let hash = 0
                for (let i = 0; i < str.length; ++i)
                    hash = Math.imul(31, hash) + str.charCodeAt(i)

                return hash | 0
            }
          return stringHashCode(this.options.content);
        },
        close: function(){
            if (this.popup) {
                this.popup.close();
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


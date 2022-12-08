(function($) {

    $.widget("mapbender.mbCopyright", {
        options: {},
        elementUrl: null,
        popup: null,
        _create: function() {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
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
            var widget = this;
            var options = widget.options;
            var element = widget.element;
            var width = options.popupWidth ? options.popupWidth : 350;
            var height = options.popupHeight ? options.popupHeight : 350;
            this.callback = callback ? callback : null;
            if (!this.popup) {
                $.ajax({url: this.elementUrl + 'content'}).then(function(response) {
                    widget.popup = new Mapbender.Popup2({
                        title: element.attr('data-title'),
                        modal:               true,
                        detachOnClose: false,
                        closeOnOutsideClick: true,
                        content: response,
                        width:               width,
                        height:              height,
                        buttons:             {
                            'ok': {
                                label: 'OK, ich habe verstanden',
                                cssClass: 'button right popupClose'
                            },
                            'ok_2': {
                                label: 'OK, diese Meldung nicht mehr anzeigen',
                                cssClass: 'button right popupClose',
                                callback: function() {
                                    let key = Mapbender.Model.getLocalStoragePersistenceKey_('hide_copyright_popup_'+widget.getContentHash());
                                    window.localStorage.setItem(key, '1');
                                    this.close();
                                }
                            }
                        }
                    });
                    widget.popup.$element.on('close', function() {
                        widget.close();
                    });
                });
            } else {
                this.popup.$element.show();
            }
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
            if(this.popup){
                this.popup.$element.hide();
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


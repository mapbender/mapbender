(function($) {

    $.widget("mapbender.mbSuggestMap", {
        options: {},

        elementUrl: null,

        _create: function() {
        if(!Mapbender.checkTarget("mbSuggestMap", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the wmc handler
         */
        _setup: function() {
            var self = this;
//            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
//            me.click(function() {
//                self._onClick.call(self);
//            });
        },
        
        open: function() {
            var self = this;
//            var me = $(this.element);
//            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function(){
                    self.close();                  
                });
                $("body").mbPopup('showCustom', {
                    title: this.element.attr("title"), 
                    showHeader: true, 
                    content: this.element, 
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: false,
                    overflow:true
                });
//                $('#popupContent').css({
//                    height: "500px"
//                });
//                $('#popup').css({
//                    width: "400px"
//                });
                this.element.show();
//                self._loadList();
            }
        },
        
        close: function() {
            this.element.hide().appendTo($('body'));
            $("body").mbPopup("close");
        },
                
        _call: function(){
            $('div.dialog-weiterempfehlen a#weiterempfehlen-email').bind("click", function(e){
                var mail_cmd = "mailto:?subject=" + that._getTranslatedText("Karte weiterempfehlen-E-Mail-Betreff") + "&body="+encodeURIComponent(url);
                document.location.href = mail_cmd;
                $('#toolbar-karte-weiterempfehlen').removeClass(that.class_active);
                $('#toolbar-karte-weiterempfehlen span.ui-button-icon-primary').removeClass(that.class_tool_active);
                that.dlg_weiterempfehlen.dialog("close");
                try{ that.dlg_weiterempfehlen.dialog("destroy"); }catch(e){}
                that.dlg_weiterempfehlen = null;
                return false;
            });
        }
    });

})(jQuery);

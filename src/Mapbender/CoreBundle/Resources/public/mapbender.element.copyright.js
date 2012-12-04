(function($) {

$.widget("mapbender.mbCopyright", {
    options: {
    },

    elementUrl: null,

    _create: function() {
        var me = $(this.element);
        this.elementUrl = Mapbender.configuration.elementPath + $(this.element).attr('id') + '/';
//        $(document).one('mapbender.setupfinished', $.proxy(this._mapbenderSetupFinished, this));
        $('#' + $(this.element).attr("id") + "-link").click($.proxy(this._showTermsOfUse, this));
    },
//    
//    _mapbenderSetupFinished: function() {
//      this._init(); 
//    },
//    
    
    _showTermsOfUse: function(evt){
        $('#' + $(this.element).attr("id") + "-dialog").dialog({
            autoOpen: true
        });
//        var self = this;
//        var mess = '<div class="copyright-content">'+self.options.termsofuse+'</div>';
//        $(mess).dialog({
//            autoOpen: true,
//            title: self.options.dialog_title,
//            zIndex: 20000,
//            width: 550
//        });
    },

    _destroy: $.noop
});

})(jQuery);


(function($) {

$.widget("mapbender.mbCopyright", {
    options: {
    },

    _create: function() {
        if(this.options.anchor === "left-top"){
            $(this.element).css({
                left: this.options.position[0],
                top: this.options.position[1]
            });
        } else if(this.options.anchor === "right-top"){
            $(this.element).css({
                right: this.options.position[0],
                top: this.options.position[1]
            });
        } else if(this.options.anchor === "left-bottom"){
            $(this.element).css({
                left: this.options.position[0],
                bottom: this.options.position[1]
            });
        } else if(this.options.anchor === "right-bottom"){
            $(this.element).css({
                right: this.options.position[0],
                bottom: this.options.position[1]
            });
        }
        $(this.element).css({width: this.options.width});
        $('#' + $(this.element).attr("id")).find('span.mb-element-copyright-link').click($.proxy(this._showTermsOfUse, this));
    },

    _showTermsOfUse: function(evt){
        $('#' + $(this.element).attr("id") + "-dialog").dialog({
            autoOpen: true
        });
    },

    _destroy: $.noop
});

})(jQuery);


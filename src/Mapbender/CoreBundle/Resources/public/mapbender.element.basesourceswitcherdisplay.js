(function($) {
    $.widget("mapbender.mbBaseSourceSwitcherDisplay", {
        options: {},
        targetEl: null,
        _create: function() {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcherDisplay", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            var self = this;
            this.targetEl = $('#' + this.options.target).data('mapbenderMbBaseSourceSwitcher');
            var cprContent=this.targetEl.getCpr();  //Set Start cpr ->
            var me = $(this.element);
            var element = me.find('a');
            element.html("©" + cprContent.name);
            element.attr("href",cprContent.url);    // <-
            
            $(document).bind('mbbasesourceswitchergroupactivate', $.proxy(self._display, self));
            $(document).bind('mbbasesourceswitcherready', $.proxy(self._displayDefault, self));
        },
        _display: function(e, options) {
            var cpr = this.element.find("a");
            if (cpr === undefined || cpr === null) {
                this.element.html("©" + options.name[options.position]);
            }
            else {
                cpr.html("©" + options.name[options.position]);
                cpr.attr("href", options.href[options.position]);
            }
        },
        _destroy: $.noop
    });

})(jQuery);


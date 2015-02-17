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
            this._setup();
        },
        _setup: function() {
            var self = this;
            this.targetEl = $('#' + this.options.target).data('mbBaseSourceSwitcher');
//            console.log(this.targetEl.getCpr()) ;
            $(document).bind('mbbasesourceswitchergroupactivate', $.proxy(self._display, self));
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


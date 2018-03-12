/*jslint browser: true, nomen: true*/
/*globals initDropdown, Mapbender, OpenLayers, Proj4js, _, jQuery*/

(function ($) {
    'use strict';
    $.widget("mapbender.mbUtfGridInfo", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            debug: false
        },
        readyCallbacks: [],
        _create: function () {
            var self = this;
            if (!Mapbender.checkTarget("mbUtfGridInfo", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            this.model = this.mbMap.getModel();
            console.log(this.model.sourceTree, this.mbMap);
            _.filter(this.model.sourceTree[1], function(o) {
                return !!o.gridlayer;
            });
            this._trigger('ready');
            this._ready();
        }
    });
})(jQuery);

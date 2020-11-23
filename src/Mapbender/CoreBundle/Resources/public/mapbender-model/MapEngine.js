window.Mapbender = Mapbender || {};
window.Mapbender.MapEngine = (function() {
    function MapEngine(code) {
        this.code = code;
        this.globalsPatched_ = false;
    }

    MapEngine.prototype = {
        constructor: MapEngine,
        globalsPatched_: false,
        code: null,
        patchGlobals: function(mapOptions) {
            if (this.globalsPatched_) {
                console.warn("Globals already patched");
            }
            this.globalsPatched_ = true;
        },
        getWmsBaseUrl: function(nativeLayer, srsName, removeProxy) {
            var removeProxy_ = removeProxy || (typeof removeProxy === 'undefined');
            var url = this.getWmsBaseUrlInternal_(nativeLayer, srsName);
            var removeParams = [
                '_olsalt',
                'WIDTH',
                'HEIGHT',
                'BBOX'
            ];
            if (removeProxy_) {
                removeParams.push('_signature');
                url = Mapbender.Util.removeProxy(url);
            }
            return Mapbender.Util.removeUrlParams(url, removeParams, false);
        },
        /**
         * @param {Object|Array<Number>} x
         * @return {OpenLayers.Bounds|(Array<Number> | {left: number, bottom: number, right: number, top: number})}
         */
        toExtent: function(x) {
            if (Array.isArray(x)) {
                return this.boundsFromArray(x);
            }
            if (x && typeof x.left !== 'undefined') {
                return this.boundsFromArray([x.left, x.bottom, x.right, x.top]);
            }
            console.error("Unsupported extent input", x);
            throw new Error("Unsupported extent input");
        }
    };
    MapEngine.typeMap = {};
    MapEngine.factory = function(engineCode) {
        var typeMap = MapEngine.typeMap;
        var constructor = typeMap[engineCode] || typeMap['current'];
        return new (constructor)(engineCode);
    };

    return MapEngine;
}());

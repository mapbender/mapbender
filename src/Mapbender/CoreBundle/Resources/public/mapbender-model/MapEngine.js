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

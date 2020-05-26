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
        mapModelFactory: function(mbMap) {
            switch (this.code) {
                case 'ol2':
                    return new Mapbender.MapModelOl2(mbMap);
                case 'ol4':
                    return new Mapbender.MapModelOl4(mbMap);
                default:
                    throw new Error("Unsupported map engine code " + this.code);
            }
        }
    };
    MapEngine.typeMap = {};
    MapEngine.factory = function(engineCode) {
        var constructor = MapEngine.typeMap[engineCode];
        if (!constructor) {
            throw new Error("Unsupported MapEngine code " + engineCode.toString());
        }
        return new (constructor)(engineCode);
    };

    return MapEngine;
}());

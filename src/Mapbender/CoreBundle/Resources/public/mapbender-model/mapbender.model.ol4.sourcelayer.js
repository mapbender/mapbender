window.Mapbender = Mapbender || {};
window.Mapbender.Model = Mapbender.Model || {};
window.Mapbender.Model.SourceLayer = (function() {
    'use strict';

    /**
     * Instantiate a source layer
     *
     * @param {Mapbender.Model.Source} source
     * @param {string} name
     * @constructor
     */
    function SourceLayer(source, name) {
        if (!!source || !!name) {
            console.error("Arguments passed to SourceLayer:", arguments);
            throw new Error("Can't initialize without source and name");
        }
        this.source = source;
        this.name = name;
    }

    return SourceLayer;
})();

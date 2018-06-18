Mapbender.Model = {
    map:            null,
    mapElement:     null,
    currentProj:    null,
    /**
     *
     * @param mbMap
     * @returns {boolean}
     */
    init:           function(mbMap) {

        this.map = new ol.CanvasMap({
            view:   new ol.View({
                center: [0, 0],
                zoom:   1
            }),
            layers: [new ol.layer.Tile({
                source: new ol.source.OSM()
            })],
            target: 'Map'
        });

        return true;
    },
    /**
     *
     * @param callback
     */
    onMapClick:     function(callback) {

    },
    /**
     *
     * @param callback
     */
    onFeatureClick: function(callback) {
    },
    /**
     *
     * @param layerId
     * @param styleMap
     */
    setLayerStyle:  function(layerId, styleMap) {
    },
    /**
     *
     * @param styleJson
     */
    createStyle:    function(styleJson) {
    },

    /**
     *
     * @returns {*}
     */

    getActiveLayers: function() {
        return this.activeLayer;
    },

    /**
     *
     * @param layerId
     * @param requestParameterBag
     */
    setRequestParameter: function(layerId, requestParameterBag) {

    },
    /**
     *
     * @param addLayers
     */
    setView:             function(addLayers) {

    },
    /**
     *
     * @returns {*}
     */
    getCurrentProj:      function() {

        /** @type String */
        return this.currentProj;
    },
    /**
     *
     * @returns {null|*}
     */
    getAllSrs:           function() {
        return this.srsDefs;
    },

    /**
     *
     */
    getMapExtent: function() {

    },
    /**
     *
     */
    getMapState:  function() {

    },

    /**
     * Returns the current map scale
     */
    getScale: function() {

    },



    /**
     *
     */
    center: function(options) {

    },

    /**
     *
     */
    highlightOn:      function(features, options) {

    },
    /**
     *
     */
    highlightOff:     function(features) {

    },
    setOpacity:       function(source, opacity) {

    },
    /**
     * Zooms to layer
     * @param {object} options of form { sourceId: XXX, layerId: XXX, inherit: BOOL }
     */
    zoomToLayer:      function(options) {

    },
    getLayerExtents:  function(options) {

    },
    /**
     *
     */
    addSource:        function(addOptions) {

    },
    /**
     *
     */
    removeSource:     function(options) {

    },
    /**
     *
     */
    removeSources:    function(keepSources) {

    },
    /**
     *
     * @param {Object} sourceIdObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @param {Object} options in form of:
     * {layers:{'LAYERNAME': {options:{treeOptions:{selected: bool,info: bool}}}}}
     */
    changeLayerState: function(id, state) {

    },
    /*
     * Changes the map's projection.
     */
    changeProjection: function(srs) {

    },

    /**
     *
     */
    parseURL: function() {

    }

};
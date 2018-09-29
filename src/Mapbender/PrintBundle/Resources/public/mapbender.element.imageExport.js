(function($){

    /**
     * @typedef {{type:string, opacity:number, geometries: Array<Object>}} VectorLayerData~print
     */
    $.widget("mapbender.mbImageExport", {
        options: {},
        map: null,
        popupIsOpen: true,
        _geometryToGeoJson: null,

        _create: function(){
            if(!Mapbender.checkTarget("mbImageExport", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
            var olGeoJson = new OpenLayers.Format.GeoJSON();
            this._geometryToGeoJson = olGeoJson.extract.geometry.bind(olGeoJson);
        },
        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap');

            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 250,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans("mb.print.imageexport.popup.btn.cancel"),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans("mb.print.imageexport.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function(){
                                self._exportImage();
                                self.close();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            }else{
                if(this.popupIsOpen === false){
                    this.popup.open(self.element);
                }
            }
            me.show();
            this.popupIsOpen = true;
        },
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this.popupIsOpen = false;
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }
        },
        /**
         *
         */
        _ready: function(){
            this.readyState = true;
        },
        /**
         *
         * @param sourceDef
         * @returns {{layers: *, styles: *}}
         * @private
         */
        _getRasterVisibilityInfo: function(sourceDef) {
            var layer = this.map.map.layersList[sourceDef.mqlid].olLayer;
            return {
                layers: layer.params.LAYERS,
                styles: layer.params.STYLES
            };
        },
        /**
         * @returns {Array<Object>} sourceTreeish configuration objects
         * @private
         */
        _getRasterSourceDefs: function() {
            var sourceTree = this.map.getSourceTree();
            return sourceTree.filter(function(sourceDef) {
                var layer = this.map.map.layersList[sourceDef.mqlid].olLayer;
                if (0 !== layer.CLASS_NAME.indexOf('OpenLayers.Layer.')) {
                    return false;
                }
                if (typeof (Mapbender.source[sourceDef.type] || {}).getPrintConfig !== 'function') {
                    return false;
                }
                return true;
            }.bind(this));
        },
        _collectRasterLayerData: function() {
            var sources = this._getRasterSourceDefs();
            var dataOut = [];
            for(var i = 0; i < sources.length; i++) {
                var sourceDef = sources[i];
                var visLayers = this._getRasterVisibilityInfo(sourceDef);

                if (visLayers.layers.length) {
                    var layer = this.map.map.layersList[sourceDef.mqlid].olLayer;
                    var layerConf = Mapbender.source[sourceDef.type].getPrintConfig(layer, this.map.map.olMap.getExtent(), sourceDef.configuration.options.proxy);
                    layerConf.opacity = sourceDef.configuration.options.opacity;
                    dataOut.push(layerConf);
                }
            }
            return dataOut;
        },
        _collectJobData: function() {
            var mapExtent = this.map.map.olMap.getExtent();
            var imageSize = this.map.map.olMap.getCurrentSize();
            return {
                requests: this._collectRasterLayerData(),
                // @todo: fix unscoped input lookup
                format: $("input[name='imageformat']:checked").val(),
                width: imageSize.w,
                height: imageSize.h,
                centerx: mapExtent.getCenterLonLat().lon,
                centery: mapExtent.getCenterLonLat().lat,
                extentwidth: mapExtent.getWidth(),
                extentheight: mapExtent.getHeight(),
                vectorLayers: this._collectGeometryLayers()
            };
        },
        _exportImage: function() {
            var jobData = this._collectJobData();
            if (!jobData.requests.length) {
                Mapbender.info(Mapbender.trans("mb.print.imageexport.info.noactivelayer"));
            }else{
                this._submitJob(jobData);
            }
        },
        _submitJob: function(jobData) {
            var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/export';

            var form = $('<form method="POST" action="' + url + '"  />');
            var submitValue = JSON.stringify(jobData);
            var $input = $('<input/>').attr('type', 'hidden').attr('name', 'data');
            $input.val(submitValue);
            $input.appendTo(form);
            form.appendTo($('body'));
            form.submit();
            form.remove();
        },
        /**
         * Should return true if the given layer needs to be included in export
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterGeometryLayer: function(layer) {
            if ('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || layer.visibility === false || this.layer === layer) {
                return false;
            }
            if (!(layer.features && layer.features.length)) {
                return false;
            }
            return true;
        },
        /**
         * Should return true if the given feature should be included in export.
         *
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {boolean}
         * @private
         */
        _filterFeature: function(feature) {
            // onScreen throws an error if geometry is not populated, see
            // https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Feature/Vector.js#L198
            if (!feature.geometry || !feature.onScreen(true)) {
                return false;
            }
            return true;
        },
        /**
         * Extracts and preprocesses the geometry from a feature for export backend consumption.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {Object} geojsonish, with (non-conformant) "style" entry bolted on (native Openlayers format!)
         * @private
         */
        _extractFeatureGeometry: function(layer, feature) {
            var geometry = this._geometryToGeoJson(feature.geometry);
            if (feature.style !== null) {
                // stringify => decode: makes a deep copy of the style at the moment of capture
                geometry.style = JSON.parse(JSON.stringify(feature.style));
            } else {
                geometry.style = layer.styleMap.createSymbolizer(feature, feature.renderIntent);
            }
            return geometry;
        },
        /**
         * Should return true if the given feature geometry should be included in export.
         *
         * @param geometry
         * @returns {boolean}
         * @private
         */
        _filterFeatureGeometry: function(geometry) {
            if (geometry.style.fillOpacity > 0 || geometry.style.strokeOpacity > 0) {
                return true;
            }
            if (geometry.style.label !== undefined) {
                return true;
            }
            return false;
        },
        /**
         * Should return export data (sent to backend) for the given geometry layer. Given layer is guaranteed
         * to have passsed through the _filterGeometryLayer check positively.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns VectorLayerData~export
         * @private
         */
        _extractGeometryLayerData: function(layer) {
            var geometries = layer.features
                .filter(this._filterFeature.bind(this))
                .map(this._extractFeatureGeometry.bind(this, layer))
                .filter(this._filterFeatureGeometry.bind(this))
            ;
            return {
                type: 'GeoJSON+Style',
                opacity: 1,
                geometries: geometries
            };
        },
        _collectGeometryLayers: function() {
            // Iterating over all vector layers, not only the ones known to MapQuery
            return this.map.map.olMap.layers
                .filter(this._filterGeometryLayer.bind(this))
                .map(this._extractGeometryLayerData.bind(this))
            ;
        },

        _noDanglingCommaDummy: null
    });

})(jQuery);

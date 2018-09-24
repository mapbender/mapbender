(function($){
    'use strict';
    $.widget('mapbender.mbImageExport', {
        options: {},
        map: null,
        popupIsOpen: true,
        _create: function() {
            if(!Mapbender.checkTarget('mbImageExport', this.options.target)) {
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            this.model = Mapbender.elementRegistry.listWidgets().mapbenderMbMap.model;

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
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.cancel'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.ok'),
                            cssClass: 'button right',
                            callback: function(){
                                self._exportImage();
                                self.close();
                            }
                        }
                    }
                });

                this.popup.$element.on('close', $.proxy(this.close, this));
            } else {
                if(this.popupIsOpen === false){
                    this.popup.open(self.element);
                }
            }
            me.show();
            this.popupIsOpen = true;
        },
        close: function() {
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this.popupIsOpen = false;
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }

            if ( this.callback ) {
                this.callback.call();
            } else {
                this.callback = null;
            }
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true){
                callback();
            }
        },
        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        },
        _exportImage: function() {
            var format = $("input[name='imageformat']:checked").val();
            var mapSize = this.model.getMapSize();
            var mapExtent = this.model.getMapExtent();
            var mapCenter = this.model.getMapCenter();

            var activeSources = this.model.getActiveSources();
            var printConfigs = [];
            for (var i = 0; i < activeSources.length; i++) {
                var source = activeSources[i];
                if (!source.isVisible()) {
                    continue;
                }
                var printConfig = this.model.getSourcePrintConfig(source, mapExtent, mapSize);
                if (printConfig) {
                    printConfigs.push(printConfig);
                }
            }

            var printStyleOptions = this.model.getVectorLayerPrintStyleOptions();

            var vectorLayers = [];
            var geojsonFormat = this.model.createOlFormatGeoJSON();
            var allFeatures = this.model.getVectorLayerFeatures();
            for (var owner in allFeatures) {
                for (var uuid in allFeatures[owner]) {
                    var features = allFeatures[owner][uuid];
                    if (!features) {
                        continue;
                    }
                    var geometries = [];
                    for (var idx = 0; idx < features.length; idx++) {
                        var geometry = geojsonFormat.writeGeometryObject( features[ idx ].getGeometry(), geojsonFormat );
                        if (geometry) {
                            var styleOptions = {};
                            if (printStyleOptions.hasOwnProperty(owner) && printStyleOptions[owner].hasOwnProperty(uuid)) {
                                styleOptions = printStyleOptions[owner][uuid];
                            }

                            geometry.style = styleOptions;
                            geometries.push(geometry);
                        }
                    }

                    var layerOpacity = 1;
                    if (this.model.vectorLayer.hasOwnProperty(owner)
                        && this.model.vectorLayer[owner].hasOwnProperty(uuid )
                    ) {
                        layerOpacity = this.model.vectorLayer[owner][uuid].getOpacity()
                    }

                    var objectForVectorLayers = {
                        "type": "GeoJSON+Style",
                        "opacity": layerOpacity,
                        "geometries": geometries
                    };

                    vectorLayers.push(JSON.stringify(objectForVectorLayers));
                }
            }

            var data = {
                requests: printConfigs,
                format: format,
                width: mapSize[0],
                height: mapSize[1],
                centerx: mapCenter[0],
                centery: mapCenter[1],
                extentwidth: this.model.getWidthOfExtent(mapExtent),
                extentheight: this.model.getHeigthOfExtent(mapExtent),
                vectorLayers: vectorLayers
            };

            var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/export';

            var form = $('<form method="POST" action="' + url + '"/>');
            $('<input/>').attr('type', 'hidden').attr('name', 'data').val(JSON.stringify(data)).appendTo(form);
            form.appendTo($('body'));
            form.submit();
            form.remove();
        }
    });

})(jQuery);

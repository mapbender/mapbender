(function($) {

    $.widget('mapbender.mbDataUpload', {
        options: {
            maxFileSize: 10,
        },
        map: null,
        popup: null,
        dropArea: null,

        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget('mbDataUpload');
            });
        },

        _setup: function(mbMap) {
            var self = this;
            this.map = mbMap.map.olMap;
            this.dropArea = this.element.find('.dropFileArea')[0];
            this.setupProjSelection();
            this.setupDropArea();
            this.setupFileUploadForm();
            $(document).on('mbmapsrschanged', $.proxy(self._onSrsChanged, self));
        },

        open: function(callback) {
            this.callback = callback ? callback : null;
            this.openPopup();
        },

        close: function() {
            if (this.callback) {
                this.callback.call();
                this.callback = null;
            }
            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
                this.popup = null;
            }
            this.removeLayers();
            this.removeTable();
        },

        openPopup: function () {
            var self = this;
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup({
                    title: this.element.attr('data-title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: false,
                    detachOnClose: false,
                    content: this.element,
                    resizable: true,
                    cssClass: 'datauploadDialog',
                    width: 500,
                    height: 500,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'btn btn-danger popupClose'
                        }
                    ]
                });
                this.popup.$element.on('close', function () {
                    self.close();
                });
                this.popup.$element.find('.fileUploadLink').focus();
            }
        },

        setupProjSelection: function () {
            var projections = Mapbender.Model.mbMap.getAllSrs();
            projections.forEach((proj) => {
                this.element.find('.projSelection').append($('<option/>', {
                    value: proj.name,
                    text: proj.title
                }));
            });
        },

        setupDropArea: function () {
            var self = this;
            var events = ['dragenter', 'dragover', 'dragleave', 'drop'];
            events.forEach(function (eventName) {
                self.dropArea.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            ['dragenter', 'dragover'].forEach(function (event) {
                self.dropArea.addEventListener(event, self.highlight.bind(self), false);
            });
            ['dragleave', 'drop'].forEach(function (event) {
                self.dropArea.addEventListener(event, self.unhighlight.bind(self), false);
            });
            self.dropArea.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                var files = dt.files;
                self.handleFileUpload(files);
            });
        },

        setupFileUploadForm: function () {
            $('.mb-element-dataupload form').on('submit', function (e) {
                e.preventDefault();
            });
            this.element.find('.fileUploadLink').on('click', (e)=> {
                e.preventDefault();
                this.element.find('.fileUploadField').trigger('click');
            });

            const self = this;
            this.element.find('.fileUploadField').on('change', function () {
                self.handleFileUpload(this.files);
            });
        },

        highlight: function () {
            this.dropArea.classList.remove('unhighlight');
            this.dropArea.classList.add('highlight');
        },

        unhighlight: function () {
            this.dropArea.classList.remove('highlight');
            this.dropArea.classList.add('unhighlight');
        },

        handleFileUpload: function (files) {
            var self = this;
            let extent = undefined;

            ([...files]).forEach(function (file, idx) {
                var reader = new FileReader();
                reader.addEventListener('load', function () {
                    var maxFileSizeBytes = parseInt(self.options.maxFileSize) * 1000000;
                    if (file.size > maxFileSizeBytes) {
                        var msg = Mapbender.trans('mb.core.dataupload.error.filesize') + ' (' + self.options.maxFileSize + 'MB)';
                        Mapbender.error(msg);
                        return;
                    }
                    var uploadId = Date.now() + '_' + idx;

                    try {
                        // zoom to the bounding box of all recently uploaded files
                        const createdSource = self.renderFeatures(file, uploadId, reader.result);
                        if (extent) {
                            ol.extent.extend(extent, createdSource.getExtent());
                        } else {
                            extent = createdSource.getExtent();
                        }
                        self.map.getView().fit(extent, {
                            padding: [75, 75, 75, 75],
                        });

                        self.renderTable(file, uploadId);
                    } catch (error) {
                        Mapbender.error(error.message);
                    }
                });
                reader.readAsText(file);
            });
        },

        renderFeatures: function (file, uploadId, result) {
            var featureProjection = this.map.getView().getProjection().getCode();

            const [format, dataProjection] = this.findFormatByType(file, result);
            if (!format || !dataProjection) {
                var msg = Mapbender.trans('mb.core.dataupload.error.filetype') + ' ' + file.type;
                Mapbender.error(msg);
                throw new Error(msg);
            }

            var features = format.readFeatures(result, {
                dataProjection: dataProjection,
                featureProjection: featureProjection,
            });
            var source = new ol.source.Vector();
            source.addFeatures(features);
            var layer = new ol.layer.Vector({
                source: source,
            });
            layer.id = uploadId;
            this.map.addLayer(layer);
            return source;
        },

        /**
         * @param {File} file
         * @param {string | ArrayBuffer | null} result FileReader result
         * @returns {[ol.format.*,string]} An array containing the openlayers format and the appropriate projection
         */
        findFormatByType: function(file, result) {
            // best case: mime type is transmitted, but only works in some browsers/OS
            switch (file.type) {
                case 'application/geo+json':
                case 'application/json':
                    return [new ol.format.GeoJSON(), this.findGeoJsonProjection(result)];
                case 'application/vnd.google-earth.kml+xml':
                    return [new ol.format.KML(), 'EPSG:4326'];
                case 'application/gml+xml':
                    return [Mapbender.FileUtil.findGmlFormat(result), this.findProjection()];
                case 'application/gpx+xml':
                    return [new ol.format.GPX(), 'EPSG:4326'];
            }

            // fallback: use FileUtil for file extension based detection
            var formatInfo = Mapbender.FileUtil.getFormatParserByFilename(file.name);
            if (formatInfo) {
                var projection;
                var parser = formatInfo.parser;
                
                if (parser instanceof ol.format.GeoJSON) {
                    projection = this.findGeoJsonProjection(result);
                } else if (parser instanceof ol.format.KML || parser instanceof ol.format.GPX) {
                    projection = 'EPSG:4326';
                } else if (parser instanceof ol.format.GML || parser instanceof ol.format.GML2 || 
                           parser instanceof ol.format.GML3 || parser instanceof ol.format.GML32) {
                    parser = Mapbender.FileUtil.findGmlFormat(result);  // GML requires content inspection
                    projection = this.findProjection();
                } else {
                    projection = this.findProjection();
                }
                
                return [parser, projection];
            }

            return [null, null];
        },

        findProjection: function () {
            var proj = this.element.find('.projSelection').val();
            if (proj !== '') {
                return proj;
            }
            return this.map.getView().getProjection().getCode();
        },

        findGeoJsonProjection: function (geoJson) {
            // proj from selectbox overrides proj specification in geoJson
            var proj = this.element.find('.projSelection').val();
            if (proj !== '') {
                return proj;
            }
            geoJson = JSON.parse(geoJson);
            if (geoJson.hasOwnProperty('crs')) {
                proj = geoJson.crs.properties.name;
                var urnParts = proj.split(':').filter(function (part) {
                    return (part !== '');
                });
                var length = urnParts.length;
                if (urnParts[length - 1] === 'CRS84') {
                    return 'EPSG:4326';
                } else if (urnParts[length - 2] === 'EPSG') {
                    return 'EPSG:' + urnParts[length - 1];
                } else {
                    var msg = Mapbender.trans('mb.core.dataupload.error.projection') + ': ' + proj;
                    Mapbender.error(msg);
                    throw new Error(msg);
                }
            }
            return 'EPSG:4326';
        },

        renderTable: function (file, uploadId) {
            var self = this;
            var table = this.element.find('.filesTable');
            var tr = $('<tr>', {
                id: uploadId
            });
            var div = $('<div>', {
                'class': 'overflow',
                text: file.name
            });
            var td1 = $('<td>', {
                title: file.name
            });
            td1.append(div);
            tr.append(td1);
            var td2 = $('<td>', {
                'class': 'text-nowrap text-end',
            });
            var iconActivate = $('<i>', {
                'class': 'fas fa-eye ms-2',
                'tabindex': 0,
                click: function (e) {
                    self.toggleActivation(e);
                }
            });
            var iconZoom = $('<i>', {
                'class': 'fas fa-magnifying-glass-plus ms-2',
                'tabindex': 0,
                click: function (e) {
                    self.zoom(e);
                }
            });
            var iconDelete = $('<i>', {
                'class': 'fa far fa-trash-can ms-2',
                'tabindex': 0,
                click: function (e) {
                    self.delete(e);
                }
            });
            td2.append(iconActivate);
            td2.append(iconZoom);
            td2.append(iconDelete);
            tr.append(td2);
            table.append(tr);
            $('.table-responsive').removeClass('d-none');
        },

        toggleActivation: function (e) {
            var iconActivate = $(e.target);
            var activate = iconActivate.hasClass('fa-eye-slash');
            iconActivate.toggleClass(function () {
                if (activate) {
                    iconActivate.removeClass('fa-eye-slash');
                    return 'fa-eye';
                }
                iconActivate.removeClass('fa-eye');
                return 'fa-eye-slash';
            });
            var tr = iconActivate.parent().parent();
            var id = tr.attr('id');
            var layer = this.getLayerById(id);
            if (layer) {
                layer.setVisible(activate);
            }
        },

        zoom: function (e) {
            var iconZoom = $(e.target);
            var tr = iconZoom.parent().parent();
            var id = tr.attr('id');
            var layer = this.getLayerById(id);
            if (layer) {
                var extent = layer.getSource().getExtent();
                this.map.getView().fit(extent, {
                    padding: [75, 75, 75, 75],
                });
            }
        },

        delete: function (e) {
            var iconDelete = $(e.target);
            var tr = iconDelete.parent().parent();
            var id = tr.attr('id');
            var layer = this.getLayerById(id);
            if (layer) {
                this.map.removeLayer(layer);
                this.element.find('.filesTable').find(tr).remove();
                if (this.element.find('.filesTable').find('tr').length < 2) {
                    this.removeTable();
                }
            }
        },

        getLayerById: function (id) {
            var layer = this.map.getLayers().getArray().filter(function (layer) {
                return (layer.hasOwnProperty('id') && layer.id === id);
            });
            if (layer.length === 1) {
                return layer[0];
            }
            return false;
        },

        getLayers: function () {
            return this.map.getLayers().getArray().filter(function (layer) {
                return layer.hasOwnProperty('id');
            });
        },

        removeLayers: function () {
            var self = this;
            var layers = this.getLayers();
            if (layers.length > 0) {
                layers.forEach(function (layer) {
                    self.map.removeLayer(layer);
                });
            }
        },

        removeTable: function () {
            this.element.find('.filesTable').find('tbody').find('tr').remove();
            $('.table-responsive').addClass('d-none');
        },

        _onSrsChanged: function (event, data) {
            var layers = this.getLayers();
            if (layers.length > 0) {
                layers.forEach(function (layer) {
                    layer.getSource().getFeatures().forEach(function (f) {
                        var geometry = f.getGeometry();
                        if (geometry) {
                            geometry.transform(data.from, data.to);
                        }
                    });
                });
            }
        }
    });
})(jQuery);

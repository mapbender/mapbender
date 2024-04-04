(function($) {

    $.widget('mapbender.mbDataUpload', {
        options: {
            deactivate_on_close: true,
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
            this.map = mbMap.map.olMap;
            this.dropArea = document.getElementById('dropFileArea');
            this.setupProjSelection();
            this.setupDropArea();
            this.setupFileUploadForm();
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
                this.popup = new Mapbender.Popup2({
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
            }
        },

        setupProjSelection: function () {
            var projections = Mapbender.Model.mbMap.getAllSrs();
            projections.forEach(function (proj) {
                $('#projSelection').append($('<option/>', {
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
            var self = this;
            $('.mb-element-dataupload form').on('submit', function (e) {
                e.preventDefault();
            });
            $('#fileUploadLink').on('click', function (e) {
                e.preventDefault();
                $('#fileUploadField').trigger('click');
            });
            $('#fileUploadField').on('change', function () {
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
            ([...files]).forEach(function (file, idx) {
                var reader = new FileReader();
                reader.addEventListener('load', function () {
                    if (file.size > 10000000) {
                        var msg = Mapbender.trans('mb.core.dataupload.error.filesize');
                        Mapbender.error(msg);
                        return;
                    }
                    var uploadId = Date.now() + '_' + idx;
                    self.renderFeatures(file, uploadId, reader.result);
                    self.renderTable(file, uploadId);
                });
                reader.readAsText(file);
            });
        },

        renderFeatures: function (file, uploadId, result) {
            var format = {};
            var featureProjection = this.map.getView().getProjection().getCode();
            var dataProjection = featureProjection;

            switch (file.type) {
                case 'application/geo+json':
                case 'application/json':
                    format = new ol.format.GeoJSON();
                    dataProjection = this.findGeoJsonProjection(result);
                    break;
                case 'application/vnd.google-earth.kml+xml':
                    format = new ol.format.KML();
                    dataProjection = 'EPSG:4326';
                    break;
                case 'application/gml+xml':
                    format = this.findGmlFormat(result);
                    dataProjection = this.findProjection();
                    break;
                case 'application/gpx+xml':
                    format = new ol.format.GPX();
                    dataProjection = 'EPSG:4326';
                    break;
                default:
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
            var extent = source.getExtent();
            this.map.getView().fit(extent, {
                padding: [75, 75, 75, 75],
            });
        },

        findProjection: function () {
            var proj = $('#projSelection').val();
            if (proj !== '') {
                return proj;
            }
            return this.map.getView().getProjection().getCode();
        },

        findGeoJsonProjection: function (geoJson) {
            // proj from selectbox overrides proj specification in geoJson
            var proj = $('#projSelection').val();
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

        findGmlFormat: function (gml) {
            var gmlFormats = {
                gml: new ol.format.GML(),
                gml2: new ol.format.GML2(),
                gml3: new ol.format.GML3(),
                gml32: new ol.format.GML32()
            };

            for (var format in gmlFormats) {
                format = gmlFormats[format];
                var features = format.readFeatures(gml);
                if (features.length > 0) {
                    var coordinates = features[0].getGeometry().getCoordinates();
                    if (coordinates.length > 0) {
                        return format;
                    }
                }
            }

            var msg = Mapbender.trans('mb.core.dataupload.error.gml');
            Mapbender.error(msg);
            throw new Error(msg);
        },

        renderTable: function (file, uploadId) {
            var self = this;
            var table = $('#filesTable');
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
                click: function (e) {
                    self.toggleActivation(e);
                }
            });
            var iconZoom = $('<i>', {
                'class': 'fas fa-magnifying-glass-plus ms-2',
                click: function (e) {
                    self.zoom(e);
                }
            });
            var iconDelete = $('<i>', {
                'class': 'fa far fa-trash-can ms-2',
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
                $('#filesTable').find(tr).remove();
                if ($('#filesTable tr').length < 2) {
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

        removeLayers: function () {
            var self = this;
            var layers = this.map.getLayers().getArray().filter(function (layer) {
                return layer.hasOwnProperty('id');
            });
            if (layers.length > 0) {
                layers.forEach(function (layer) {
                    self.map.removeLayer(layer);
                });
            }
        },

        removeTable: function () {
            $('#filesTable tbody').find('tr').remove();
            $('.table-responsive').addClass('d-none');
        }
    });
})(jQuery);

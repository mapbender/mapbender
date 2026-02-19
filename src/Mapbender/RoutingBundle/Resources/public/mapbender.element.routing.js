(function($) {
    'use strict';
    /**
     * @namespace mapbender.mbRouting
     */
    $.widget('mapbender.mbRouting', {
        map: null,
        olMap: null,
        popup: null,
        routingLayer: null,
        markerLayer: null,
        elementUrl: null,
        isActive: false,
        exportFormatOptions: [],
        styleLinearDistance: {
            pointRadius: 0,
            strokeLinecap: 'square',
            strokeDashstyle: 'dash'
        },

        _create: function() {
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, () => {
                Mapbender.checkTarget('mbRoutingElement');
            });
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            const searchDriver = this.options.searchConfig.driver;
            this.searchConfig = this.options.searchConfig[searchDriver];
            this.exportFormatOptions = [{
                id: 'geojson',
                name: 'GeoJSON',
            }, {
                id: 'gpx',
                name: 'GPX',
            }, {
                id: 'kml',
                name: 'KML',
            }];
        },

        _setup: function(mbMap) {
            this.map = mbMap;
            this.olMap = mbMap.map.olMap;

            if (this.options.autoSubmit) {
                this._autoSubmit();
            }

            this._setupExportFormatSelection();
            this._initializeEventListeners();
            this._trigger('ready');
        },

        open: function(callback) {
            this.callback = callback ? callback : null;
            this.isActive = true;

            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup({
                    title: Mapbender.trans('mb.routing.backend.title'),
                    draggable: true,
                    resizable: true,
                    header: true,
                    modal: false,
                    closeOnESC: false,
                    content: this.element,
                    width: 350,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'btn btn-sm btn-light popupClose'
                        }
                    ]
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
                $(this.element).show();
            }
        },

        close: function() {
            if (this.popup) {
                this.element.hide().appendTo($('body'));
                if (this.popup.$element) {
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this._clearRoute();
            this.isActive = false;
            this.callback ? this.callback.call() : this.callback;
        },

        reveal: function() {
            this.isActive = true;
        },

        hide: function() {
            this.isActive = false;
        },

        _initializeEventListeners: function() {
            this.olMap.on('singleclick', $.proxy(this._mapClickHandler, this));

            // flush points when srs is changed
            $(document).on('mbmapsrschanged', $.proxy(this._emptyPoints, this));

            // add point on click
            $('.addPoint', this.element).click(() => {
                this._addInputField();
            });

            // remove input on click
            $('.mb-routing-location-points', this.element).on('click', '.clearField', (e) => {
                this._removeInputWrapper(e.target);
            });

            // reset route and input on click
            $('.resetRoute', this.element).click(() => {
                this._clearRoute();
            });

            // swap points on click
            $('.swapPoints', this.element).click(() => {
                this._flipPoints();
            });

            // calculate route button click
            $('.calculateRoute', this.element).click(() => {
                this._getRoute();
            });

            $('.mb-routing-location-points', this.element).on('focus', 'input[type="text"]', (e) => {
                this.focusedInputField = e.target;
                $('.mb-element-map').css('cursor', 'crosshair');
            });

            if (this.options.useSearch) {
                $('.mb-routing-location-points', this.element).on('focus', 'input[type="text"]', (e) => {
                    $(e.target).autocomplete({
                        classes: {
                            'ui-autocomplete': 'mb-routing-autocomplete',
                        },
                        minLength: 3,
                        source: this._handleAutocompleteSource.bind(this),
                        focus: (event, ui) => {
                            $(this).val(ui.item.label);
                            return false;
                        },
                        select: this._handleAutocompleteSelect.bind(this)
                    }).autocomplete('instance')._renderItem = (ul, item) => {
                        return $('<li>')
                            .append('<a>' + item.label + '</a>')
                            .appendTo(ul);
                    };
                });
            }

            $('.mb-routing-location-points', this.element).sortable({
                axis: 'y',
                start: (e, ui) => {
                    $(e.target).attr('data-previndex', ui.item.index());
                },
                update: (e) => {
                    $(e.target).removeAttr('data-previndex');
                    this._reorderInputFields();
                }
            }).disableSelection();

            $('.select-export-format', this.element).on('change', () => {
                const exportFormat = $('.select-export-format', this.element).val();

                let exportData = '';
                let format = null;

                switch (exportFormat) {
                    case 'geojson':
                        format = new ol.format.GeoJSON();
                        break;
                    case 'gpx':
                        format = new ol.format.GPX();
                        break;
                    case 'kml':
                        format = new ol.format.KML();
                        break;
                    default:
                        return;
                }

                const features = this.routingLayer.getSource().getFeatures();
                exportData = format.writeFeatures(features, {
                    dataProjection: 'EPSG:4326',
                    featureProjection: Mapbender.Model.getCurrentProjectionCode(),
                });
                const timestamp = new Date().toISOString().replace('T', '_').slice(0, 16);
                this._download(new Blob([exportData]), "route-" + timestamp + "." + exportFormat);
            });
        },

        _setupExportFormatSelection: function() {
            for (let i = 0; i < this.exportFormatOptions.length; i++) {
                const option = this.exportFormatOptions[i];
                this.element.find('.select-export-format').append($('<option/>', {
                    value: option.id,
                    text: option.name
                }));
            }
            this.element.find('.select-export-format').hide();
        },

        _showSelectExportFormat: function() {
            if (!this.options.allowExport) {
                return;
            }
            this.element.find('.select-export-format').show();
        },

        _download: function(blob, filename) {
            const a = document.createElement('a');
            a.href = window.URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        _autoSubmit: function() {
            $('.mb-routing-location-points, input[type="radio"]', this.element).change(() => {
                if (this._isInputValid()) {
                    this._getRoute();
                }
            });
        },

        _isInputValid: function() {
            let isValid = true;
            $.each(this.element.find('.mb-routing-location-points input'), (index, element) => {
                isValid = isValid && ($(element).val() !== '');
            });
            return isValid;
        },

        _mapClickHandler: function(event) {
            if (this.isActive && this.focusedInputField) {
                let coordinates = event.coordinate;
                let formattedCoordinates = this._formatCoordinates(coordinates);
                $(this.focusedInputField).val(formattedCoordinates);
                const regex = new RegExp(/^(\-?\d+(\.\d+)?)(?:,|;|\s)+(\-?\d+(\.\d+)?)$/);

                if (regex.test(coordinates.toString())) {
                    this._addPointWithMarker(this.focusedInputField, coordinates);
                    const source = this.markerLayer.getSource();
                    const extent = source.getExtent();

                    if (source.getFeatures().length > 1) {
                        this.olMap.getView().fit(extent, {
                            padding: new Array(4).fill(this.options.buffer)
                        });
                    }
                } else {
                    Mapbender.error(Mapbender.trans('mb.routing.exception.main.invalidCoordinates'));
                }
            }
        },

        _formatCoordinates: function(coordinates) {
            coordinates = [...coordinates];
            // do not format coordinates for WGS 84 / EPSG:4326
            if (this.olMap.getView().getProjection().getCode() !== 'EPSG:4326') {
                coordinates[0] = coordinates[0].toFixed(2);
                coordinates[1] = coordinates[1].toFixed(2);
            }
            return coordinates[0] + ', ' + coordinates[1];
        },

        _emptyPoints: function() {
            const $inputs = this._findLocationInputFields();
            $.each($inputs, (idx, input) => {
                this._removeMarker(input);
                $(input).val('').data('coords', null);
            });
        },

        _addInputField: function() {
            const htmlIntermediatePoint = $(this.element.find('.tplIntermediatePoint').html());
            const lastInputElement = $('.mb-routing-location-points div:last-child', this.element);
            htmlIntermediatePoint.insertBefore(lastInputElement);
            htmlIntermediatePoint.find('input').focus();
        },

        _removeInputWrapper: function(btn) {
            const inputGroup = $(btn).parent();
            let inputField = $('input', inputGroup);
            inputField.val('').data('coords', null);
            this._removeMarker(inputField);
            if (this._findLocationInputFields().length > 2) {
                inputGroup.remove();
                this._reorderInputFields();
            } else {
                this.routingLayer.getSource().clear();
            }
        },

        _clearRoute: function() {
            if (this.markerLayer !== null) {
                this.markerLayer.getSource().clear();
            }
            if (this.routingLayer !== null) {
                this.routingLayer.getSource().clear();
            }
            this._emptyPoints();
            $('.mb-routing-location-points > .intermediatePoints', this.element).remove();
            $('.attribution', this.element).addClass('d-none');
            $('.mb-routing-info', this.element).addClass('d-none').html('');
            $('.mb-routing-instructions', this.element).html('');
            $('.select-export-format').hide();
            $('.mb-element-map').css('cursor', 'auto');
            return true;
        },

        _flipPoints: function() {
            const form = $('.mb-routing-location-points', this.element);
            const inputFields = form.children('div');
            form.append(inputFields.get().reverse());
            this._reorderInputFields();
        },

        _getRoute: function() {
            const requestProj = 'EPSG:4326';
            const mapProj = this.olMap.getView().getProjection().getCode();
            let points = this._getRoutingPoints();

            if (!points) {
                console.warn(Mapbender.trans('mb.routing.exception.main.noValidPoints'));
                return false;
            }

            if (requestProj !== mapProj) {
                points = points.map((point) => {
                    return this._transformCoordinates(point, mapProj, requestProj);
                });
            }

            this.setSpinnerVisible(true);
            $.ajax({
                type: 'POST',
                url: this.elementUrl + 'getRoute',
                data: {
                    'vehicle': $('input[name=vehicle]:checked', this.element).val(),
                    'points': points,
                    'srs': mapProj
                }
            }).fail((e) => {
                Mapbender.handleAjaxError(e, () => this._getRoute(), Mapbender.trans('mb.routing.exception.main.serviceUnavailable'));
                this.setSpinnerVisible(false);
            }).done((response) => {
                if (response.error) {
                    Mapbender.error(response.error.message);
                } else {
                    this._renderRoute(response.featureCollection);
                    this._showAttribution();
                    this._showRouteInfo(response.routeInfo);
                    this._showRouteInstructions(response.routingInstructions);
                    this._showSelectExportFormat();
                }
                this.setSpinnerVisible(false);
            });
        },

        _handleAutocompleteSource: function(request, _response) {
            const self = this;
            return $.ajax({
                type: 'GET',
                url: this.elementUrl + 'search',
                data: {
                    terms: encodeURI(request.term),
                    srsId: this.olMap.getView().getProjection().getCode()
                }
            }).then(function(response) {
                if (response.error) {
                    Mapbender.error(Mapbender.trans('mb.routing.exception.main.general'));
                } else {
                    _response($.map(response, function(value) {
                        return {
                            label: self._formatLabel(value),
                            geom: value[self.searchConfig.geom_attribute],
                            srid: (self.searchConfig.geom_proj) ? self.searchConfig.geom_proj : self.olMap.getView().getProjection().getCode()
                        };
                    }));
                }
            }).fail((e) => Mapbender.handleAjaxError(e, () => this._handleAutocompleteSource(request, _response)));
        },

        _extractAttribute: function(obj, path) {
            var props = obj;
            var parts = path.split('.');
            var last = parts.pop();
            for (var i = 0; i < parts.length; ++i) {
                props = props && props[parts[i]];
                if (!props) {
                    break;
                }
            }
            if (props && (props[last] || (typeof props[last] === 'number'))) {
                return [props[last]].join('');  // force to string
            } else {
                return null;
            }
        },

        _formatLabel: function(doc) {
            // Find / match '${attribute_name}' / '${nested.attribute.path}' placeholders
            const label_attribute = this.searchConfig.label_attribute;
            var templateParts = label_attribute.split(/\${([^}]+)}/g);
            if (templateParts.length > 1) {
                var parts = [];
                for (var i = 0; i < templateParts.length; i += 2) {
                    var fixedText = templateParts[i];
                    // NOTE: attributePath is undefined (index >= length of list) if label_attribute defines static text after last placeholder
                    var attributePath = templateParts[i + 1];
                    var attributeValue = attributePath && this._extractAttribute(doc, attributePath);
                    if (attributeValue) {
                        parts.push(fixedText);
                        parts.push(attributeValue);
                    } else {
                        // Show text before label component only if attribute data was non-empty
                        if (!attributePath) {
                            parts.push(fixedText);
                        }
                    }
                }
                return parts.join('').replace(/(^[\s.,:]+)|([\s.,:]+$)/g, '');
            } else {
                return this._extractAttribute(doc, label_attribute);
            }
        },

        _handleAutocompleteSelect: function(e, ui) {
            $(e.target).val(ui.item.label);
            let format;
            switch (this.searchConfig.geom_format) {
                case 'WKT':
                    format = new ol.format.WKT();
                    break;
                case 'GeoJSON':
                    format = new ol.format.GeoJSON();
                    break;
                default:
                    const msg = Mapbender.trans('mb.routing.exception.main.format') + ': ' + this.searchConfig.geom_format;
                    Mapbender.error(msg);
                    throw new Error(msg);
            }
            const feature = format.readFeature(ui.item.geom, {
                dataProjection: ui.item.srid,
                featureProjection: this.olMap.getView().getProjection().getCode(),
            });
            this._createMarker(e.target, feature);
            const featureGeom = feature.getGeometry();
            $(e.target).data('coords', featureGeom.getCoordinates());
            const source = this.markerLayer.getSource();
            let geometryOrExtent = source.getExtent();

            if (source.getFeatures().length === 1) {
                geometryOrExtent = featureGeom;
            }

            this.olMap.getView().fit(geometryOrExtent, {
                padding: new Array(4).fill(this.options.buffer)
            });

            if (this.options.autoSubmit) {
                this._getRoute();
                e.target.blur();
            }

            return false;
        },

        _reorderInputFields: function() {
            let inputFields = $('.mb-routing-location-points > div', this.element);
            inputFields.removeClass('intermediatePoints');
            inputFields.first().find('.fa-location-dot').removeClass('text-success text-danger text-primary').addClass('text-success');
            inputFields.last().find('.fa-location-dot').removeClass('text-success text-danger text-primary').addClass('text-danger');
            $('input', inputFields.first()).attr('placeholder', Mapbender.trans('mb.routing.frontend.dialog.label.start'));
            $('input', inputFields.last()).attr('placeholder', Mapbender.trans('mb.routing.frontend.dialog.label.destination'));
            // update intermediate points, if any
            inputFields.slice(1, -1).each((idx, inputField) => {
                const $el = $(inputField);
                const $input = $('input', $el);
                $el.addClass('intermediatePoints');
                $el.find('.fa-location-dot').removeClass('text-success text-danger').addClass('text-primary');
                $input.attr('placeholder', Mapbender.trans('mb.routing.frontend.dialog.label.intermediate'));
                let marker = $input.data('marker');
                if (marker) {
                    marker.setStyle(this._getMarkerStyle('intermediateIcon'));
                }
            });
            // intermediate markers can keep their styling, but start end destination markers have to switch
            const startMarker = $('input', inputFields.first()).data('marker');
            const destMarker = $('input', inputFields.last()).data('marker');
            if (startMarker) {
                startMarker.setStyle(this._getMarkerStyle('startIcon'));
            }
            if (destMarker) {
                destMarker.setStyle(this._getMarkerStyle('destinationIcon'));
            }
            if (this.options.autoSubmit) {
                this._getRoute();
            }
        },

        _addPointWithMarker: function(inputEl, coordinates) {
            if (this.options.useReverseGeocoding) {
                const p = {
                    name: 'point',
                    value: [coordinates.lon, coordinates.lat]
                };
                this._getRevGeocode([p]).then(function(response) {
                    const resultLabel = this._checkResultLabel(coordinates, response);
                    $(inputEl).val(resultLabel).data('coords', coordinates).change();
                });
                this._createMarker(inputEl, coordinates);

            } else {
                const feature = new ol.Feature({
                    geometry: new ol.geom.Point(coordinates)
                });
                $(inputEl).data('coords', coordinates).change();
                this._createMarker(inputEl, feature);
            }
        },

        _findLocationInputFields: function() {
            return $('.mb-routing-location-points .input-group input', this.element);
        },

        _removeMarker: function(inputEl) {
            const marker = $(inputEl).data('marker');
            if (marker && this.markerLayer) {
                this.markerLayer.getSource().removeFeature(marker);
            }
            $(inputEl).data('marker', null);
        },

        _getRoutingPoints: function() {
            let isValid = true;
            let routingPoints = [];
            this._findLocationInputFields().each((idx, element) => {
                let coords = $(element).data('coords');
                if ($.trim(coords) === '') {
                    isValid = false;
                    $(element).addClass('empty');
                } else {
                    if (coords === undefined) {
                        isValid = false;
                        $(element).addClass('empty');
                    } else {
                        routingPoints.push(coords);
                        $(element).removeClass('empty');
                    }
                }
            });
            if (!isValid) {
                return false;
            } else {
                return routingPoints;
            }
        },

        _transformCoordinates: function(coordinatePair, srcProj, destinationProj) {
            return ol.proj.transform(coordinatePair, srcProj, destinationProj);
        },

        setSpinnerVisible: function(setVisible){
            let calculateRouteBtn = $('.calculateRoute i', this.element);
            if (setVisible) {
                calculateRouteBtn.attr('class', 'fa-solid fa-sync fa-spin').parent().prop('disabled', true);
            } else {
                calculateRouteBtn.attr('class', 'fa-solid fa-flag-checkered').parent().prop('disabled', false);
            }
        },

        _renderRoute: function(response) {
            if (!this.routingLayer) {
                const styleConfig = this.options.routingStyles;
                const lineColor = styleConfig.lineColor;
                const lineWidth = styleConfig.lineWidth;
                const fill = new ol.style.Fill({
                    color: lineColor,
                });
                const stroke = new ol.style.Stroke({
                    color: lineColor,
                    width: lineWidth,
                });
                this.routingLayer = new ol.layer.Vector({
                    source: new ol.source.Vector(),
                    style: new ol.style.Style({
                        image: new ol.style.Circle({
                            fill: fill,
                            stroke: stroke,
                            radius: 5,
                        }),
                        fill: fill,
                        stroke: stroke,
                    })
                });
                this.olMap.addLayer(this.routingLayer);
                this.routingLayer.setZIndex(1);
            }

            const format = new ol.format.GeoJSON();
            const features = format.readFeatures(response);
            const mapProj = this.olMap.getView().getProjection().getCode();
            const featureProj = 'EPSG:' + response.crs.properties.name.split('::')[1];

            if (mapProj !== featureProj) {
                features.forEach((feature) => {
                    feature.getGeometry().transform(featureProj, mapProj);
                });
            }

            this.routingLayer.getSource().clear();
            this.routingLayer.getSource().addFeatures(features);
            const extent = this.routingLayer.getSource().getExtent();
            this.olMap.getView().fit(extent, {
                padding: new Array(4).fill(this.options.buffer)
            });
        },

        _createMarker: function(inputElement, feature) {
            this._createMarkerLayer();
            const inputIndex = $(inputElement).parent().index();
            const inputLength = this._findLocationInputFields().length;
            let style = {};

            if (inputIndex === 0) {
                style = this._getMarkerStyle('startIcon');
            } else if (inputIndex === inputLength - 1) {
                style = this._getMarkerStyle('destinationIcon');
            } else {
                style = this._getMarkerStyle('intermediateIcon');
            }

            feature.setStyle(style);
            let previousMarker = $(inputElement).data('marker');

            if (previousMarker) {
                this.markerLayer.getSource().removeFeature(previousMarker);
            }

            $(inputElement).data('marker', feature);
            this.markerLayer.getSource().addFeature(feature);
        },

        _createMarkerLayer: function() {
            if (!this.markerLayer) {
                this.markerLayer = new ol.layer.Vector({
                    source: new ol.source.Vector(),
                });
                this.olMap.addLayer(this.markerLayer);
                this.markerLayer.setZIndex(10);
            }
        },

        _getMarkerStyle: function(marker) {
            const styleConfig = this.options.routingStyles[marker];
            if (!styleConfig.imagePath) {
                const msg = Mapbender.trans('mb.routing.exception.main.icon');
                Mapbender.error(msg);
                return null;
            }
            let options = {
                src: Mapbender.configuration.application.urls.base + styleConfig.imagePath,
            };
            if (styleConfig.imageSize) {
                const size = styleConfig.imageSize.split(',');
                options['width'] = Number(size[0]);
                options['height'] = Number(size[1]);
            }
            if (styleConfig.imageOffset) {
                const offset = styleConfig.imageOffset.split(',');
                options['displacement'] = [Number(offset[0]), Number(offset[1])];
            }
            return new ol.style.Style({
                image: new ol.style.Icon(options),
            });
        },

        _getRouteStyle: function() {
            return this.options.styleMap.route;
        },

        _showRouteInfo: function(routeInfo) {
            $('.mb-routing-info', this.element).html(routeInfo).removeClass('d-none');
        },

        _showRouteInstructions: function(instructions) {
            let $table = $('<table/>');
            $table.addClass('table table-striped table-bordered table-sm instructions');
            let $tbody = $('<tbody/>');
            instructions.forEach(function(inst) {
                let $icon;
                let $tr = $('<tr/>');
                let $td = $('<td/>');
                if (inst.icon) {
                    $icon = $('<span/>');
                    $icon.addClass('routing-icon ' + inst.icon);
                    $td.addClass('text-center align-middle').append($icon);
                    $tr.append($td);
                    $td = $('<td/>');
                } else {
                    $icon = $('<span/>');
                    $td.append($icon);
                    $tr.append($td);
                    $td = $('<td/>');
                }
                if (inst.text) {
                    $td.text(inst.text).addClass('inst-text');
                    $tr.append($td);
                    $td = $('<td/>');
                }
                if (inst.metersOnLeg && inst.secondsOnLeg) {
                    let span = '<span>' + inst.metersOnLeg + '<br>' + inst.secondsOnLeg + '</span>';
                    $td.addClass('inst-dist').append(span);
                    $tr.append($td);
                }
                $tbody.append($tr);
            });
            let $instructionsDiv = $('.mb-routing-instructions', this.element);
            let $instructionsTable = $instructionsDiv.children(':first');
            const maxHeight = ($instructionsDiv.offset().top - $('.mb-routing-info').offset().top);
            $instructionsTable.remove();
            if (!$instructionsTable.length) {
                $instructionsDiv.css('max-height', maxHeight);
            }
            $table.append($tbody);
            $instructionsDiv.append($table);
        },

        _showAttribution: function() {
            $('.attribution', this.element).removeClass('d-none');
        }
    });
})(jQuery);

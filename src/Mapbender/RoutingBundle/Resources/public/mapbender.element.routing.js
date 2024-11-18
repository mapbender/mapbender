(function($) {
    'use strict';
    /**
     * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
     * @author Robert Klemm <robert.klemm@wheregroup.com>
     * @namespace mapbender.mbRoutingElement
     */
    $.widget('mapbender.mbRouting', {
        map: null,
        olMap: null,
        popup: null,
        routingLayer: null,
        markerLayer: null,
        elementUrl: null,
        isActive: false,
        styleLinearDistance: {
            pointRadius: 0,
            strokeLinecap : 'square',
            strokeDashstyle: 'dash'
        },

        _create: function() {
            const self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                self._setup(mbMap);
            }, () => {
                Mapbender.checkTarget('mbRoutingElement');
            });
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            const searchDriver = this.options.searchConfig.driver;
            this.searchConfig = this.options.searchConfig[searchDriver];
        },

        _setup: function(mbMap) {
            const self = this;
            this.map = mbMap;
            this.olMap = mbMap.map.olMap;

            if (this.options.autoSubmit) {
                self._autoSubmit();
            }

            this._initializeEventListeners();
            this._trigger('ready');
        },

        open: function(callback) {
            this.callback = callback ? callback : null;
            this.isActive = true;

            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title: Mapbender.trans('mb.routing.backend.title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: this.element,
                    width: 350,
                    height: 490,
                    buttons: {}
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
            this.olMap.removeLayer(this.markerLayer);
            this.olMap.removeLayer(this.routingLayer);
            this.callback ? this.callback.call() : this.callback;
        },

        reveal: function () {
            this.isActive = true;
        },

        hide: function () {
            this.isActive = false;
        },

        _initializeEventListeners: function() {
            const self = this;
            this.olMap.on('singleclick', $.proxy(this._mapClickHandler, this));

            // flush points when srs is changed
            $(document).on('mbmapsrschanged', $.proxy(this._emptyPoints, this));

            // add point on click
            $('#addPoint', this.element).click(() => {
                self._addInputField();
            });

            // remove input on click
            $('.mb-routing-location-points', this.element).on('click', '.clearField', (e) => {
                self._removeInputWrapper(e.target);
            });

            // reset route and input on click
            $('#resetRoute', this.element).click(() => {
                self._clearRoute();
            });

            // swap points on click
            $('#swapPoints', this.element).click(() => {
                self._flipPoints();
            });

            // calculate route button click
            $('#calculateRoute', this.element).click(() => {
                self._getRoute();
            });

            $('.mb-routing-location-points', this.element).on('focus', 'input[type="text"]', (e) => {
                self.focusedInputField = e.target;
            });

            if (this.options.useSearch) {
                $('.mb-routing-location-points', this.element).on('focus', 'input[type="text"]', (e) => {
                    $(e.target).autocomplete({
                        minLength: 3,
                        source: self._handleAutocompleteSource.bind(self),
                        focus: (event, ui) => {
                            $(this).val(ui.item.label);
                            return false;
                        },
                        select: self._handleAutocompleteSelect.bind(self)
                    }).autocomplete('instance')._renderItem = (ul, item) => {
                        return $('<li>')
                            .append('<a>' + item.label + '</a>')
                            .appendTo(ul);
                    };
                });
            }

            $('.mb-routing-location-points', this.element).sortable({
                start: (e, ui) => {
                    $(e.target).attr('data-previndex', ui.item.index());
                },
                update: (e) => {
                    $(e.target).removeAttr('data-previndex');
                    self._reorderInputFields();
                }
            }).disableSelection();
        },

        _autoSubmit: function() {
            const self = this;
            $('.mb-routing-location-points', this.element).change(() => {
                if (self._isInputValid()) {
                    self._getRoute();
                }
            });
        },

        _isInputValid: function() {
            let isValid = true;
            $.each($(this).find('.mb-routing-location-points input'), (index, element) => {
                isValid = isValid && ($(element).val() !== '');
            });
            return isValid;
        },

        _mapClickHandler: function(event) {
            if (this.isActive && this.focusedInputField) {
                let coordinates = event.coordinate.toString();
                $(this.focusedInputField).val(coordinates);
                const regex = new RegExp(/^(\-?\d+(\.\d+)?)(?:,|;|\s)+(\-?\d+(\.\d+)?)$/);

                if (regex.test(coordinates)) {
                    coordinates = coordinates.split(',');
                    coordinates[0] = parseFloat(coordinates[0].trim());
                    coordinates[1] = parseFloat(coordinates[1].trim());
                    this._addPointWithMarker(this.focusedInputField, coordinates);
                    const source = this.markerLayer.getSource();
                    const extent = source.getExtent();

                    if (source.getFeatures().length > 1) {
                        this.olMap.getView().fit(extent, {
                            padding: new Array(4).fill(this.options.buffer)
                        });
                    }
                } else {
                    Mapbender.error('Invalid coordinates provided!')
                }
            }
        },

        _emptyPoints: function() {
            const self = this;
            const $inputs = this._findLocationInputFields();
            $.each($inputs, (idx, input) => {
                self._removeMarker(input);
                $(input).val('').data('coords', null);
            });
        },

        _addInputField: function() {
            const htmlIntermediatePoint = $($('#tplIntermediatePoint').html());
            const lastInputElement = $('.mb-routing-location-points div:last-child', this.element);
            htmlIntermediatePoint.insertBefore(lastInputElement);
            htmlIntermediatePoint.find('input').focus();
        },

        _removeInputWrapper: function(btn) {
            const self = this;
            const inputGroup = $(btn).parent();
            let inputField = $('input', inputGroup);
            inputField.val('').data('coords', null);
            this._removeMarker(inputField);
            if (this._findLocationInputFields().length > 2) {
                inputGroup.remove();
                self._reorderInputFields();
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
            $('.mb-routing-info', this.element).addClass('d-none').html('');
            $('.mb-routing-instructions', this.element).html('');
            return true;
        },

        _flipPoints: function() {
            const form = $('.mb-routing-location-points', this.element);
            const inputFields = form.children('div');
            form.append(inputFields.get().reverse());
            this._reorderInputFields();
        },

        _getRoute: function() {
            const self = this;
            const requestProj = 'EPSG:4326';
            const mapProj = this.olMap.getView().getProjection().getCode();
            let points = this._getRoutingPoints();

            if (!points) {
                console.warn('No valid points for routing.');
                return false;
            }

            if (requestProj !== mapProj) {
                points = points.map((point) => {
                    return self._transformCoordinates(point, mapProj, requestProj);
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
            }).fail(() =>  {
                Mapbender.error('route service is not available');
                self.setSpinnerVisible(false);
            }).done((response) => {
                if (response.error) {
                    Mapbender.error(response.error.message);
                } else {
                    self._renderRoute(response.featureCollection);
                    self._showRouteInfo(response.routeInfo);
                    self._showRouteInstructions(response.routingInstructions);
                }
                self.setSpinnerVisible(false);
            });
        },

        _handleAutocompleteSource: function (request, _response) {
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
                    _response($.map(response, function (value) {
                        return {
                            label: value[self.searchConfig.label_attribute],
                            geom: value[self.searchConfig.geom_attribute],
                            srid: (self.searchConfig.geom_proj) ? self.searchConfig.geom_proj : self.olMap.getView().getProjection().getCode()
                        };
                    }));
                }
            });
        },

        _handleAutocompleteSelect: function (e, ui) {
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
            const self = this;
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
                    marker.setStyle(self._getMarkerStyle('intermediateIcon'));
                }
            });
            // intermediate markers can keep their styling, but start end destination markers have to switch
            const startMarker = $('input', inputFields.first()).data('marker');
            const destMarker = $('input', inputFields.last()).data('marker');
            if (startMarker) {
                startMarker.setStyle(self._getMarkerStyle('startIcon'));
            }
            if (destMarker) {
                destMarker.setStyle(self._getMarkerStyle('destinationIcon'));
            }
            if (this.options.autoSubmit) {
                self._getRoute();
            }
        },

        _addPointWithMarker: function(inputEl, coordinates) {
            if (this.options.useReverseGeocoding) {
                const p = {
                    name: 'point',
                    value: [coordinates.lon,coordinates.lat]
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
            let calculateRouteBtn = $('#calculateRoute i');
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

            // create and add result or airline Features
            // var air_line = self._getAirLineFeature(response, style);
            // self.routingLayer.addFeatures(air_line);

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
                src: styleConfig.imagePath,
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
                    $td.addClass('text-center').append($icon);
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

        _transformFeatures: function(routeData,respProj,mapProj) {
            var lineGeometry = routeData.features;
            var self = this;
            lineGeometry.forEach(function (feature, index) {
                var geomCoordinates = feature.geometry.coordinates;
                var propWayPoints = feature.properties.waypoints || null;

                routeData.features[index].geometry.coordinates = self._transformLineGeometry(
                    geomCoordinates,
                    respProj,
                    mapProj
                );

                // transform snapped WayPoints
                if (propWayPoints){
                    propWayPoints.forEach(function (element) {
                        element.coordinates = self._transformCoordinates(
                            element.coordinates,
                            respProj,
                            mapProj
                        );
                        element.srid = mapProj.projCode;
                    });
                }
            });
        },

        _transformLineGeometry: function(lineGeometry, inputProj, mapProj) {
            var transformedLineGeometry = [];
            var self = this;
            lineGeometry.forEach(function(pt) {
                var p = self._transformCoordinates(pt, inputProj, mapProj);
                transformedLineGeometry.push([p[0], p[1]]);
            });
            return transformedLineGeometry;
        },

        _transformMultiLineGeometry: function(multilineGeometry, requestProj, mapProj) {
            var self = this;
            var transformedLineGeometry = [];
            multilineGeometry.forEach(function(line) {
                var l = self._transformLineGeometry(line, requestProj, mapProj);
                transformedLineGeometry.push(l);
            });
            return transformedLineGeometry;
        },

        _revGeocode: function (feature){
            var self = this;

            if (self.options.useReverseGeocoding) {
                var p = {
                    name: "point",
                    value: [feature.geometry.x,feature.geometry.y]
                };

                self._getRevGeocode([p]).then(function(response) {
                    var resultLabel = self._checkResultLabel(feature,response);
                    $(feature.attributes.input)
                        .val(resultLabel)
                        .data('coords', [feature.geometry.x, feature.geometry.y])
                        .change();
                });

                return true;

            }else{
                return false;
            }
        },

        _getRevGeocode: function(coordinate) {
            var self = this;
            return $.ajax({
                type: "GET",
                url: self.elementUrl + 'revGeocode',
                data: {
                    coordinate: coordinate,
                    srsId: self.olMap.getProjectionObject().projCode
                }
            });
        },

        _getAirLineFeature: function (response, style) {
            var self = this;
            var points = self._getRoutingPoints();
            var airLineFeatures = [];
            var waypoints = response.features[0].properties.waypoints;
            //IE doesn't support Object.assign()
            //https://stackoverflow.com/questions/42091600/how-to-merge-objects-in-ie-11
            var styleLinearDistance = self.options.styleLinearDistance;
            var stylesToCombine = [styleLinearDistance, style];
            var styleLinearDistance = stylesToCombine.reduce(function (r, o) {
                Object.keys(o).forEach(function (k) {
                    r[k] = o[k];
                });
                return r;
            }, {});

            // Loop inputPoints of Frontend
            points.forEach(function (element, index) {
                var coordPoints = element; // coordinatePair (Input)
                var inputPoint =  new OpenLayers.Geometry.Point (coordPoints[0],coordPoints[1]); // geomety of points
                var wayCoord = waypoints[index].coordinates; // wayPoints of Response
                var wayPoint = new OpenLayers.Geometry.Point (wayCoord[0],wayCoord[1]);

                var lineGeometry = new OpenLayers.Geometry.LineString([inputPoint, wayPoint]);
                airLineFeatures.push(new OpenLayers.Feature.Vector(lineGeometry, null, styleLinearDistance));
            });
            return airLineFeatures;
        },

        _checkResultLabel: function (feature,response) {
            var resultLabel = "", x, y;

            if (feature instanceof OpenLayers.Feature.Vector) {

                x = feature.geometry.x;
                y = feature.geometry.y;
            } else {

                x = feature.lon;
                y = feature.lat;
            }

            resultLabel = x + "," + y;

            if (Array.isArray(response)) {
                response.forEach(function (element, index) {
                    resultLabel = element.label || resultLabel ;
                    if (element.hasOwnProperty('messages')){
                        console.log('ReversGeocoding: '+element.messages);
                    }
                });
            }

            return resultLabel;
        }
    });
})(jQuery);

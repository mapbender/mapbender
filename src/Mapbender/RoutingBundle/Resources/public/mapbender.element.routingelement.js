(function($) {
    'use strict';
    /**
     * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
     * @author Robert Klemm <robert.klemm@wheregroup.com>
     * @namespace mapbender.mbRoutingElement
     */
    $.widget('mapbender.mbRoutingElement', {
        options: {
            target: null,
            routingIcons: {
                images: {
                    startIcon: 'bundles/mapbenderrouting/image/start-map.png',
                    destinationIcon: 'bundles/mapbenderrouting/image/destination-map.png',
                    intermediateIcon: 'bundles/mapbenderrouting/image/intermediate-map.png'
                },
                width: 37,
                height: 41,
                xoffset: -13,
                yoffset: -41
            },
            styleLinearDistance: {
                pointRadius: 0,
                strokeLinecap : 'square',
                strokeDashstyle: 'dash'
            }
        },
        map: null,
        olMap: null,
        routingLayer: null,
        markerLayer: null,
        intermediateMarker: null,
        mapClickHandlerCoordinate: null,
        elementUrl: null,
        configuration: null,
        pointsSet: {
            start: false,
            destination: false
        },
        inputWrapperSkeleton: null,
        placeholders: {},
        reverseGeocoding: null,
        search: null,
        snappedWayPoint: null,
        popup: null,
        routeData: null,

        _create: function() {
            const self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self._setup(mbMap);
            }, function () {
                Mapbender.checkTarget('mbRoutingElement');
            });
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.configuration = Mapbender.configuration.elements[this.element.attr('id')].configuration;
            this.inputWrapperSkeleton = $('.wrapper-clone', this.element).clone();
            this.inputWrapperSkeleton.removeClass('wrapper-clone');
            this.placeholders = {
                'start': $('.input-wrapper.start input', this.element).attr('placeholder'),
                'destination': $('.input-wrapper.destination input', this.element).attr('placeholder'),
                'intermediate': $('input', this.inputWrapperSkeleton).attr('placeholder')
            };
            this.reverseGeocoding = !!this.configuration.addReverseGeocoding;
            this.search = !!this.configuration.addSearch;
        },

        _setup: function(mbMap) {
            var self = this;
            this.map = mbMap;
            this.olMap = mbMap.map.olMap;

            if (!this.configuration.disableContextMenu) {
                this._initializeContextMenu();
            }

            if (this.configuration.autoSubmit) {
                self._autoSubmit();
            }

            this._initializeEventListeners();
            this._trigger('ready');
        },

        defaultAction: function (callback) {
            this.open(callback);
        },

        /**
         * open popoup dialog
         * @param callback
         * @returns {boolean}
         */
        open: function(callback) {
            this.callback = callback ? callback : null;
            const element = $(this.element);

            if (this.options.type !== 'dialog') {
                return false;
            }

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
                this.activate();
                element.show();
            }
        },

        activate: function() {
            if (!this.configuration.disableContextMenu) {
                this._initializeContextMenu();
            }
            this.markerLayer && this.markerLayer.setVisibility(true);
            this.routingLayer && this.routingLayer.setVisibility(true);
        },

        deactivate: function() {
            $(this.olMap.div).off('contextmenu');
            this.markerLayer && this.markerLayer.setVisibility(false);
            this.routingLayer && this.routingLayer.setVisibility(false);
        },

        _initializeEventListeners: function() {
            var self = this;

            this.olMap.on('singleclick', $.proxy(this._mapClickHandler, this));

            // flush points when srs is changed
            $(document).on('mbmapsrschanged', () => {
                self._emptyPoints();
            });

            // add point on click
            $('button[name=addPoint]', this.element).click(() => {
                self._addInputWrapper();
            });

            // remove input on click
            $('.clearField', this.element).on('click', (e) => {
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

            this._findLocationInputFields().each((e) => {
                $(e.target).on('focusout', self._handleInputFocusOut.bind(self));
            });

            $('.mb-routing-location-points input', this.element).autocomplete({
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

            $('.mb-routing-location-points', this.element).sortable({
                start: function (event, ui) {
                    $(this).attr('data-previndex', ui.item.index());
                },
                update: function(event, ui) {
                    $(this).removeAttr('data-previndex');
                    self._reorderPointDiv();
                }
            }).disableSelection();

            if (self.element.closest('.tabContainer,.accordionContainer')) {
                // set up marker layer visibility switching depending on "active" vs "inactive"
                console.log(MapbenderContainerInfo);
                /*
                var _ = new MapbenderContainerInfo(self, {
                    onactive: $.proxy(self.activate, self),
                    oninactive: $.proxy(self.deactivate, self)
                });
                */
            }
        },

        /**
         * On right click on map, coordinate needs to be extracted so the context menu can access it
         * @todo: We store the click coords in the instance so the context menu can get them. This is pretty ugly.
         *        Unfortunately, there doesn't seem to be a way to access the click event directly in the context menu
         *        event handler. See docs at https://swisnl.github.io/jQuery-contextMenu/docs.html#events
         *        Maybe we should open an issue to ask for access to the event in a future release.
         *
         * @param event
         * @private
         */
        _mapClickHandler: function(event) {
            // TODO ugly!!
            this.mapClickHandlerCoordinate = event.coordinate;
        },

        /**
         * Get all coordinate inputs (jQuery collection)
         * @private
         */
        _findLocationInputFields: function() {
            return $('.mb-routing-location-points .input-group input', this.element);
        },

        _initializeContextMenu: function() {
            /*
            var self = this;
            $(this.olMap.div).contextMenu({
                selector: 'div',
                events: {
                    'show': function(options) {
                        // suppress context menu, if dialog is currently not open.
                        if (self.options.type === 'dialog' && !self.popup) {
                            return false;

                        // suppress context menu, if element is currently not activated
                        } else if (self.options.type === 'element' && !self._isActive()) {
                            return false;
                        }
                    }
                },
                items: this._createContextMenuItems()
            });
            */
        },

        _createContextMenuItems: function() {
            var items = {},
                self = this;
            var start = {
                name: Mapbender.trans('mb.routing.frontend.context.btn.start'),
                callback: function(){
                    var inputEl = $('.input-wrapper.start input', self.element);
                    self._addPointToInput(inputEl, self.mapClickHandlerCoordinate);
                }
            };
            var intermediate = {
                name: Mapbender.trans('mb.routing.frontend.context.btn.intermediate'),
                callback: function(){
                    var inputEl = self._addInputWrapper();
                    self._addPointToInput(inputEl, self.mapClickHandlerCoordinate);
                },
                disabled: function (){
                    return ($(".start > input", self.element).val() === '' ||
                        $(".destination > input", self.element).val() === '');
                }
            };
            var destination = {
                name: Mapbender.trans('mb.routing.frontend.context.btn.destination'),
                callback: function(){
                    var inputEl = $('.input-wrapper.destination input', self.element);
                    self._addPointToInput(inputEl, self.mapClickHandlerCoordinate);
                }
            };
            if (this.configuration.addIntermediatePoints) {
                items = {
                    'start': start,
                    'sep1': '---------',
                    'intermediate': intermediate,
                    'sep2': '---------',
                    'destination': destination
                };
                return items;
            }
            items = {
                'start': start,
                'sep1': '---------',
                'destination': destination
            };
            return items;
        },

        _handleInputFocusOut: function () {
            console.log('geht')
            return;
            var inputVal = $(this).val();
            var coordArray = null;
            var coordinates = null;
            var $inputEl = null;
            var patt = new RegExp(/^(\-?\d+(\.\d+)?)(?:,|;|\s)+(\-?\d+(\.\d+)?)$/);
            var extent = this.olMap.getView().calculateExtent();
            // var extent = self.olMap.getMaxExtent().clone();

            if (this._isValidGPSCoordinates(inputVal)) {
                coordArray = $(this).val().split(/[\s,;]+/);
                var x = coordArray[0].trim();
                var y = coordArray[1].trim();
                var destProj = this.olMap.getProjectionObject();
                var sourceProj = new OpenLayers.Projection('EPSG:4326');
                coordinates = new OpenLayers.LonLat(y,x);
                coordinates.transform(sourceProj, destProj);
                extent = extent.transform(destProj, sourceProj);
                // check if coordinates are inside max extent
                if (x > extent['top'] || x < extent['bottom'] || y > extent['right'] || y < extent['left']) {
                    console.warn('Input: Coordinates out of bounds');
                    return false;
                }

                $inputEl = $(this); // current Status
                this._addPointToInput($inputEl, coordinates);

                if(this.markerLayer.features.length > 1) {
                    this.olMap.zoomToExtent(this.markerLayer.getDataExtent());
                    return true;
                }

                this.olMap.setCenter(coordArray);
            } else if (patt.test(inputVal)) {
                coordArray = $(this).val().split(',');
                coordinates = {
                    lon: parseFloat(coordArray[0].trim()),
                    lat: parseFloat(coordArray[1].trim())
                };

                // check if coordinates are inside max extent
                if (coordinates.lat > extent['top'] || coordinates.lat < extent['bottom'] || coordinates.lon > extent['right'] || coordinates.lon < extent['left']) {
                    console.warn('Input: Coordinates out of bounds');
                    return false;
                }

                $inputEl = $(this); // current Status
                this._addPointToInput($inputEl, coordinates);

                if(this.markerLayer.features.length > 1) {
                    this.olMap.zoomToExtent(this.markerLayer.getDataExtent());
                    return true;
                }

                this.olMap.setCenter(coordArray);
            }
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
                $('> .mb-routing-error', self.element).empty();
                if (response.error) {
                    self._searchErrorHandling(response.error);
                } else {
                    _response($.map(response, function (value) {
                        return {
                            label: value[self.options.label_attribute],
                            geom: value[self.options.geom_attribute],
                            srid: (self.options.geom_proj) ? self.options.geom_proj : self.olMap.getView().getProjection().getCode()
                        };
                    }));
                }
            });
        },

        _handleAutocompleteSelect: function (event, ui) {
            $(event.target).val(ui.item.label);
            let format;
            switch (this.options.geom_format) {
                case 'WKT':
                    format = new ol.format.WKT();
                    break;
                case 'GeoJSON':
                    format = new ol.format.GeoJSON();
                    break;
                default:
                    const msg = Mapbender.trans('mb.routing.exception.main.format') + ': ' + this.options.geom_format;
                    Mapbender.error(msg);
                    throw new Error(msg);
            }
            const feature = format.readFeature(ui.item.geom, {
                dataProjection: ui.item.srid,
                featureProjection: this.olMap.getView().getProjection().getCode(),
            });
            //const coords = feature.getGeometry().getCoordinates();
            //$(this).data('coords', coords);
            this._createMarker(event.target, feature);
            const source = this.markerLayer.getSource();
            let geometryOrExtent = source.getExtent();

            if (source.getFeatures().length === 1) {
                geometryOrExtent = feature.getGeometry();
            }

            this.olMap.getView().fit(geometryOrExtent, {
                padding: new Array(4).fill(this.options.buffer)
            });

            if (this.options.autoSubmit) {
                this._getRoute();
                event.target.blur();
            }

            return false;
        },

        _isActive: function() {
            var $sidebarContainer = $(this.element).closest('.container-accordion,.container');
            if ($sidebarContainer) {
                return $sidebarContainer.hasClass('active');
            } else {
                console.warn("Warning: _mapbender.mbRoutingElement._isActive not implemented for current container; only supports 'accordion' or 'tabs' style sidebar");
                return true;
            }
        },

        /**
         * Sets clicked coordinates to input fields
         * @param inputEl
         * @param coordinates
         * @private
         */
        _addPointToInput: function(inputEl, coordinates) {
            var self = this;

            // Check set reverseGeocoding
            if (self.reverseGeocoding) {
                var p = {
                    name: "point",
                    value: [coordinates.lon,coordinates.lat]
                };

                self._getRevGeocode([p]).then(function(response) {
                    var resultLabel = self._checkResultLabel(coordinates,response);
                    $(inputEl).val(resultLabel).data('coords', [coordinates.lon,coordinates.lat]).change();
                });

                self._createMarker(inputEl, coordinates);

            } else {

                $(inputEl).val(Number((coordinates.lon).toFixed(2)) + "," + Number((coordinates.lat).toFixed(2))).data('coords', [coordinates.lon, coordinates.lat]).change();
                self._createMarker(inputEl, coordinates);
            }
        },

        _createMarker: function(inputElement, feature) {
            this._createMarkerLayer();
            const inputIndex = $(inputElement).parent().index();
            const inputLength = this._findLocationInputFields().length;
            let style = {};
            console.log(inputIndex, inputLength)
            console.log(this.options.styleMap)

            if (inputIndex === 0) {
                style = this._getMarkerStyle('start');
            } else if (inputIndex === inputLength - 1) {
                style = this._getMarkerStyle('destination');
            } else {
                style = this._getMarkerStyle('intermediate');
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
                // Make features draggable
                /*
                self.olMap.addControl(new OpenLayers.Control.DragFeature(self.markerLayer, {
                    autoActivate: true,
                    onComplete: function (feature) {
                        // Check ReversGeocode and change features attribute
                        if (self._revGeocode(feature) === false) {
                            $(feature.attributes.input)
                                .val(Number(feature.geometry.x).toFixed(2) + "," + Number(feature.geometry.y).toFixed(2))
                                .data('coords', [feature.geometry.x, feature.geometry.y])
                                .change();
                        }
                    }
                }));
                */
            }
        },

        /**
         * Transforms coordinate pair
         * @param coordinatePair Array [lon, lat] (from point array)
         * @param fromProj String olMap.getProjectionObject() "EPSG:xxxx"
         * @param toProj String olMap.getProjectionObject() "EPSG:xxxx"
         * @returns Array new projected point
         * @private
         */
        _transformCoordinates: function(coordinatePair, fromProj, toProj) {
            var olLonLat = new OpenLayers.LonLat(coordinatePair[0], coordinatePair[1]);
            var olPoint =  olLonLat.transform(fromProj, toProj);
            return [olPoint.lon, olPoint.lat];
        },

            /**
             * Returns a serialzed form of point array
             * @private
             */
        _getSerializedPoints: function() {
            var isValid = true;
            var pointsArrayNew = [];
            this._findLocationInputFields().each(function(i,element) {
                var coords = $(element).data('coords');
                if ($.trim(coords) === '') {
                    isValid = false;
                    $(element).addClass('empty');
                } else {
                    if (coords === undefined) {
                        isValid = false;
                        $(element).addClass('empty');
                    } else {
                        pointsArrayNew.push(coords);
                        $(element).removeClass('empty');
                    }
                }
            });
            if (!isValid) {
                return false;
            } else {
                return pointsArrayNew;
            }
        },

            /**
             * Returns selected transportation mode
             * @private
             */
        _getTransportationMode: function() {
            return $('input[name=vehicle]:checked', this.element).val();
        },

            /**
             *
             * @returns {Backbone.Router.route|Backbone.History.route}
             * @private
             */
        _getRouteStyle: function() {
            return this.configuration.styleMap.route;
        },

            /**
             * route action
             * TODO Refactor, shorten it!!!
             * @private
             */
        _getRoute: function() {
            var self = this,
                requestProj = new OpenLayers.Projection('EPSG:4326'),
                mapProj = self.olMap.getProjectionObject();

            var points = this._getSerializedPoints();
            if (!points) {
                console.warn("no valid points for routing");
                return false;
            }

            if (requestProj.projCode !== mapProj.projCode) {
                points = points.map(function(point) {
                    return self._transformCoordinates(point, mapProj, requestProj);
                });
            }

            var ajaxSettings = {
                type: 'POST',
                url: self.elementUrl + 'getRoute',
                data: {
                    'vehicle': this._getTransportationMode(),
                    'points': points,
                    'srs': mapProj.projCode
                }
            };

            self.spinner.activate();

            $.ajax(ajaxSettings)
                .success(function() {
                    console.log('ajax success');
                 })
                .fail(function () {
                    var errorDiv = $('> .mb-routing-error', self.element);
                    var error = {
                        'apiMessage' : 'route service is not available'
                    };
                    errorDiv.empty();
                    self._routingErrorHandling(error);
                    self.spinner.deactivate();
                })
                .done(function (response) {

                    var errorDiv = $('> .mb-routing-error', self.element);
                    var respProj = null;
                    var routeData = null;

                    // clear errorDiv
                    errorDiv.empty();

                    if (response.routeError) {
                        self._routingErrorHandling(response.routeError);
                    } else {
                        routeData = response.routeData;
                        self.routeData = routeData;
                        var srs = parseInt(routeData.crs.properties.name.split("::")[1]);
                        respProj =  srs > 0 ? new OpenLayers.Projection('EPSG:'+srs) : requestProj.projCode;

                        // Check requestProj and Transform LineGeometry array
                        // ol2 can not transform whole geometry objects therefore this way.
                        if (mapProj.projCode !== respProj.projCode) {
                            self._transformFeatures(routeData,respProj,mapProj);
                        }

                        var routeStyle = self._getRouteStyle();
                        self._renderRoute(routeData, routeStyle);
                        if (routeData.features[0].properties.instructions) {
                            self._parseRouteInstructions(routeData.features[0].properties.instructions);
                        }
                        var routeInfo = self._parseRouteData(routeData.features[0].properties);
                        self._displayRouteInfo(routeInfo);
                    }
                    self.spinner.deactivate();
                });
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


            /**
             * creates new OpenLayers geoJson layer, adds it to map
             * @param response
             * @param style
             * @private
             */
        _renderRoute: function(response, style) {
            var self = this;
            var olGeoJSON = new OpenLayers.Format.GeoJSON();
            var targetExtent;

            if (self.routingLayer === null) {
                self.routingLayer = new OpenLayers.Layer.Vector('routingLayer', {
                    style: style
                });
                self.olMap.addLayer(self.routingLayer);
            }
            self.routingLayer.removeAllFeatures();
            // create and add result or airline Features
            var air_line = self._getAirLineFeature(response, style);
            self.routingLayer.addFeatures(air_line);
            var line = olGeoJSON.read(response);
            self.routingLayer.addFeatures(line);

            targetExtent = self.routingLayer.getDataExtent();
            if (self.markerLayer) {
                targetExtent.extend(self.markerLayer.getDataExtent());
            }
            self._zoomToFittingExtent(targetExtent);

        },

            /**
             * Takes instructions object and renders a table with routing instructions
             * @param instructions
             * @private
             */
        _parseRouteInstructions: function(instructions) {
            var $t = $('<table/>');
            $t.addClass('instructions');
            instructions.forEach(function(inst) {
                var $icon;
                var $tr = $('<tr/>');
                var $td = $('<td/>');
                if (inst.icon) {
                    $icon = $('<img/>');
                    $icon.attr('src', inst.icon);
                    $td.addClass('inst-marker').append($icon);
                    $tr.append($td);
                    $td = $('<td/>');
                } else {
                    $icon = $('<span/>');
                    $td.addClass('inst-marker').append($icon);
                    $tr.append($td);
                    $td = $('<td/>');
                }
                if (inst.text) {
                    $td.text(inst.text).addClass('inst-text');
                    $tr.append($td);
                    $td = $('<td/>');
                }
                if (inst.metersOnLeg && inst.secondsOnLeg) {
                    var span = '<span>' + inst.metersOnLeg + '<br>' + inst.secondsOnLeg + '</span>';
                    $td.addClass('inst-dist').append(span);
                    $tr.append($td);
                }
                $t.append($tr);
            });
            var $instructionsDiv = $('.mb-routing-instructions', this.element);
            var $instructionsTable = $instructionsDiv.children(':first');
            var maxHeight = ($instructionsDiv.offset().top - $('.mb-routing-info').offset().top);
            $instructionsTable.remove();
            if (!$instructionsTable.length) {
                $instructionsDiv.css('max-height', maxHeight)
            }
            $instructionsDiv.append($t);
        },

            /**
             *
             * @param response
             * @returns {{length: null, lengthUnit: null, time: null, timeUnit: null, instructions: Array}}
             * @private
             */
        _parseRouteData: function(properties) {
            // passes parameters to _get Snapped WayPoint otherwise no parameter
            if (properties.waypoints){
                this._setSnappedWayPoint(properties.waypoints);
            }else{
                this._setSnappedWayPoint();
            }

            var routeInfo = {
                    length: properties.length,
                    lengthUnit: properties.lengthUnit,
                    time: properties.graphTime,
                    timeUnit: properties.graphTimeFormat,
                    instructions: [],
                    start: this.snappedWayPoint.startValue,
                    destination: this.snappedWayPoint.destinationValue
                };

            if (routeInfo.timeUnit === "ms" ) {
                routeInfo.time = this.__msToTime(routeInfo.time);
            }
            routeInfo.length = (routeInfo.length < 1000) ? Math.round(routeInfo.length * 100) / 100 + 'm' :  Math.round(routeInfo.length/1000*100) /100 + 'km';

            return routeInfo;
        },

            /**
             * Return responseWayPoints from EleInput or Response
             * Here it is checked if the search or reverse geocoding variable is set to true, then the input fields are used
             * @param responseWayPoints
             * @returns {array}
             * @private
             */
        _setSnappedWayPoint: function(responseWayPoints){
            var inputStartEle = $('.input-wrapper.start input', this.element);
            var inputDestEle  = $('.input-wrapper.destination input', this.element);
            var resultValue;

            var inputContainsCoords = (input) => {
                return input.val() === input.data('coords').join(',');
            }

            if (responseWayPoints){
                var responseWayPointLength = responseWayPoints.length;
                var responseStartName = responseWayPoints[0].name;
                var responseDestinationName = responseWayPoints[responseWayPointLength - 1].name;
            }

            if (this.search || this.reverseGeocoding){


                var start = (responseStartName && inputContainsCoords(inputStartEle)) ? responseStartName : inputStartEle.val();
                var dest = (responseDestinationName && inputContainsCoords(inputDestEle)) ? responseDestinationName : inputDestEle.val();

                console.log(start,responseStartName,inputStartEle.val());
                console.log(dest,responseDestinationName,inputDestEle.val());
                resultValue ={
                    startValue: start,
                    destinationValue: dest
                };
            } else {
                resultValue = {
                    startValue:  responseStartName || inputStartEle.val(),
                    destinationValue: responseDestinationName || inputDestEle.val()
                };
            }
            this.snappedWayPoint = resultValue;
        },

            /**
             * Convert Ms to hrs
             * @param s
             * @returns {string}
             * @private
             */
        __msToTime: function(s) {
            var ms = s % 1000;
            s = (s - ms) / 1000;
            var secs = s % 60;
            s = (s - secs) / 60;
            var mins = s % 60;
            var hrs = (s - mins) / 60;

            return hrs === 0 ? mins + ' min' : hrs + ' h ' + mins + ' min ';
        },

            /**
             *
             * @param routeInfo
             * @returns {boolean}
             * @private
             */
        _displayRouteInfo: function(routeInfo) {
            var $routeInfoDiv = $(".mb-routing-info", this.element),
                infoText = this.configuration.infoText;
            infoText = infoText.replace(/{start}|{destination}|{time}|{length}/g,
                function(matched) {
                    matched = matched.replace(/{|}/gi, '');
                    return routeInfo[matched];
                });
            $routeInfoDiv.html("<p>" + infoText + "<p/>").show();
            return true;
        },

            /**
             * Checks for buffer parameter and zooms to a fitting extent (or buffer)
             * @param extent
             * @private
             */
        _zoomToFittingExtent: function(extent) {
            var buffer = this.configuration.buffer;
            if (buffer === 0) {
                var targetZoom = this.olMap.getZoomForExtent(extent),
                    targetCenter = extent.getCenterLonLat();
                this.olMap.moveTo(targetCenter, targetZoom - 1);
            }
            extent.bottom -= buffer;
            extent.top += buffer;
            extent.left -= buffer;
            extent.right += buffer;
            this.olMap.zoomToExtent([extent.left, extent.bottom, extent.right, extent.top]);
        },

            /**
             * Does error handling for route request
             * @param response
             * @private
             */
        _routingErrorHandling: function(response) {
            var errorDiv = $('> .mb-routing-error', this.element);

            if (response.apiMessage) {
                var tmpErrors = response.apiMessage;
                var messageDetails = '';

                if (response.messageDetails > 0) {
                    for (var m = 0; m < response.messageDetails.length; m++) {
                        messageDetails += response.messageDetails[m].message + ' ';
                    }
                }

                console.error('RouteActionError: ' + tmpErrors + messageDetails);
                errorDiv.html(Mapbender.trans(tmpErrors));
            }
        },

            /**
             * Transforms line geometry, usually coming from a geoJson
             * @param lineGeometry
             * @param inputProj
             * @param mapProj
             * @returns {Array}
             * @private
             */
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

            /**
             * Creates a new input for an intermediate coordinate
             * Returns the input element
             * @private
             */
        _addInputWrapper: function() {

            var intermediateWrapperDiv = this.inputWrapperSkeleton.clone(),
                lastDiv = $(".mb-routing-location-points div:last-child", this.element);
            intermediateWrapperDiv.insertBefore(lastDiv).show();
            return $('input', intermediateWrapperDiv);
        },

            /**
             * Removes a point input and its corresponding map marker
             * @param element the clicked on span element .cancelIcon
             * @private
             */
        _removeInputWrapper: function(element) {
            var self = this;
            var currentDiv = $(element).parent().closest('div');

            var $inputEl = $('input', $(element).parent());
            $inputEl.val('');
            this._removeMarker($inputEl);
            if (this._findLocationInputFields().length > 2) {
                currentDiv.remove();
                self._reorderPointDiv();
            }
        },

            /**
             * Removes the marker tied to the given coordinate input
             * @param inputEl
             * @private
             */
        _removeMarker: function(inputEl) {
            var marker = $(inputEl).data('marker');
            if (marker && this.markerLayer) {
                this.markerLayer.removeFeatures(marker);
                this.markerLayer.redraw();
            }
            $(inputEl).data('marker', null);
        },

            /**
             * Removes user input, when srs is changed
             * TODO implement coordinate AND geometry transformation
             * @private
             */
        _emptyPoints: function() {
            var self = this;
            var $inputs = this._findLocationInputFields();
            $.each($inputs, function() {
                self._removeMarker(this);
                $(this).val('').data('coords', null);
            });
        },

            /**
             * Reverse point order last to first
             * @private
             */
        _flipPoints: function() {
            var list = $('.mb-routing-location-points', this.element);
            var listItems = list.children('div');
            list.append(listItems.get().reverse());
            this._reorderPointDiv();
        },

            /**
             * When user input fields are dragged around, reorders points accordingly
             * @private
             */
        _reorderPointDiv: function() {
            var self = this;
            var list = $('.mb-routing-location-points > div', this.element);

            /*
            first div always has class .start
            last div always has class .destination
            intermediate divs always have class .intermediate
            */
            list.first().removeClass().addClass("input-wrapper start");
            list.last().removeClass().addClass("input-wrapper destination");
            $('input', list.first()).attr('placeholder', this.placeholders.start);
            $('input', list.last()).attr('placeholder', this.placeholders.destination);
            // update intermediate points, if any
            list.slice(1, -1).each(function() {
                var $el = $(this);
                var $input = $('input', this);
                $el.removeClass().addClass("input-wrapper intermediate");
                $input.attr('placeholder', self.placeholders.intermediate);
                var marker = $input.data('marker');
                if (marker) {
                    marker.style = self._getMarkerStyle('intermediate');
                }
            });

            // intermediate markers can keep their styling, but start end destination markers have to switch
            var startMarker = $('input', list.first()).data('marker');
            var destMarker = $('input', list.last()).data('marker');
            if (startMarker) {
                startMarker.style = this._getMarkerStyle('start');
            }
            if (destMarker) {
                destMarker.style = this._getMarkerStyle('destination');
            }
            if (this.markerLayer) {
                this.markerLayer.redraw();
            }
            if (this.configuration.autoSubmit) {
                self._getRoute();
            }
        },

        /**
         *
         * @param marker
         * @returns {{graphicWidth: number, graphicHeight: number, graphicXOffset: number|*, graphicYOffset: number|*}}
         * @private
         */
        _getMarkerStyle: function(marker) {
            let styleConfig = this.options.styleMap[marker];
            console.log(styleConfig);
            return new ol.style.Style({
                image: new ol.style.Icon({
                    anchor: [0.5, 46],
                    anchorXUnits: 'fraction',
                    anchorYUnits: 'pixels',
                    src: styleConfig.imagePath,
                }),
            });
            //var style = null;
            if (!!this.configuration.styleMap[marker].externalGraphic) {
                style = this.configuration.styleMap[marker];
                style.externalGraphic = style.externalGraphic.replace(Mapbender.configuration.application.urls.asset, '');
                style.externalGraphic = Mapbender.configuration.application.urls.asset + style.externalGraphic;
                return this.configuration.styleMap[marker];
            }
            var iconName = marker + 'Icon';
            style = {
                    graphicWidth: this.options.routingIcons.width,
                    graphicHeight: this.options.routingIcons.height,
                    graphicXOffset: this.options.routingIcons.xoffset,
                    graphicYOffset: this.options.routingIcons.yoffset
                };
            style.externalGraphic = Mapbender.configuration.application.urls.asset + this.options.routingIcons.images[iconName];

            return style;
        },

        /**
         * Clears every input that was made by the user and resets routing points
         * (routing geometry, routing marker, clears routing point array,
         * removes intermediate input fields, clears input fields)
         * @returns {boolean}
         * @private
         */
        _clearRoute: function() {
            // var self = this;
            if (this.routingLayer !== null) this.routingLayer.removeAllFeatures();
            if (this.markerLayer !== null) this.markerLayer.removeAllFeatures();
            this._emptyPoints();
            $('.mb-routing-location-points > .intermediate', this.element).remove();
            $(".mb-routing-info", this.element).hide().html('');
            return true;
        },

        _isInputValid: function() {
            var isValid = true;
            $.each($(this).find('input.input'), function(index, element){
                isValid = isValid && ($(element).val() !== '');
                console.log(isValid);
            });
            return isValid;
        },

        _autoSubmit: function() {
            var self = this;
            $('.mb-routing-location-points', this.element).change(function() {
                if (self._isInputValid()) {
                    self._getRoute();
                }
            });
        },

        // TODO implement "drag marker on map to define start point"-feature
        _dragStartMarker: function(){
            console.log('drag started');
        },
        //_destroy: $.noop

        /**
         * ReversGeocoding
         * @param feature
         * @returns {boolean} true|false and change ol2.feature(val|data)
         * @private
         */
        _revGeocode: function (feature){
            var self = this;

            if (self.reverseGeocoding) {
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

        /**
         *
         * @param coordinate
         * @returns {*}
         * @private
         */
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

        /**
         *  close Popup dialog
         */
        close: function() {
            if (this.popup) {
                this.element.hide().appendTo($('body'));
                if (this.popup.$element) {
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.deactivate();
            this.callback ? this.callback.call() : this.callback = null;
        },


        /**
         * Validation of GPS-Coordinates
         * @param coordinates
         * @returns {boolean}
         * @private
         */
        _isValidGPSCoordinates:function(coordinates){
            // Breite, Länge = latitude, longitude = lat, lon
            var args = coordinates.split(/[\s,;]+/);
            var lat = new RegExp(/^(-?[1-8]?\d(?:\.\d{1,18})?|90(?:\.0{1,18})?)$/);
            var lon = new RegExp(/^(-?(?:1[0-7]|[1-9])?\d(?:\.\d{1,18})?|180(?:\.0{1,18})?)$/);

            if (isNaN(args[0]) || isNaN(args[1])) {
                return false;
            }

            return lat.test(args[0].trim()) === true && lon.test(args[1].trim()) === true;
        },

        /**
         * Does error handling for search request
         * @param response
         * @private
         */
        _searchErrorHandling: function(response) {
            var errorDiv = $('> .mb-routing-error', this.element);

            if (response.apiMessage) {
                var tmpErrors = response.apiMessage;
                var messageDetails = '';

                if (response.messageDetails > 0) {
                    messageDetails = ' Details: ' + response.messageDetails;
                }

                console.error('SearchActionError: ' + tmpErrors + messageDetails);
                errorDiv.html(Mapbender.trans('mb.routing.exception.main.general'));
            }
        },

        /**
         *  Create air line Feature between input point and snapped waypoint
         * @param response
         * @param style
         * @returns {Array}
         * @private
         */
        _getAirLineFeature: function (response, style) {
            var self = this;
            var points = self._getSerializedPoints();
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

        /**
         * Checks if a string exists as a street name in the response
         * @param feature | feature with geometry (X,Y)
         * @param response | response
         * @returns {string}
         * @private
         */
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
        },

        spinner: function(){
            var $getRouteInput = $('span.routing-icon-start-routing');
            this.activate = function(){
                $getRouteInput.addClass('spinner').parent().prop('disabled', true);
            };
            this.deactivate = function(){
                $getRouteInput.removeClass('spinner').parent().prop('disabled', false);
            };
        }
    });
})(jQuery);

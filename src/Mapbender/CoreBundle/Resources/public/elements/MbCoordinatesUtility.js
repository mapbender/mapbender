(function() {

    class MbCoordinatesUtility extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.mapClickActive = false;
            this.isPopUpDialog = false;
            this.callback = null;
            this.mbMap = null;
            this.highlightLayer = null;
            this.currentMapCoordinate = null;
            this.transformedCoordinate = null;
            this.lon = null;
            this.lat = null;

            this.DECIMAL_ANGULAR = 6;
            this.DECIMAL_METRIC = 2;
            this.STRING_SEPARATOR = ' ';

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            });
        }

        /**
         * @private
         */
        _setup() {
            this.highlightLayer = window.Mapbender.vectorLayerPool.getElementLayer(this, 0);
            this.isPopUpDialog = Mapbender.ElementUtil.checkDialogMode(this.$element);

            this._initializeMissingSrsDefinitions(this.options.srsList);
            this._setupButtons();
            this._setupSrsDropdown();
            this._setupEventListeners();

            $('select', this.$element.trigger('change'));
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        /**
         * Initialize missing SRS definitions
         * @param srsList
         * @private
         */
        _initializeMissingSrsDefinitions(srsList) {
            if (!srsList || !srsList.length) {
                return;
            }
            srsList.map(function (srs) {
                if (window.proj4 && (typeof proj4.defs[srs.name] === "undefined")) {
                    proj4.defs(srs.name, srs.definition);
                }
                if (window.Proj4js && (typeof Proj4js.defs[srs.name] === "undefined")) {
                    Proj4js.defs[srs.name] = srs.definition;
                }
            });
            if (window.proj4 && (((window.ol || {}).proj || {}).proj4 || {}).register) {
                ol.proj.proj4.register(window.proj4);
            }
        }

        /**
         * Setup buttons
         * @private
         */
        _setupButtons() {
            var self = this;
            $('.-fn-copy-clipboard', self.$element).on('click', this._copyToClipboard);
            $('.center-map', self.$element).on('click', $.proxy(self._centerMap, self));

            if (!self.isPopUpDialog) {
                var coordinateSearchButton = $('.coordinate-search', this.$element);
                coordinateSearchButton.on('click', function () {
                    var isActive = $(this).hasClass('active');
                    if (isActive) {
                        self.deactivate();
                        this.blur();
                    } else {
                        self.activate();
                    }
                });
                coordinateSearchButton.removeClass('hidden');
            }
        }

        /**
         * Create SRS dropdown
         * @private
         */
        _setupSrsDropdown() {
            var dropdown = $('select.srs', this.$element);
            if (dropdown.children().length === 0) {
                var srsList = this._getDropdownSrsList();
                if (!srsList.length) {
                    Mapbender.error(Mapbender.trans("mb.core.coordinatesutility.widget.error.noSrs"));
                    return;
                }
                dropdown.append(srsList.map(function (srs) {
                    return $('<option>').val(srs.name).text(srs.title || srs.name);
                }));
            }
            var $wrapper = dropdown.parent('.dropdown');
            if ($wrapper.length && window.initDropdown) {
                window.initDropdown.call($('.dropdown', this.$element));
            }
        }

        /**
         * Collect dropdown SRS list
         * @private
         */
        _getDropdownSrsList() {
            var srsList = (this.options.srsList || []).slice();
            if (this.options.addMapSrsList) {
                var mapSrs = this.mbMap.getAllSrs();
                var srsNames = srsList.map(function (srs) { return srs.name; });
                mapSrs.forEach(function (srs) {
                    if (srsNames.indexOf(srs.name) === -1) {
                        srsList.push(srs);
                    }
                });
            }
            return srsList;
        }

        /**
         * Validate SRS
         * @param srs
         * @private
         */
        _isValidSRS(srs) {
            if (window.proj4) {
                return typeof proj4.defs[srs] !== "undefined";
            } else if (window.Proj4js) {
                return typeof Proj4js.defs[srs] !== "undefined";
            } else {
                throw new Error("Missing proj4js library");
            }
        }

        /**
         * Setup events
         * @private
         */
        _setupEventListeners() {
            var self = this;
            $(document).on('mbmapsrschanged', $.proxy(self._resetFields, self));
            $('select.srs', this.$element).on('change', function () {
                self._recalculateDisplayCoordinate($(this).val());
            });
            $('input.input-coordinate', self.$element).on('change', $.proxy(self._transformCoordinateToMapSrs, self));
            this.mbMap.element.on('mbmapclick', function (event, data) {
                self._mapClick(event, data);
            });
        }

        /** Popup handling */
        popup() {
            var self = this;
            if (!self.popupWindow || !self.popupWindow.$element) {
                self.popupWindow = new Mapbender.Popup({
                    title: this.$element.attr('data-title'),
                    draggable: true,
                    resizable: true,
                    modal: false,
                    closeOnESC: false,
                    detachOnClose: false,
                    content: this.$element,
                    width: 450,
                    height: 400,
                    buttons: {}
                });
                self.popupWindow.$element.on('close', function () { self.close(); });
            }
            self.popupWindow.$element.show();
            self.popupWindow.focus();
        }

        open(callback) {
            this.callback = callback;
            this.popup();
            this.activate();
        }

        close() {
            if (this.popupWindow && this.popupWindow.$element) {
                this.popupWindow.$element.hide();
            }
            if (this.callback) {
                this.callback.call();
                this.callback = null;
            }
            this.deactivate();
            this._resetFields();
        }

        activate() {
            this.mbMap.map.element.addClass('crosshair');
            Mapbender.vectorLayerPool.showElementLayers(this);
            $('.coordinate-search', this.$element).addClass('active');
            this.mapClickActive = true;
        }

        deactivate() {
            this.mbMap.map.element.removeClass('crosshair');
            Mapbender.vectorLayerPool.hideElementLayers(this);
            $('.coordinate-search', this.$element).removeClass('active');
            this.mapClickActive = false;
        }

        // Sidepane API
        reveal() {
            this.activate();
            this._showFeature();
        }
        hide() {
            this.deactivate();
            this._removeFeature();
        }

        /** Map click */
        _mapClick(event, data) {
            if (!this.mapClickActive) { return; }
            event.stopPropagation();
            var x = this.lon = data.coordinate[0];
            var y = this.lat = data.coordinate[1];
            var mapSrs = this.mbMap.getModel().getCurrentProjectionCode();
            this.currentMapCoordinate = this._formatOutputString(x, y, mapSrs);
            var selectedSrs = $('select.srs', this.$element).val();
            if (selectedSrs) {
                if (selectedSrs !== mapSrs) {
                    var transformed = this._transformCoordinate(x, y, selectedSrs, mapSrs);
                    this.transformedCoordinate = this._formatOutputString(transformed.x, transformed.y, selectedSrs);
                } else {
                    this.transformedCoordinate = this.currentMapCoordinate;
                }
            }
            this._updateFields();
            this._showFeature();
        }

        /** Coordinate transform */
        _transformCoordinate(x, y, targetSrs, sourceSrs) {
            var sourceSrs_ = sourceSrs || this.mbMap.getModel().getCurrentProjectionCode();
            if (window.proj4) {
                var fromProj = proj4.Proj(sourceSrs_);
                var toProj = proj4.Proj(targetSrs);
                var transformedCoordinates = proj4.transform(fromProj, toProj, [x, y]);
                return { x: transformedCoordinates.x, y: transformedCoordinates.y };
            } else if (window.OpenLayers && window.OpenLayers.LonLat) {
                var lonlat = new OpenLayers.LonLat(x, y).transform(sourceSrs_, targetSrs);
                return { x: lonlat.lon, y: lonlat.lat };
            } else {
                throw new Error("Cannot transform");
            }
        }

        _formatOutputString(x, y, srsCode) {
            var decimals = (this.mbMap.getModel().getProjectionUnitsPerMeter(srsCode) > 0.25) ? this.DECIMAL_METRIC : this.DECIMAL_ANGULAR;
            return x.toFixed(decimals) + this.STRING_SEPARATOR + y.toFixed(decimals);
        }

        _updateFields() {
            $('input.map-coordinate', this.$element).val(this.currentMapCoordinate);
            $('input.input-coordinate', this.$element).val(this.transformedCoordinate);
        }

        _resetFields() {
            this.currentMapCoordinate = null;
            this.transformedCoordinate = null;
            this.lon = null;
            this.lat = null;
            $('input.map-coordinate', this.$element).val('');
            $('input.input-coordinate', this.$element).val('');
            this._removeFeature();
        }

        _recalculateDisplayCoordinate(selectedSrs) {
            if (!selectedSrs) { console.error('No srs'); return; }
            if (null !== this.lon && null !== this.lat) {
                var mapSrs = this.mbMap.getModel().getCurrentProjectionCode();
                if (mapSrs !== selectedSrs) {
                    var transformed = this._transformCoordinate(this.lon, this.lat, selectedSrs, mapSrs);
                    this.transformedCoordinate = this._formatOutputString(transformed.x, transformed.y, selectedSrs);
                } else {
                    this.transformedCoordinate = this._formatOutputString(this.lon, this.lat, selectedSrs);
                }
            }
            this._updateFields();
        }

        _showFeature() {
            this._removeFeature();
            this.highlightLayer.addMarker(this.lon, this.lat);
        }

        _removeFeature() {
            this.highlightLayer.clear();
        }

        _copyToClipboard(e) {
            $('input', $(this).parent()).select();
            document.execCommand('copy');
        }

        _centerMap() {
            if (null === this.lon || null === this.lat) { return; }
            if (this._areCoordinatesValid(this.lon, this.lat)) {
                this._showFeature();
                this.mbMap.getModel().centerXy(this.lon, this.lat, { zoom: this.options.zoomlevel });
            } else {
                Mapbender.error(Mapbender.trans('mb.core.coordinatesutility.widget.error.invalidCoordinates'));
            }
        }

        _areCoordinatesValid(x, y) {
            if (!$.isNumeric(x) || !$.isNumeric(y)) { return false; }
            var mapExtentArray = this.mbMap.getModel().getMaxExtentArray();
            return (x >= mapExtentArray[0] && x <= mapExtentArray[2] && y >= mapExtentArray[1] && y <= mapExtentArray[3]);
        }

        _transformCoordinateToMapSrs() {
            var selectedSrs = $('select.srs', this.$element).val();
            var inputCoordinates = $('input.input-coordinate', this.$element).val();
            inputCoordinates = inputCoordinates.replace(/,/g, '.');
            var inputCoordinatesArray = inputCoordinates.split(/ \s*/);
            var lat = parseFloat(inputCoordinatesArray.pop());
            var lon = parseFloat(inputCoordinatesArray.pop());
            var mapProjection = this.mbMap.getModel().getCurrentProjectionCode();
            var transformed = this._transformCoordinate(lon, lat, mapProjection, selectedSrs);
            this.lon = transformed.x;
            this.lat = transformed.y;
            if (this._areCoordinatesValid(transformed.x, transformed.y)) {
                if (selectedSrs !== mapProjection) {
                    this.currentMapCoordinate = this._formatOutputString(transformed.x, transformed.y, mapProjection);
                } else {
                    this.currentMapCoordinate = inputCoordinates;
                }
                this.transformedCoordinate = inputCoordinates;
                this._updateFields();
                this._showFeature();
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCoordinatesUtility = MbCoordinatesUtility;
})();

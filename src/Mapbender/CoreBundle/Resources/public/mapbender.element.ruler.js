(function ($) {

    $.widget("mapbender.mbRuler", {
        options: {
            type: 'line',
            help: '',
            precision: 'auto',
            fillColor: 'rgba(255,255,255,0.2)',
            strokeColor: '#3399CC',
            strokeWidth: 2,
            strokeWidthWhileDrawing: 3,
            fontColor: '#000000',
            fontSize: 12,
        },
        control: null,
        segments: null,
        total: null,
        container: null,
        popup: null,
        layer: null,
        mapModel: null,
        isDialog: true,

        _create: function () {
            var self = this;
            if (this.options.type !== 'line' && this.options.type !== 'area' && this.options.type !== 'both') {
                throw Mapbender.trans("mb.core.ruler.create_error");
            }
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self._setup(mbMap);
            }, function () {
                Mapbender.checkTarget('mbRuler');
            });
        },
        _createControl: function () {
            const source = this.layer.getNativeLayer().getSource();
            const self = this;

            this.drawStyle = Mapbender.StyleUtil.createDrawStyle(
                this.options.strokeColor,
                this.options.fillColor,
                this.options.strokeWidthWhileDrawing,
                this.options.fontSize,
                this.options.fontColor
            );
            this.drawCompleteStyle = this.drawStyle.clone();
            this.drawCompleteStyle.setStroke(new ol.style.Stroke({
                width: this.options.strokeWidth,
                color: this.options.strokeColor,
            }));

            this.layer.getNativeLayer().setStyle(function (feature) {
                return this._getStyle(feature, true);
            }.bind(this));

            const control = new ol.interaction.Draw({
                type: this.options.type === 'line' ? 'LineString' : 'Polygon',
                source: source,
                stopClick: true,
                style: this._getStyle.bind(this),
            });
            control.on('drawstart', function (event) {
                self._reset();
                source.clear();
                /** @var {ol.Feature} */
                var feature = event.feature;
                var geometry = feature.getGeometry();
                var nVertices = geometry.getFlatCoordinates().length;
                geometry.on('change', function () {
                    var nVerticesNow = geometry.getFlatCoordinates().length;
                    if (nVerticesNow === nVertices) {
                        // geometry change event does not have a .feature attribute like drawend, shim it
                        self._handleModify({feature: feature});
                    } else {
                        // geometry change event does not have a .feature attribute like drawend, shim it
                        self._handlePartial({feature: feature});
                        nVertices = nVerticesNow;
                    }
                });
            });
            control.on('drawend', function (event) {
                self._handleFinal({feature: event.feature});
            });
            return control;
        },
        _getStyle(feature, useFinishedStyle) {
            const style = useFinishedStyle === true ? this.drawCompleteStyle : this.drawStyle;
            if (this.options.type === 'area') {
                const measure = this._getMeasureFromEvent({feature: feature});
                style.getText().setText(measure);
            }
            return style;
        },
        _setup: function (mbMap) {
            this.isDialog = Mapbender.ElementUtil.checkDialogMode(this.element);
            this.mapModel = mbMap.getModel();
            this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            this.createContentContainer();
            this.control = this._createControl();

            $(document).bind('mbmapsrschanged', $.proxy(this._mapSrsChanged, this));

            this._trigger('ready');
        },
        createContentContainer: function() {
            this.container = $('<div/>');
            this.total = $('<div/>').addClass('total-value').css({'font-weight': 'bold'});
            this.segments = $('<ul/>');
            this.segments.append();
            if (this.options.type === 'both') {
                const $buttonContainer = $(document.createElement("div")).attr("class", "mb-ruler__radiobuttons");
                $buttonContainer.append(this.createRadioButton("line", true));
                $buttonContainer.append(this.createRadioButton("area"));
                this.container.append($buttonContainer);
                this.options.type = "line";
            }
            if (this.options.help) {
                const help = $('<p/>').text(Mapbender.trans(this.options.help));
                this.container.append(help);
            }
            this.container.append(this.total);
            this.container.append(this.segments);
        },
        createRadioButton: function (type, checked) {
            let dataTest = (type == 'line') ? 'mb-ruler-rb-line' : 'mb-ruler-rb-area';
            const radioLine = $(document.createElement("input"))
                .attr("type", "radio")
                .attr("name", "draw_type")
                .attr('data-test', dataTest)
                .attr("checked", checked)
                .on('click', () => {
                    if (this.options.type === type) return;
                    this.options.type = type;
                    this._reset();
                    this.mapModel.olMap.removeInteraction(this.control);
                    this.control = this._createControl();
                    this.mapModel.olMap.addInteraction(this.control);
                });
            return $("<label />").append(radioLine).append(Mapbender.trans("mb.core.ruler.tag." + type));
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function (callback) {
            this.activate(callback);
        },
        _toggleControl: function (state) {
            if (state) {
                this.mapModel.olMap.addInteraction(this.control);
                this.control.setActive(true);
                this.layer.clear();
                this.layer.show();
            } else {
                this.control.setActive(false);
                this.mapModel.olMap.removeInteraction(this.control);
                this.layer.hide();
            }
        },
        reveal: function () {
            this.activate();
        },
        hide: function () {
            this.deactivate();
        },
        activate: function (callback) {
            this.callback = callback ? callback : null;
            this._toggleControl(true);

            this._reset();
            if (this.isDialog) {
                this.showPopup();
            } else {
                this.element.append(this.container);
            }
        },
        showPopup: function () {
            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title: this.element.attr('data-title'),
                    modal: false,
                    draggable: true,
                    resizable: true,
                    closeOnESC: true,
                    destroyOnClose: true,
                    content: this.container,
                    width: 300,
                    height: 300,
                    buttons: [
                        {
                            label: Mapbender.trans("mb.actions.close"),
                            cssClass: 'btn btn-sm btn-light popupClose'
                        }
                    ]
                });
                this.popup.$element.on('close', $.proxy(this.deactivate, this));
            } else {
                this.popup.open();
            }
        },
        deactivate: function () {
            this.container.detach();
            this._toggleControl(false);
            if (this.popup && this.popup.$element) {
                this.popup.destroy();
            }
            this.popup = null;
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
        },
        _mapSrsChanged: function (event, srs) {
            if (this.control) {
                this._reset();
            }
        },
        _reset: function () {
            $('>li', this.segments).remove();
            this.total.text('');
        },
        _handleModify: function (event) {
            var measure = this._getMeasureFromEvent(event);
            this._updateTotal(measure, event);
            // Reveal the previously hidden segment measure if it's now different from total
            var $previous = $('>li', this.segments).first();
            if ($previous.text() !== measure) {
                $previous.show();
            }
        },
        _handlePartial: function (event) {
            if (this.options.type === 'area') {
                this._handleFinal(event);
                return;
            }
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                return;
            }
            this._updateTotal(measure, event);
            var measureElement = $('<li/>');
            measureElement.text(measure);
            // initially hide segment entry identical to current total
            measureElement.hide();
            this.segments.prepend(measureElement);
        },
        _handleFinal: function (event) {
            var measure = this._getMeasureFromEvent(event);
            this._updateTotal(measure, event);
        },
        _updateTotal: function (measure, event) {
            this.total.text(measure);
            if (measure && this.options.type === 'area') {
                var feature = event.feature || event.object.handler.polygon;
            }
        },
        _getMeasureFromEvent: function (event) {
            var measure;
            if (!event.measure && event.feature) {
                measure = this._calculateFeatureSize(event.feature, this.options.type);
            } else {
                measure = event.measure;
            }
            if (!measure) {
                return '';
            }
            return this._formatMeasure(measure);
        },
        _calculateFeatureSize: function (feature, type) {
            /** @type {ol.geom.Geometry} */
            var geometry = feature.getGeometry();
            var calcOptions = {
                projection: this.mapModel.getCurrentProjectionCode()
            };
            switch (type) {
                case 'line':
                    return ol.sphere.getLength(geometry, calcOptions);
                default:
                    console.warn("Unsupported geometry type in measure calculation", type, feature);
                // fall through to area
                case 'area':
                    return ol.sphere.getArea(geometry, calcOptions);
            }
        },

        _formatMeasure: function (value) {
            if (!value || value < 0.00001) return '';

            var scale = 1;
            var unit;
            if (this.options.type === 'area') {
                if (value >= 10000000) {
                    scale = 1000000;
                    unit = 'km²';
                } else {
                    unit = 'm²';
                }
            } else {
                if (value > 10000) {
                    scale = 1000;
                    unit = 'km';
                } else {
                    unit = 'm';
                }
            }
            let precision = parseInt(this.options.precision);

            if (this.options.precision === 'auto') {
                precision = 3;
                if (value / scale > 10) precision = 2;
                if (value / scale > 100) precision = 1;
                if (value / scale > 1000) precision = 0;
            }

            const localeString = (value / scale).toLocaleString(undefined, {
                minimumFractionDigits: precision,
                maximumFractionDigits: precision
            });
            return localeString + ' ' + unit;
        }
    });

})(jQuery);

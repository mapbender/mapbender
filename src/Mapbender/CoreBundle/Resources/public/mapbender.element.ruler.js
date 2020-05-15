(function($){

    $.widget("mapbender.mbRuler", {
        options: {
            target: null,
            immediate: false,
            type: 'line',
            precision: 2
        },
        control: null,
        segments: null,
        total: null,
        container: null,
        popup: null,
        mapModel: null,

        _create: function(){
            var self = this;
            if(this.options.type !== 'line' && this.options.type !== 'area'){
                throw Mapbender.trans("mb.core.ruler.create_error");
            }
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbRuler", self.options.target);
            });
        },
        _createControl4: function() {
            var source = Mapbender.vectorLayerPool.getElementLayer(this, 0).getNativeLayer().getSource();
            var controlOptions = {
                type: this.options.type === 'line' ? 'LineString' : 'Polygon',
                source: source
            };
            var self = this;
            var control = new ol.interaction.Draw(controlOptions);
            control.on('drawstart', function(event) {
                self._reset();
                source.clear();
                var feature = event.feature;
                var geometry = feature.getGeometry();
                var nVertices = geometry.getFlatCoordinates().length;
                geometry.on('change', function() {
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
            control.on('drawend', function(event) {
                self._handleFinal(event);
            });
            return control;
        },
        _createControl: function() {
            var nVertices = 1;
            var self = this;
            var handlerClass, validateEventGeometry;
            if (this.options.type === 'area') {
                handlerClass = OpenLayers.Handler.Polygon;
                validateEventGeometry = function(event) {
                    // OpenLayers 2 Polygon Handler can create degenerate linear rings with too few components, and calculate a (very
                    // small) area for them. Ignore these cases.
                    return event.geometry.components[0].components.length >= 4;
                }
            } else {
                handlerClass = OpenLayers.Handler.Path;
                validateEventGeometry = function(event) {
                    return event.geometry.components.length >= 2;
                }
            }

            var control = new OpenLayers.Control.Measure(handlerClass, {
                persist: true,
                immediate: !!this.options.immediate,
                displaySystemUnits: {
                    metric: ['m']
                },
                geodesic: true
            });

            control.events.on({
                'scope': this,
                'measure': function(event) {
                    self._handleFinal(event);
                },
                'measurepartial': function(event) {
                    if (!validateEventGeometry(event)) {
                        return;
                    }
                    var nVerticesNow = event.geometry.components.length;
                    if (nVerticesNow <= 2) {
                        self._reset();
                    }
                    if (nVerticesNow !== nVertices) {
                        nVertices = nVerticesNow;
                        return self._handlePartial(event);
                    } else {
                        return self._handleModify(event);
                    }
                }
            });

            return control;
        },
        _setup: function(mbMap) {
            this.mapModel = mbMap.getModel();
            if (Mapbender.mapEngine.code === 'ol2') {
                this.control = this._createControl();
            } else {
                this.control = this._createControl4();
            }
            this.container = $('<div/>');
            this.total = $('<div/>').appendTo(this.container);
            this.segments = $('<ul/>').appendTo(this.container);

            $(document).bind('mbmapsrschanged', $.proxy(this._mapSrsChanged, this));
            
            this._trigger('ready');
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },
        _toggleControl: function(state) {
            if (Mapbender.mapEngine.code === 'ol2') {
                if (state) {
                    this.mapModel.olMap.addControl(this.control);
                    this.control.activate();
                } else {
                    this.control.deactivate();
                    this.mapModel.olMap.removeControl(this.control);
                }
            } else {
                var elementLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
                if (state) {
                    this.mapModel.olMap.addInteraction(this.control);
                    this.control.setActive(true);
                    elementLayer.clear();
                    elementLayer.show();
                } else {
                    this.control.setActive(false);
                    this.mapModel.olMap.removeInteraction(this.control);
                    elementLayer.hide();
                }
            }
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            this._toggleControl(true);

            this._reset();
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: false,
                    draggable: true,
                    resizable: true,
                    closeOnESC: true,
                    destroyOnClose: true,
                    content: self.container,
                    width: 300,
                    height: 300,
                    buttons: {
                        'ok': {
                            label: Mapbender.trans("mb.core.ruler.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function(){
                                self.deactivate();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.deactivate, this));
            }else{
                this.popup.open("");
            }
        },
        deactivate: function(){
            this.container.detach();
            this._toggleControl(false);
            $("#linerulerButton, #arearulerButton").parent().removeClass("toolBarItemActive");
            if(this.popup && this.popup.$element){
                this.popup.destroy();
            }
            this.popup = null;
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
        },
        _mapSrsChanged: function(event, srs){
            if (this.control) {
                this._reset();
            }
        },
        _reset: function() {
            this.segments.empty();
            this.total.empty();
            this.segments.append('<li/>');
        },
        _handleModify: function(event){
            if (this.options.immediate) {
                this._handleFinal(event);
            }
        },
        _handlePartial: function(event) {
            if (this.options.type === 'area') {
                this._handleFinal(event);
                return;
            }
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                return;
            }
            var recentSegments = this.segments.children('li');
            this.total.empty().append($('<b>').text(measure));
            for (var i = 0; i < recentSegments.length; ++i) {
                var recentSegment = recentSegments.eq(i);
                var segmentText = recentSegment.text();
                if (segmentText === measure) {
                    recentSegment.hide();
                } else {
                    recentSegment.show();
                    break;
                }
            }
            var measureElement = $('<li/>');
            measureElement.text(measure);
            measureElement.hide();
            this.segments.prepend(measureElement);
        },
        _handleFinal: function(event){
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                return;
            }
            if (this.options.type === 'area'){
                this._updateAreaLabel(event.feature, measure);
                this.segments.empty();
            }
            var mostRecent = this.segments.children('li').first();
            if (mostRecent.length && measure === mostRecent.text()) {
                // remove first text entry node, with identical text to final measure
                mostRecent.remove();
            }
            this.total.empty().append($('<b>').text(measure));
        },
        _getMeasureFromEvent: function(event) {
            var measure;
            if (!event.measure && event.feature) {
                measure = this._calculateFeatureSizeOl4(event.feature, this.options.type);
            } else {
                measure = event.measure;
            }
            if (!measure) {
                return null;
            }
            return this._formatMeasure(measure);
        },
        _calculateFeatureSizeOl4: function(feature, type) {
            /** @type {ol.geom.Geometry} */
            var geometry = feature.getGeometry();
            var calcOptions = {
                projection: this.mapModel.getCurrentProjectionCode()
            };
            switch (type) {
                case 'line':
                    return ol.Sphere.getLength(geometry, calcOptions);
                default:
                    console.warn("Unsupported geometry type in measure calculation", type, feature);
                    // fall through to area
                case 'area':
                    return ol.Sphere.getArea(geometry, calcOptions);
            }
        },
        /**
         * @param {ol.Feature} feature
         * @param {String} text
         * @private
         */
        _updateAreaLabel: function(feature, text) {
            if (Mapbender.mapEngine.code === 'ol2') {
                // @todo
            } else {
                var style = feature.getStyle();
                if (!style) {
                    // grab current layer default style and bind it to the feature
                    var styleFn = Mapbender.vectorLayerPool.getElementLayer(this, 0).getNativeLayer().getStyleFunction();
                    style = styleFn(feature)[0];
                    feature.setStyle(style);
                }
                // Style object may start with null text property, create one
                if (!style.getText()) {
                    style.setText(new ol.style.Text());
                }
                // actual label update
                style.getText().setText(text);
            }
        },
        _formatMeasure: function(value) {
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
            return [(value / scale).toFixed(this.options.precision), unit].join('');
        }
    });

})(jQuery);

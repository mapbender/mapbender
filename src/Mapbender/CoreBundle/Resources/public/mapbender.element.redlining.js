(function($){

    $.widget("mapbender.mbRedlining", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            display_type: 'dialog',
            auto_activate: false,
            deactivate_on_close: true,
            geometrytypes: ['point', 'line', 'polygon', 'rectangle', 'text'],
            paintstyles: {
                'strokeColor': '#ff0000',
                'fillColor': '#ff0000',
                'strokeWidth': '3'
            }
        },
        mbMap: null,
        map: null,
        layer: null,
        activeControl: null,
        geomCounter: 0,
        rowTemplate: null,
        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbRedlining", self.options.target);
            });
        },
        _setup: function(){
            var $geomTable = $('.geometry-table', this.element);
            // @todo: remove direct access to OpenLayers 2 map
            this.map = this.mbMap.map.olMap;
            this.rowTemplate = $('tr', $geomTable).remove();
            if(this.options.auto_activate || this.options.display_type === 'element'){
                this.activate();
            }
            $geomTable.on('click', '.geometry-remove', $.proxy(this._removeFromGeomList, this));
            $geomTable.on('click', '.geometry-edit', $.proxy(this._modifyFeature, this));
            $geomTable.on('click', '.geometry-zoom', $.proxy(this._zoomToFeature, this));
            var self = this;
            $('.redlining-tool', this.element).on('click', function() {
                return self._onToolButtonClick($(this));
            });

            this.setupMapEventListeners();

            this._trigger('ready');
        },
        setupMapEventListeners: function() {
            $(document).on('mbmapsourceadded', this._moveLayerToLayerStackTop.bind(this));
            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
        },
        defaultAction: function(callback){
            this.activate(callback);
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            if (!this.layer) {
                var defaultStyle = new OpenLayers.Style($.extend({}, OpenLayers.Feature.Vector.style["default"], this.options.paintstyles));
                var styleMap = new OpenLayers.StyleMap({'default': defaultStyle}, {extendDefault: true});
                this.layer = new OpenLayers.Layer.Vector('Redlining', {styleMap: styleMap});
                this.map.addLayer(this.layer);
                this.editControl = new OpenLayers.Control.ModifyFeature(this.layer, {standalone: true, active: false});
                this.map.addControl(this.editControl);
            }
            if (this.options.display_type === 'dialog'){
                this._open();
            } else {
                this.element.removeClass('hidden');
            }
        },
        deactivate: function(){
            this._deactivateControl();
            this._endEdit(null);
            // end popup, if any
            this._close();
            if (this.options.deactivate_on_close) {
                this._removeAllFeatures();
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        // sidepane interaction, safe to use activate / deactivate unchanged
        reveal: function() {
            this.activate();
        },
        hide: function() {
            this.deactivate();
        },
        /**
         * deprecated
         * @param {array} callback
         */
        open: function(callback){
            this.activate(callback);
        },
        /**
         * deprecated
         */
        close: function(){
            this.deactivate();
        },
        _open: function(){
            var self = this;
            if(!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('data-title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 500,
                    height: 380,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans('mb.core.redlining.dialog.btn.cancel'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.deactivate();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.deactivate, this));
            } else {
                    this.popup.open(self.element);
            }
            this.element.removeClass('hidden');
        },
        _close: function(){
            if (this.popup) {
                this.element.addClass('hidden').appendTo($('body'));
                if(this.popup.$element) {
                    this.popup.destroy();
                }
                this.popup = null;
            }
        },
        _onToolButtonClick: function($button) {
            this._endEdit();
            if ($button.hasClass('active')) {
                this._deactivateControl();
            } else {
                if (this.activeControl) {
                    this._deactivateControl();
                }
                var toolName = $button.attr('name');
                var control = this._controlFactory(toolName);
                this.map.addControl(control);
                control.activate();
                this.activeControl = control;
                $button.addClass('active');
            }
            return false;
        },
        _controlFactory: function(toolName){
            var self = this;
            switch(toolName) {
                case 'point':
                    return new OpenLayers.Control.DrawFeature(this.layer,
                            OpenLayers.Handler.Point, {
                                featureAdded: function(e){
                                    self._addToGeomList(e, Mapbender.trans('mb.core.redlining.geometrytype.point'));
                                }
                            });
                case 'line':
                    return new OpenLayers.Control.DrawFeature(this.layer,
                            OpenLayers.Handler.Path, {
                                featureAdded: function(e){
                                    self._addToGeomList(e, Mapbender.trans('mb.core.redlining.geometrytype.line'));
                                }
                            });
                case 'polygon':
                    return new OpenLayers.Control.DrawFeature(this.layer,
                            OpenLayers.Handler.Polygon, {
                                handlerOptions: {
                                    handleRightClicks: false
                                },
                                featureAdded: function(e){
                                    self._addToGeomList(e, Mapbender.trans('mb.core.redlining.geometrytype.polygon'));
                                }
                            });
                case 'rectangle':
                    return new OpenLayers.Control.DrawFeature(this.layer,
                            OpenLayers.Handler.RegularPolygon, {
                                handlerOptions: {
                                    sides: 4,
                                    irregular: true,
                                    rightClick: false
                                },
                                featureAdded: function(e){
                                    self._addToGeomList(e, Mapbender.trans('mb.core.redlining.geometrytype.rectangle'));
                                }
                            });
                case 'text':
                    $('input[name=label-text]', this.element).val('');
                    $('#redlining-text-wrapper', this.element).removeClass('hidden');
                    return new OpenLayers.Control.DrawFeature(this.layer,
                            OpenLayers.Handler.Point, {
                                featureAdded: function (feature) {
                                    if ($('input[name=label-text]', self.element).val().trim() === '') {
                                        Mapbender.info(Mapbender.trans('mb.core.redlining.geometrytype.text.error.notext'));
                                        self._removeFeature(feature);
                                    } else {
                                        feature.style = self._generateTextStyle($('input[name=label-text]', self.element).val());
                                        self._addToGeomList(feature, Mapbender.trans('mb.core.redlining.geometrytype.text.label'));
                                        self.layer.redraw();
                                        $('input[name=label-text]', self.element).val('');
                                    }
                                }
                            });
            }
        },
        _removeFeature: function(feature){
            this.layer.destroyFeatures([feature]);
        },
        _removeAllFeatures: function(){
            $('.geometry-table tr', this.element).remove();
            this.layer.removeAllFeatures();
        },
        _endEdit: function(nextControl) {
            var editFeature = (this.editControl || {}).feature;
            if (this.editControl && nextControl !== this.editControl) {
                this.editControl.deactivate();
            }
            if (editFeature && editFeature.style && editFeature.style.label) {
                editFeature.style = this._setTextDefault(editFeature.style);
                editFeature.layer.redraw();
            }
        },
        _deactivateControl: function(){
            $('input[name=label-text]', this.element).off('keyup');
            if (this.activeControl !== null) {
                this.activeControl.deactivate();
                this.activeControl.destroy();
                this.map.removeControl(this.activeControl);
                this.activeControl = null;
            }
            $('#redlining-text-wrapper', this.element).addClass('hidden');
            this._deactivateButton();
        },
        _deactivateButton: function(){
            $('.redlining-tool', this.element).removeClass('active');
        },
        
        _getGeomLabel: function(feature, typeLabel, featureType){
            if(featureType === 'text') {
                return typeLabel + (feature.style && feature.style.label ? ' (' + feature.style.label + ')' : '');
            } else {
                return typeLabel + ' ' + (++this.geomCounter);
            }
        },
        _addToGeomList: function(feature, typeLabel){
            var activeTool = $('.redlining-tool.active', this.element).attr('name');
            var row = this.rowTemplate.clone();
            row.attr("data-id", feature.id);
            $('.geometry-name', row).text(this._getGeomLabel(feature, typeLabel, activeTool));
            var $geomtable = $('.geometry-table', this.element);
            $geomtable.append(row);
        },
        _removeFromGeomList: function(e){
            var $tr = $(e.target).parents("tr:first");
            var eventFeature = this.layer.getFeatureById($tr.attr('data-id'));
            if (this.editControl && this.editControl.active && this.editControl.feature === eventFeature) {
                this._endEdit(null);
            }
            this._removeFeature(eventFeature);
            $tr.remove();
        },
        _modifyFeature: function(e){
            var eventFeature = this.layer.getFeatureById($(e.target).parents("tr:first").attr('data-id'));
            this._deactivateControl();
            this._endEdit(this.editControl);
            if (eventFeature.style && eventFeature.style.label) {
                eventFeature.style = this._setTextEdit(eventFeature.style);
                $('input[name=label-text]', this.element).val(eventFeature.style.label);
                $('#redlining-text-wrapper', this.element).removeClass('hidden');
                $('input[name=label-text]', this.element).on('keyup', $.proxy(this._writeText, this, eventFeature));
            }
            this.editControl.activate();
            this.editControl.selectFeature(eventFeature);
            // This might seem redundant, but without a second activate, the first edit in the session
            // just does not work; Style changes, vertices are displayed, but you can't pull them.
            // Second call to activate() fixes this.
            this.editControl.activate();
        },
        _zoomToFeature: function(e){
            this._deactivateControl();
            var feature = this.layer.getFeatureById($(e.target).parents("tr:first").attr('data-id'));
            this.mbMap.getModel().zoomToFeature(feature);
        },
        _generateTextStyle: function(label){
            var style = OpenLayers.Util.applyDefaults(null, OpenLayers.Feature.Vector.style['default']);
            if (label) {
                style.label = label;
            }
            style.labelAlign = 'lm';
            style.labelXOffset = 10;
            style.pointRadius = 6;
            style.fillOpacity = 0.4;
            style.strokeOpacity = 1;
            style.strokeWidth = 2;
            return this._setTextDefault(style);
        },
        _setTextDefault: function(style){
            style.fillColor = style.strokeColor = style.fontColor = 'red';
            return style;
        },
        _setTextEdit: function(style){
            style.fillColor = style.strokeColor = style.fontColor = 'blue';
            return style;
        },
        
        _writeText: function(feature) {
            if (feature.style && feature.style.label) {
                var inputText = $('input[name=label-text]', this.element).val().trim();
                if (!inputText) {
                    Mapbender.info(Mapbender.trans('mb.core.redlining.geometrytype.text.error.notext'));
                } else {
                    feature.style.label = inputText;
                    var label = this._getGeomLabel(feature, Mapbender.trans('mb.core.redlining.geometrytype.text.label'), 'text');
                    $('.geometry-table tr[data-id="'+feature.id+'"] .geometry-name', this.element).text(label);
                    feature.layer.redraw();
                }
            }
        },
        /**
         * Move redlining layer on top of layer stack if a source is added, i.e. by wms loader
         * @private
         */
        _moveLayerToLayerStackTop: function(event, params) {
            this._endEdit(null);
            if (this.layer) {
                this.map.raiseLayer(this.layer, this.map.getNumLayers());
                this.map.resetLayersZIndex();
            }
        },
        _onSrsChange: function(event, data) {
            this._endEdit(null);
            this._deactivateControl();
            if (this.layer) {
                (this.layer.features || []).map(function(feature) {
                    if (feature.geometry && feature.geometry.transform) {
                        feature.geometry.transform(data.from, data.to);
                    }
                });
                this.layer.redraw();
            }
        }
    });

})(jQuery);

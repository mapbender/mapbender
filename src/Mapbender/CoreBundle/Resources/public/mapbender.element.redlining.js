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
        toolLabels: {},
        requireText_: false,
        editing_: null,
        _create: function() {
            Object.assign(this.toolLabels, {
                'point': Mapbender.trans('mb.core.redlining.geometrytype.point'),
                'line': Mapbender.trans('mb.core.redlining.geometrytype.line'),
                'polygon': Mapbender.trans('mb.core.redlining.geometrytype.polygon'),
                'rectangle': Mapbender.trans('mb.core.redlining.geometrytype.rectangle'),
                'text': Mapbender.trans('mb.core.redlining.geometrytype.text.label')
            });
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
            $geomTable.on('click', '.geometry-remove', $.proxy(this._removeFromGeomList, this));
            $geomTable.on('click', '.geometry-edit', $.proxy(this._modifyFeature, this));
            $geomTable.on('click', '.geometry-zoom', $.proxy(this._zoomToFeature, this));
            var self = this;
            $('.redlining-tool', this.element).on('click', function() {
                return self._onToolButtonClick($(this));
            });

            this.setupMapEventListeners();
            if (Mapbender.mapEngine.code === 'ol2') {
                this.layerBridge = this._createLayer(this.mbMap);
                this.layer = this.layerBridge.getNativeLayer();
                this.editControl = this._createEditControl(this.mbMap, this.layer);
            } else {
                this.layerBridge = this._createLayer4(this.mbMap);
                this.layer = this.layerBridge.getNativeLayer();
                this.editControl = this._createEditControl4(this.mbMap, this.layer);
            }

            this._trigger('ready');
            if (this.options.auto_activate || this.options.display_type === 'element') {
                this.activate();
            }
        },
        setupMapEventListeners: function() {
            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
        },
        defaultAction: function(callback){
            this.activate(callback);
        },
        _createLayer: function(mbMap) {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            var self = this;
            layerBridge.customizeStyle(Object.assign({}, this.options.paintstyles, {
                label: function(feature) {
                    return self._getFeatureAttribute(feature, 'label') || '';
                },
                labelAlign: 'lm',
                labelXOffset: 10
            }));
            layerBridge.getNativeLayer().events.on({
                sketchcomplete: this._validateText.bind(this),
                afterfeaturemodified: function() {
                    self.editing_ = null;
                }
            });
            return layerBridge;
        },
        _createLayer4: function(mbMap) {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            var self = this;
            layerBridge.customizeStyle(Object.assign({}, this.options.paintstyles, {
                label: function(feature) {
                    return self._getFeatureAttribute(feature, 'label') || '';
                },
                labelAlign: 'lm',
                labelXOffset: 10
            }));
            return layerBridge;
        },
        _createEditControl: function(mbMap, olLayer) {
            var control = new OpenLayers.Control.ModifyFeature(olLayer, {standalone: true, active: false});
            mbMap.model.olMap.addControl(control);
            return control;
        },
        _createEditControl4: function(mbMap, olLayer) {
            return null;
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            if (this.options.display_type === 'dialog'){
                this._open();
            } else {
                this.element.removeClass('hidden');
            }
            this._moveLayerToLayerStackTop();
        },
        deactivate: function(){
            this._deactivateControl();
            this._endEdit();
            // end popup, if any
            this._close();
            if (this.options.deactivate_on_close) {
                this._removeAllFeatures();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
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
        _toolRequiresLabel: function(toolName) {
            return toolName === 'text';
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
                if (this._toolRequiresLabel(toolName)) {
                    $('input[name=label-text]', this.element).val('');
                    $('#redlining-text-wrapper', this.element).removeClass('hidden');
                    this.requireText_ = true;
                } else {
                    this.requireText_ = false;
                }
                this._startDraw(toolName);
                $button.addClass('active');
            }
            return false;
        },
        _validateText: function() {
            if (this.requireText_ && !$('input[name=label-text]', this.element).val().trim()) {
                Mapbender.info(Mapbender.trans('mb.core.redlining.geometrytype.text.error.notext'));
                return false;
            } else {
                return true;
            }
        },
        _onFeatureAdded: function(toolName, feature) {
            this._setFeatureAttribute(feature, 'toolName', toolName);
            if (this._toolRequiresLabel(toolName)) {
                var textInput = $('input[name=label-text]', self.element);
                var text = textInput.val().trim();
                this._updateFeatureLabel(feature, text);
                textInput.val('');
            }
            this._addToGeomList(feature);
        },
        _startDraw: function(toolName) {
            var featureAdded = this._onFeatureAdded.bind(this, toolName);
            switch(toolName) {
                case 'point':
                case 'line':
                case 'polygon':
                case 'rectangle':
                    this.layerBridge.draw(toolName, featureAdded);
                    break;
                case 'text':
                    this._monkeyPatchLabelCondition(this.layerBridge.draw('point', featureAdded));
                    break;
                default:
                    throw new Error("No implementation for tool name " + toolName);
            }
        },
        _monkeyPatchLabelCondition: function(interaction) {
            // OpenLayers 4 only. OpenLayers 2 handles this via map-global sketchcomplete event
            // Condition cannot be set via public API after creation. So we patch the private attribute 'condition_'
            if (interaction.condition_ && !interaction.monkeyPatchedLabelCondition) {
                var self = this;
                interaction.condition_ = function(event) {
                    // invoke original default handler
                    var original = ol.events.condition.noModifierKeys(event);
                    return original && self._validateText();
                };
                interaction.monkeyPatchedLabelCondition = true;
            }
        },
        _removeFeature: function(feature){
            if (Mapbender.mapEngine.code === 'ol2') {
                this.layer.destroyFeatures([feature]);
            } else {
                this.layer.getSource().removeFeature(feature);
            }
        },
        _removeAllFeatures: function(){
            $('.geometry-table tr', this.element).remove();
            this.layerBridge.clear();
        },
        /**
         * @param {*} feature
         * @private
         * engine-specific
         */
        _startEdit: function(feature) {
            this.editControl.selectFeature(feature);
            this.editControl.activate();
            this.editing_ = feature;
        },
        _endEdit: function() {
            $('input[name=label-text]', this.element).off('keyup');
            if (this.editControl) {
                this.editControl.deactivate();
            }
            this.editing_ = null;
        },
        _deactivateControl: function(){
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
        
        _getGeomLabel: function(feature) {
            var toolName = this._getFeatureAttribute(feature, 'toolName');
            var typeLabel = this.toolLabels[toolName];
            if (this._toolRequiresLabel(toolName)) {
                var featureLabel = this._getFeatureLabel(feature);
                return typeLabel + (featureLabel && ('(' + featureLabel + ')') || '');
            } else {
                return typeLabel + ' ' + (++this.geomCounter);
            }
        },
        _addToGeomList: function(feature) {
            var row = this.rowTemplate.clone();
            row.data('feature', feature);
            $('.geometry-name', row).text(this._getGeomLabel(feature));
            var $geomtable = $('.geometry-table', this.element);
            $geomtable.append(row);
        },
        _removeFromGeomList: function(e){
            var $tr = $(e.target).closest('tr');
            var feature = $tr.data('feature');
            if (feature === this.editing_) {
                this._endEdit();
            }
            this._removeFeature(feature);
            $tr.remove();
        },
        _modifyFeature: function(e) {
            var self = this;
            var $row = $(e.target).closest('tr');
            var eventFeature = $row.data('feature');
            this._deactivateControl();
            this._endEdit();
            if (this._toolRequiresLabel(this._getFeatureAttribute(eventFeature, 'toolName'))) {
                $('input[name=label-text]', this.element).val(this._getFeatureLabel(eventFeature));
                $('#redlining-text-wrapper', this.element).removeClass('hidden');
                $('input[name=label-text]', this.element).on('keyup', function() {
                    var text = $(this).val().trim();
                    if (!text) {
                        Mapbender.info(Mapbender.trans('mb.core.redlining.geometrytype.text.error.notext'));
                    } else {
                        self._updateFeatureLabel(eventFeature, text);
                        var label = self._getGeomLabel(eventFeature);
                        $('.geometry-name', $row).text(label);
                    }
                });
            }
            this._startEdit(eventFeature);
        },
        _zoomToFeature: function(e){
            this._deactivateControl();
            var feature = $(e.target).closest('tr').data('feature');
            this.mbMap.getModel().zoomToFeature(feature);
        },
        _getFeatureLabel: function(feature) {
            return this._getFeatureAttribute(feature, 'label') || '';
        },
        _updateFeatureLabel: function(feature, label) {
            this._setFeatureAttribute(feature, 'label', label);
            // OpenLayers 2 only
            if (feature.layer) {
                feature.layer.redraw();
            }
        },
        /**
         * @param {*} feature
         * @param {String} name
         * @private
         * engine-specific
         */
        _getFeatureAttribute: function(feature, name) {
            if (Mapbender.mapEngine.code === 'ol2') {
                return feature.attributes[name];
            } else {
                return feature.get(name);
            }
        },
        /**
         * @param {*} feature
         * @param {String} name
         * @param {*} value
         * @private
         * engine-specific
         */
        _setFeatureAttribute: function(feature, name, value) {
            if (Mapbender.mapEngine.code === 'ol2') {
                feature.attributes[name] = value;
            } else {
                feature.set(name, value);
            }
        },
        /**
         * engine-specific
         */
        _moveLayerToLayerStackTop: function() {
            Mapbender.vectorLayerPool.raiseElementGroup(this);
        },
        _onSrsChange: function(event, data) {
            this._endEdit();
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

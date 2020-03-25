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
            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
        },
        defaultAction: function(callback){
            this.activate(callback);
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            if (!this.layer) {
                var labelStyles = {
                    label: '${label}',
                    labelAlign: 'lm',
                    labelXOffset: 10
                };
                var valueCallbacks = {
                    label: function(feature) {
                        return feature.attributes.label || ''
                    }
                };
                var customDefaultStyles = this.options.paintstyles;
                var styles = {};
                ['default', 'select', 'temporary'].forEach(function(intent) {
                    var styleOptions = Object.assign({}, OpenLayers.Feature.Vector.style[intent], labelStyles);
                    if (intent === 'default') {
                        Object.assign(styleOptions, customDefaultStyles);
                    }
                    styles[intent] = new OpenLayers.Style(styleOptions, {
                        context: valueCallbacks
                    });
                });
                var styleMap = new OpenLayers.StyleMap(styles, {extendDefault: true});
                this.layer = new OpenLayers.Layer.Vector('Redlining', {styleMap: styleMap});
                this.map.addLayer(this.layer);
                this.editControl = new OpenLayers.Control.ModifyFeature(this.layer, {standalone: true, active: false});
                this.map.addControl(this.editControl);
                this.layer.events.on({
                    sketchcomplete: this._validateText.bind(this)
                });
            }
            if (this.options.display_type === 'dialog'){
                this._open();
            } else {
                this.element.removeClass('hidden');
            }
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
        _onToolButtonClick: function($button) {
            this._endEdit();
            if ($button.hasClass('active')) {
                this._deactivateControl();
            } else {
                if (this.activeControl) {
                    this._deactivateControl();
                }
                var toolName = $button.attr('name');
                if (toolName === 'text') {
                    $('input[name=label-text]', this.element).val('');
                    $('#redlining-text-wrapper', this.element).removeClass('hidden');
                    this.requireText_ = true;
                } else {
                    this.requireText_ = false;
                }
                var control = this._controlFactory(toolName);
                this.map.addControl(control);
                control.activate();
                this.activeControl = control;
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
        _controlFactory: function(toolName){
            var self = this;
            function featureAdded(feature) {
                self._setFeatureAttribute(feature, 'toolName', toolName);
                self._addToGeomList(feature);
            }
            switch(toolName) {
                case 'point':
                    return new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.Point, {
                        featureAdded: featureAdded
                    });
                case 'line':
                    return new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.Path, {
                        featureAdded: featureAdded
                    });
                case 'polygon':
                    return new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.Polygon, {
                        featureAdded: featureAdded,
                        handlerOptions: {
                            handleRightClicks: false
                        }
                    });
                case 'rectangle':
                    return new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.RegularPolygon, {
                        featureAdded: featureAdded,
                        handlerOptions: {
                            sides: 4,
                            irregular: true,
                            rightClick: false
                        }
                    });
                case 'text':
                    return new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.Point, {
                        featureAdded: function(feature) {
                            var textInput = $('input[name=label-text]', self.element);
                            var text = textInput.val().trim();
                            self._updateFeatureLabel(feature, text);
                            featureAdded(feature);
                            textInput.val('');
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
        _endEdit: function() {
            $('input[name=label-text]', this.element).off('keyup');
            this.editControl.deactivate();
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
            if (toolName === 'text') {
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
            var eventFeature = $tr.data('feature');
            if (this.editControl && this.editControl.active && this.editControl.feature === eventFeature) {
                this._endEdit();
            }
            this._removeFeature(eventFeature);
            $tr.remove();
        },
        _modifyFeature: function(e) {
            var self = this;
            var $row = $(e.target).closest('tr');
            var eventFeature = $row.data('feature');
            this._deactivateControl();
            this._endEdit();
            var label = this._getFeatureLabel(eventFeature);
            if (label) {
                $('input[name=label-text]', this.element).val(label);
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
            this.editControl.activate();
            this.editControl.selectFeature(eventFeature);
            // This might seem redundant, but without a second activate, the first edit in the session
            // just does not work; Style changes, vertices are displayed, but you can't pull them.
            // Second call to activate() fixes this.
            this.editControl.activate();
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
            feature.layer.redraw();
        },
        /**
         * @param {*} feature
         * @param {String} name
         * @private
         * engine-specific
         */
        _getFeatureAttribute: function(feature, name) {
            return feature.attributes[name];
        },
        /**
         * @param {*} feature
         * @param {String} name
         * @param {*} value
         * @private
         * engine-specific
         */
        _setFeatureAttribute: function(feature, name, value) {
            feature.attributes[name] = value;
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

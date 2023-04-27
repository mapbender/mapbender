(function($){

    $.widget("mapbender.mbSketch", $.mapbender.mbDialogElement, {
        options: {
            deactivate_on_close: true,
            geometrytypes: ['point', 'line', 'polygon', 'rectangle', 'circle'],
            radiusEditing: false,
            colors: []
        },
        mbMap: null,
        layer: null,
        geomCounter: 0,
        rowTemplate: null,
        toolLabels: {},
        editing_: null,
        $labelInput_: null,
        useDialog_: false,
        editContent_: null,
        decimalSeparator_: ((0.5).toLocaleString().substring(1, 2)),
        selectedColor_: null,

        _create: function() {
            Object.assign(this.toolLabels, {
                'point': Mapbender.trans('mb.core.sketch.geometrytype.point'),
                'line': Mapbender.trans('mb.core.sketch.geometrytype.line'),
                'polygon': Mapbender.trans('mb.core.sketch.geometrytype.polygon'),
                'rectangle': Mapbender.trans('mb.core.sketch.geometrytype.rectangle'),
                'circle': Mapbender.trans('mb.core.sketch.geometrytype.circle'),
            });
            this.useDialog_ = this.checkDialogMode();
            this.editContent_ = $('.-js-edit-content', this.element).remove().removeClass('hidden').html();
            this.$labelInput_ = $('input[name="label-text"]', this.element);
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget('mbSketch');
            });
        },
        _setup: function(){
            var $geomTable = $('.geometry-table', this.element);
            this.rowTemplate = $('tr', $geomTable).remove().removeClass('hidden');
            $geomTable.on('click', '.geometry-remove', $.proxy(this._removeFromGeomList, this));
            $geomTable.on('click', '.geometry-edit', $.proxy(this._modifyFeature, this));
            $geomTable.on('click', '.geometry-zoom', $.proxy(this._zoomToFeature, this));
            var self = this;
            $('[data-tool-name]', this.element).on('click', function() {
                return self._onToolButtonClick($(this));
            });
            $('.-fn-tool-off', this.element).on('click', function() {
                self._deactivateControl();
                $(this).prop('disabled', true);
            });
            var $pallette = $('.-js-pallette-container', this.element);
            $pallette.on('click', '.color-select[data-color]', function() {
                var $btn = $(this);
                self.setColor_($btn.attr('data-color'), $btn);
            });
            this.selectedColor_ = $('.color-select', this.element).eq(0).attr('data-color') || '#ff3333';
            $('.-fn-color-customize', this.element).colorpicker({
                format: 'hex',
                input: false,
                component: false,
                align: $('.color-select', $pallette).not('.custom-color-select').length >= 2 && 'right' || 'left'
            }).on('changeColor', function(evt) {
                var color = evt.color.toString(true, 'hex');
                var $btn = $('.custom-color-select', self.element);
                $('.color-preview', $btn).css('background', color);
                $btn
                    .attr('data-color', color)
                    .prop('disabled', false)
                ;
                self.setColor_(color, $btn);
            }).one('showPicker', function() {
                self.setPickerColor_(self.selectedColor_, true);
            });

            this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            this.layer.customizeStyle({
                strokeWidth: 3,
                fillColor: function(feature) {
                    return self._getFeatureAttribute(feature, 'color') || self.selectedColor_;
                },
                strokeColor: function(feature) {
                    return self._getFeatureAttribute(feature, 'color') || self.selectedColor_;
                },
                label: function(feature) {
                    return self._getFeatureAttribute(feature, 'label') || '';
                },
                labelAlign: function(feature) {
                    if (-1 !== ['point'].indexOf(self._getFeatureAttribute(feature, 'toolName'))) {
                        return 'lm';
                    } else {
                        return 'cm';
                    }
                },
                labelXOffset: function(feature) {
                    if (-1 !== ['point'].indexOf(self._getFeatureAttribute(feature, 'toolName'))) {
                        return 10;
                    } else {
                        return 0;
                    }
                }
            });

            if (Mapbender.mapEngine.code === 'ol2') {
                // OpenLayers 2: keep reusing single edit control
                this.editControl = this._createEditControl(this.layer.getNativeLayer());
                // Native "sketchcomplete" event is OpenLayers 2 only
                this.layer.getNativeLayer().events.on({
                    afterfeaturemodified: function() {
                        self.editing_ = null;
                    }
                });
            } else {
                this.editControl = null;
            }
            this.setupMapEventListeners();
            this._trigger('ready');
            if (this.checkAutoOpen()) {
                this.activate();
            }
            this.trackLabelInput_(this.$labelInput_);
            this.trackRadiusInput_($('input[name="radius"]', this.element));
        },
        setupMapEventListeners: function() {
            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
        },
        defaultAction: function(callback){
            this.activate(callback);
        },
        _createEditControl: function(olLayer) {
            var control = new OpenLayers.Control.ModifyFeature(olLayer, {standalone: true, active: false});
            olLayer.map.addControl(control);
            return control;
        },
        activate: function(callback){
            this.callback = callback ? callback : null;
            if (this.useDialog_) {
                this._open();
            }
            Mapbender.vectorLayerPool.showElementLayers(this, true);
            this.notifyWidgetActivated();
        },
        deactivate: function() {
            this._deactivateControl();
            this._endEdit();
            // end popup, if any
            this._close();
            if (this.options.deactivate_on_close) {
                Mapbender.vectorLayerPool.hideElementLayers(this);
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            this.notifyWidgetDeactivated();
        },
        // sidepane interaction, safe to use activate / deactivate unchanged
        reveal: function() {
            this.activate();
        },
        hide: function() {
            this.deactivate();
        },
        /**
         * @deprecated
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
            if (!this.popup || !this.popup.$element) {
                var options = Object.assign(this.getPopupOptions(), {
                    content: this.element
                });
                this.popup = new Mapbender.Popup2(options);
                this.popup.$element.on('close', function() {
                    self.deactivate();
                });
            } else {
                this.popup.$element.removeClass('hidden');
                this.popup.focus();
            }
        },
        getPopupOptions: function() {
            return {
                title: Mapbender.trans(this.options.title),
                cssClass: 'sketch-dialog',
                draggable: true,
                header: true,
                modal: false,
                closeOnESC: false,
                detachOnClose: false,
                width: 500,
                height: 500,
                resizable: true,
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.close'),
                        cssClass: 'button popupClose'
                    }
                ]
            };
        },
        _close: function(){
            if (this.popup) {
                this.popup.$element.addClass('hidden');
            }
        },
        _onToolButtonClick: function($button) {
            this._endEdit();
            $('[data-tool-name]', this.element).not($button).removeClass('active');
            if ($button.hasClass('active')) {
                this._deactivateControl();
            } else {
                var toolName = $button.attr('data-tool-name');
                this.$labelInput_.prop('disabled', false);
                this._startDraw(toolName);
                $button.addClass('active');
            }
            return false;
        },
        _onFeatureAdded: function(toolName, feature) {
            this._setFeatureAttribute(feature, 'toolName', toolName);
            this._setFeatureAttribute(feature, 'color', this.selectedColor_);
            var text = this.$labelInput_.val().trim();
            this._updateFeatureLabel(feature, text);
            this.$labelInput_.val('');
            if (this.options.radiusEditing) {
                var radius = this.getFeatureRadius_(feature)
                var $radiusInput = $('input[name="radius"]', this.element);
                $radiusInput.prop('disabled', toolName !== 'circle');
                $radiusInput.val(radius !== null && radius.toLocaleString() || '');
            }
            this._addToGeomList(feature);
        },
        _startDraw: function(toolName) {
            var featureAdded = this._onFeatureAdded.bind(this, toolName);
            $('.-fn-tool-off', this.element).prop('disabled', false);
            $('input[name="radius"]', this.element).prop('disabled', true);
            switch(toolName) {
                case 'point':
                case 'line':
                case 'circle':
                case 'polygon':
                case 'rectangle':
                    this.layer.draw(toolName, featureAdded);
                    break;
                default:
                    throw new Error("No implementation for tool name " + toolName);
            }
        },
        /**
         * @param {*} feature
         * @private
         * engine-specific
         */
        _startEdit: function(feature) {
            this.editing_ = feature;
            var $row = this._getFeatureAttribute(feature, 'row');
            $('.geometry-item', this.element).not($row).removeClass('current-row');
            $row.addClass('current-row');
            var toolName = this._getFeatureAttribute(feature, 'toolName');
            var formScope;
            if (Mapbender.mapEngine.code === 'ol2') {
                this.editControl.selectFeature(feature);
                this.editControl.activate();
            } else {
                // OpenLayer 4 edit control does not support re-selecting a single feature
                // => Always create a new one
                this.editControl = new ol.interaction.Modify({
                    features: new ol.Collection([feature])
                });
                this.mbMap.getModel().olMap.addInteraction(this.editControl);
            }
            if (this.useDialog_) {
                formScope = this.element;
            } else {
                var $popoverContent = $($.parseHTML(this.editContent_));
                $('[data-toolnames]', $popoverContent).each(function() {
                    var $this = $(this);
                    var allowed = $this.attr('data-toolnames').split(',');
                    if (-1 === allowed.indexOf(toolName)) {
                        $this.remove();
                    }
                });
                formScope = $popoverContent;
                this.trackLabelInput_($('input[name="label-text"]', $popoverContent));
                this.trackRadiusInput_($('input[name="radius"]', $popoverContent));
                this._showRecordPopover($row, $popoverContent);
            }
            $('input[name="label-text"]', formScope)
                .prop('disabled', false)
                .val(this._getFeatureAttribute(feature, 'label') || '')
            ;
            if ('circle' === this._getFeatureAttribute(feature, 'toolName') && this.options.radiusEditing) {
                $('input[name="radius"]', formScope)
                    .prop('disabled', false)
                    .val((this.getFeatureRadius_(feature) || 0).toLocaleString())
                ;
            } else {
                $('input[name="radius"]', formScope).prop('disabled', true).val('');
            }
            var featureColor = this._getFeatureAttribute(feature, 'color') || this.selectedColor_;
            var $colorBtn = $('.color-select[data-color="' + featureColor + '"]', this.element);
            if ($colorBtn.length) {
                this.setColorButtonActive_($colorBtn);
            } else {
                this.setPickerColor_(featureColor, true);
            }
        },
        _endEdit: function() {
            if (this.editControl) {
                if (Mapbender.mapEngine.code === 'ol2') {
                    this.editControl.deactivate();
                } else {
                    this.mbMap.getModel().olMap.removeInteraction(this.editControl);
                    this.editControl.dispose();
                    this.editControl = null;
                }
            }
            $('.geometry-item', this.element).removeClass('current-row');
            this.editing_ = null;
        },
        _deactivateControl: function() {
            this.layer.endDraw();
            this.$labelInput_.prop('disabled', true);
            $('.-fn-tool-off', this.element).prop('disabled', true);
            $('[data-tool-name]', this.element).removeClass('active');
        },
        _getGeomLabel: function(feature) {
            var toolName = this._getFeatureAttribute(feature, 'toolName');
            var typeLabel = this.toolLabels[toolName];
            var featureLabel = this._getFeatureLabel(feature);
            if (featureLabel) {
                return typeLabel + (featureLabel && (' (' + featureLabel + ')') || '');
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
            this._setFeatureAttribute(feature, 'row', row);
        },
        _removeFromGeomList: function(e){
            var $tr = $(e.target).closest('tr');
            var feature = $tr.data('feature');
            if (feature === this.editing_) {
                this.$labelInput_.val('');
                this._endEdit();
            }
            this.layer.removeNativeFeatures([feature]);
            $tr.remove();
        },
        _modifyFeature: function(e) {
            var $row = $(e.target).closest('tr');
            var eventFeature = $row.data('feature');
            this._deactivateControl();
            this._endEdit();
            this._startEdit(eventFeature);
        },
        trackLabelInput_: function($input) {
            var self = this;
            $input.on('input', function() {
                if (self.editing_) {
                    var text = $(this).val().trim();
                    self._updateFeatureLabel(self.editing_, text);
                    var label = self._getGeomLabel(self.editing_);
                    var $row = self._getFeatureAttribute(self.editing_, 'row');
                    $('.geometry-name', $row).text(label);
                }
            });
        },
        trackRadiusInput_: function($input) {
            var self = this;
            $input.on('input', function() {
                if (self.editing_) {
                    var rawVal = $input.val() || '';
                    var radius = self.numberFromLocaleString_(rawVal);
                    if (!isNaN(radius)) {
                        self.updateFeatureRadius_(self.editing_, radius);
                    }
                }
            });
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
        _onSrsChange: function(event, data) {
            this._endEdit();
            this._deactivateControl();
            if (this.layer) {
                this.layer.retransform(data.from, data.to);
            }
        },
        _showRecordPopover: function($targetRow, $content) {
            var self = this;
            this._closePopovers();
            var $popover = $(document.createElement('div'))
                .addClass('popover bottom')
                .prepend($(document.createElement('div')).addClass('arrow'))
                .append($content)
            ;
            $('.-js-edit-content-anchor', $targetRow).append($popover);
            $popover.on('click', '.-fn-close', function() {
                $popover.remove();
                self._endEdit();
            });
        },
        _closePopovers: function() {
            $('table .popover', this.element).each(function() {
                var $other = $(this);
                var otherPromise = $other.data('deferred');
                if (otherPromise) {
                    // Reject pending promises on delete confirmation popovers
                    otherPromise.reject();
                }
                $other.remove();
            });
        },
        /**
         * @param {Object} feature
         * @returns {null|number}
         * @private
         */
        getFeatureRadius_: function(feature) {
            if ('circle' !== this._getFeatureAttribute(feature, 'toolName') || !this.options.radiusEditing) {
                return null;
            }
            var extent = feature.getGeometry().getExtent();
            var center = ol.extent.getCenter(extent);
            var upm = this.mbMap.getModel().getUnitsPerMeterAt(center);
            return (extent[2] - center[0]) / upm.h;
        },
        updateFeatureRadius_: function(feature, radius) {
            var geom = feature.getGeometry();
            var center = ol.extent.getCenter(geom.getExtent());
            var upm = this.mbMap.getModel().getUnitsPerMeterAt(center);
            var radius_ = radius * upm.h;
            geom.setRadius(radius_);
        },
        setColor_: function(color, $button) {
            this.selectedColor_ = color;
            if ($button.length) {
                this.setColorButtonActive_($button);
            }
            if (this.editing_) {
                this._setFeatureAttribute(this.editing_, 'color', color);
                // OpenLayers 2 only
                if (this.editing_.layer) {
                    this.editing_.layer.redraw();
                }
            }
        },
        setColorButtonActive_: function($button) {
            $('.-js-pallette-container .color-select', this.element).not($button).removeClass('active');
            $button.addClass('active');
        },
        setPickerColor_: function(color, activateButton) {
            $('.-fn-color-customize', this.element).colorpicker('updatePicker', color);
            var $btn = $('.custom-color-select', this.element);
            $('.color-preview', $btn).css('background', color);
            $btn
                .attr('data-color', color)
                .prop('disabled', false)
            ;
            if (activateButton) {
                this.setColorButtonActive_($btn);
            }
        },
        numberFromLocaleString_: (function() {
            var groupSeparator = ',';
            var decimalSeparator = '.';
            try {
                var parts = (1024.5).toLocaleString().split(/\d+/);
                decimalSeparator = parts[2] || parts[1];
                groupSeparator = parts[2] && parts[1];
            } catch (e) {
                // Treat as en-US (dot separates decimals, comma separates groups)
            }
            return function(localized) {
                return parseFloat(localized.replace(groupSeparator, '').replace(decimalSeparator, '.'));
            }
        })(),
        __dummy__: null
    });
})(jQuery);

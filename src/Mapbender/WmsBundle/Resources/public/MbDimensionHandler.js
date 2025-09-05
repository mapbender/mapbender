(function() {

    class MbDimensionHandler extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, function() {
                Mapbender.checkTarget('mbDimensionsHandler');
            });
        }

        _setup(mbMap) {
            this.model = mbMap.getModel();
            var $sets = $('.dimensionset[data-group][data-extent]', this.$element);
            for (var i = 0; i < $sets.length; ++i) {
                var $set = $sets.eq(i);
                var group = $set.attr('data-group').split('#');
                var extent = $set.attr('data-extent');
                var targetDimensions = (group || []).map(function(compoundId) {
                    return {
                        sourceId: compoundId.replace(/-.*/, ''),
                        dimensionName: compoundId.replace(/^.*-(\w+)-\w*$/, '$1')
                    };
                });
                this._preconfigureSources(targetDimensions, extent);
                var dimHandler = this._setupGroup(targetDimensions);
                if (dimHandler) {
                    this._initializeSlider($set, dimHandler, targetDimensions);
                } else {
                    console.error('Target dimension not found! Source deactivated or removed?', targetDimensions/*, groupConfig*/);
                }
            }
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        _setupGroup(targetDimensions) {
            for (var i = 0; i < targetDimensions.length; ++i) {
                var targetDimension = targetDimensions[i];
                var source = this.model.getSourceById(targetDimension.sourceId);
                var sourceDimensionConfig = source && this._getSourceDimensionConfig(source, targetDimension.dimensionName);
                if (sourceDimensionConfig) {
                    return Mapbender.Dimension(sourceDimensionConfig);
                }
            }
            return null;
        }

        /**
         * @param {jQuery} $set
         * @param dimension
         * @param targetDimensions
         * @private
         */
        _initializeSlider($set, dimension, targetDimensions) {
            var self = this;
            var valarea = $('.dimensionset-value', $set);
            valarea.text(dimension.getDefault());
            $('.mb-slider', $set).slider({
                min: 0,
                max: dimension.getStepsNum(),
                value: dimension.getStep(dimension.getDefault()),
                slide: function (event, ui) {
                    valarea.text(dimension.valueFromStep(ui.value));
                },
                stop: function (event, ui) {
                    for (var i = 0; i < targetDimensions.length; ++i) {
                        var source = self.model.getSourceById(targetDimensions[i].sourceId);
                        if (source) {
                            var params = {};
                            params[dimension.getOptions().__name] = dimension.valueFromStep(ui.value);
                            source.addParams(params);
                        }
                    }
                }
            });
        }

        _getSourceDimensionConfig(source, name) {
            var sourceDimensions = source && source.options.dimensions || [];
            for (var j = 0; j < sourceDimensions.length; ++j) {
                var sourceDimension = sourceDimensions[j];
                if (sourceDimension.name === name) {
                    return sourceDimension;
                }
            }
            return false;
        }

        _preconfigureSources(targetDimensions, extent) {
            for (var i = 0; i < targetDimensions.length; ++i) {
                var targetDimension = targetDimensions[i];
                var source = this.model.getSourceById(targetDimension.sourceId);
                this._preconfigureSource(source, targetDimension.dimensionName, extent);
            }
        }

        _preconfigureSource(source, dimensionName, extent) {
            var targetConfig = this._getSourceDimensionConfig(source, dimensionName);
            if (targetConfig) {
                // @todo: support original string extent format in Mapbender.Dimension
                var extentParts = extent.split('/').slice(0, 2);
                targetConfig.extent.splice(0, 2, extentParts[0], extentParts[1]);
                var dimension = Mapbender.Dimension(targetConfig);
                // Apply (newly restrained by modified range) default param value to source
                var params = {};
                params[targetConfig.__name] = dimension.getDefault();
                try {
                    source.addParams(params);
                } catch (e) {
                    // Source is not yet an object, but we made our config changes => error is safe to ignore
                }
            }
        }

        _destroy() {
            // noop (mirrors original _destroy: $.noop)
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbDimensionHandler = MbDimensionHandler;
})();

(function ($) {
    $.widget("mapbender.mbDimensionsHandler", {
        options: {
            dimensionsets: {}
        },
        model: null,
        _create: function () {
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbDimensionsHandler", self.options.target);
            });
        },
        _setup: function (mbMap) {
            this.model = mbMap.getModel();
            var dimensionUuids = Object.keys(this.options.dimensionsets);
            for (var i = 0; i < dimensionUuids.length; ++i) {
                var key = dimensionUuids[i];
                var groupConfig = this.options.dimensionsets[dimensionUuids[i]];
                var sourceIds = (groupConfig.group || []).map(function(compoundId) {
                    return compoundId.replace(/-.*$/, '');
                });
                this._preconfigureSources(sourceIds, groupConfig.dimension);
                this._setupGroup(key, sourceIds, groupConfig.dimension);
            }
            this._trigger('ready');
        },
        _setupGroup: function(key, sourceIds, settings) {
            var self = this;
            var dimension;
            for (var i = 0; i < sourceIds.length; ++i) {
                var source = this.model.getSourceById(sourceIds[i]);
                var sourceDimensionConfig = source && this._getSourceDimensionConfig(source, settings.name);
                if (sourceDimensionConfig) {
                    dimension = Mapbender.Dimension(sourceDimensionConfig);
                    break;
                }
            }
            var valarea = $('#' + key + ' .dimensionset-value', this.element);
            valarea.text(dimension.getDefault());
            $('#' + key + ' .mb-slider', this.element).slider({
                min: 0,
                max: dimension.getStepsNum(),
                value: dimension.getStep(dimension.getDefault()),
                slide: function (event, ui) {
                    valarea.text(dimension.valueFromStep(ui.value));
                },
                stop: function (event, ui) {
                    for (var i = 0; i < sourceIds.length; ++i) {
                        var source = self.model.getSourceById(sourceIds[i]);
                        if (source) {
                            var params = {};
                            params[dimension.getOptions().__name] = dimension.valueFromStep(ui.value);
                            source.addParams(params);
                        }
                    }
                }
            });
        },
        _getSourceDimensionConfig: function(source, name) {
            var sourceDimensions = source && source.configuration.options.dimensions || [];
            for (var j = 0; j < sourceDimensions.length; ++j) {
                var sourceDimension = sourceDimensions[j];
                if (sourceDimension.name === name) {
                    return sourceDimension;
                }
            }
            return false;
        },
        _preconfigureSources: function(sourceIds, dimensionConfig) {
            for (var i = 0; i < sourceIds.length; ++i) {
                var source = this.model.getSourceById(sourceIds[i]);
                this._preconfigureSource(source, dimensionConfig);
            }
        },
        _preconfigureSource: function(source, settings) {
            var targetConfig = this._getSourceDimensionConfig(source, settings.name);
            if (targetConfig) {
                targetConfig.extent = settings.extent;
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
        },
        _destroy: $.noop
    });
})(jQuery);

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
                this._setupGroup(dimensionUuids[i]);
            }
            this._trigger('ready');
        },
        _setupGroup: function (key) {
            var self = this;
            var dimensionset = this.options.dimensionsets[key];
            var dimension = Mapbender.Dimension(dimensionset['dimension']);
            var def = dimension.partFromValue(dimension.getDefault());
            var valarea = $('#' + key + ' .dimensionset-value', this.element);
            valarea.text(dimension.getDefault());
            $('#' + key + ' .mb-slider', this.element).slider({
                min: 0,
                max: 100,
                value: def * 100,
                slide: function (event, ui) {
                    valarea.text(dimension.valueFromPart(ui.value / 100));
                },
                stop: function (event, ui) {
                    $.each(dimensionset.group, function (idx, item) {
                        var sources = self.model.findSource({origId: item.split('-')[0]});
                        if (sources.length > 0) {
                            var params = {};
                            params[dimension.getOptions().__name] = dimension.valueFromPart(ui.value / 100);
                            self.model.resetSourceUrl(sources[0], {'add': params});
                        }
                    });
                }
            });
        },
        _destroy: $.noop
    });
})(jQuery);

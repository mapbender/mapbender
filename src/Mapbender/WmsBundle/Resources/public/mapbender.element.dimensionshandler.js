(function ($) {
    $.widget("mapbender.mbDimensionsHandler", {
        options: {
        },
        elementUrl: null,
        model: null,
        _create: function () {
            var self = this;
            if (!Mapbender.checkTarget("mbDimensionsHandler", this.options.target)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function () {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.model = $("#" + this.options.target).data("mapbenderMbMap").getModel();
            for (dimId in this.options.dimensionsets) {
                this._setupGroup(dimId);
            }
            this._trigger('ready');
            this._ready();
        },
        _setupGroup: function (key) {
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.model = $("#" + this.options.target).data("mapbenderMbMap").getModel();
            var dimensionset = this.options.dimensionsets[key];
            var dimension = Mapbender.Dimension(dimensionset['dimension']);
            var def = dimension.partFromValue(dimension.getDefault());// * 100;
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
                            self.model.resetSourceUrl(sources[0], {'add': params}, true);
                        }
                    });
                }
            });
        },
        ready: function (callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        _ready: function() {
        for (callback in this.readyCallbacks) {
        callback();
            delete(this.readyCallbacks[callback]);
        }
        this.readyState = true;
        },
        _destroy: $.noop
    });
        })(jQuery);

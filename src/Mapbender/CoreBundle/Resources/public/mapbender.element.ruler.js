(function($) {

    $.widget("mapbender.mbRuler", {
        options:                {
            target:    null,
            click:     undefined,
            icon:      undefined,
            label:     true,
            group:     undefined,
            immediate: null,
            persist:   true,
            type:      'line',
            precision: 2
        },
        control:                null,
        map:                    null,
        segments:               null,
        total:                  null,
        container:              null,
        active:                 false,
        popup:                  null,
        featureVeriticesLength: 2,
        typeMap:                {
            line: {name:       'LineString',
                startVertices: 4,
                increase:      2
            },
            area: {
                name:          'Polygon',
                startVertices: 6,
                increase:      2
            }
        },
        _create:                function() {
            var self = this;
            if(this.options.type !== 'line' && this.options.type !== 'area') {
                throw Mapbender.trans("mb.core.ruler.create_error");
            }
            if(!Mapbender.checkTarget("mbRuler", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the overview
         */
        _setup:                 function() {

            this.container = $('<div/>');
            this.total = $('<div/>').appendTo(this.container);
            this.segments = $('<ul/>').appendTo(this.container);

            this._trigger('ready');

        },
        /**
         * Default action for mapbender element
         */
        defaultAction:          function() {
            this.activate();
        },

        /**
         * This activates this button and will be called on click
         */
        activate:    function() {

            if(this.active) {
                this.popup.close("");
                return false;
            }
            this.active = true;
            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            var id = Mapbender.UUID();
            this.id = id;
            var model = this.map.model;

            var type = this.typeMap[this.options.type].name;

            var layerStyle = model.createVectorLayerStyle();
            this.layerId = model.createDrawControl(type, id, layerStyle, {
                'drawstart': function(event) {
                    var obvservable = {value: null};
                    this.featureVeriticesLength = this.typeMap[this.options.type].startVertices;
                    model.removeAllFeaturesFromLayer(id, this.layerId);
                    this._reset();

                    model.eventFeatureWrapper(event, model.onFeatureChange, [function(f) {
                        console.log(model.getGeometryCoordinates(f).length);

                        if(model.getGeometryCoordinates(f).length !== this.featureVeriticesLength) {
                            this._handleModify(model.getLineStringLength(f));
                        }
                        if(model.getGeometryCoordinates(f).length === this.featureVeriticesLength) {
                            this.featureVeriticesLength = this.featureVeriticesLength + this.typeMap[this.options.type].increase;
                            this._handlePartial(model.getLineStringLength(f));
                        }

                    }.bind(this), obvservable]);

                }.bind(this),

                'drawend': function(event) {
                    model.eventFeatureWrapper(event, function(f) {
                        this._handleFinal(model.getFeatureSize(f));
                    }.bind(this));
                }.bind(this)

            });

            this._reset();

            if(!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup2({
                    title:          this.element.attr('title'),
                    modal:          false,
                    draggable:      true,
                    resizable:      true,
                    closeButton:    false,
                    closeOnESC:     true,
                    destroyOnClose: true,
                    content:        this.container,
                    width:          300,
                    height:         300,
                    buttons:        {
                        'ok': {
                            label:    Mapbender.trans("mb.core.ruler.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function() {

                                this.popup.close("");
                            }.bind(this)
                        }
                    }
                });
                this.popup.$element.on('close', this.deactivate.bind(this));
            } else {
                this.popup.open("");
            }

            (this.options.type === 'line') ? $("#linerulerButton").parent().addClass("toolBarItemActive") : $("#arearulerButton").parent().addClass("toolBarItemActive");
        },
        /**
         * This deactivates this button and will be called if another button of
         * this group is activated.
         */
        deactivate:  function() {

            this.active = false;
            this.map.model.removeVectorLayer(this.id, this.layerId);
            $("#linerulerButton, #arearulerButton").parent().removeClass("toolBarItemActive");

        },
        _isGeodesic: function() {
            //var mapProj = this.map.data('mapQuery').olMap.getProjectionObject();
            return false;//mapProj.proj.units === 'degrees' || mapProj.proj.units === 'dd';
        },

        _reset:         function() {
            this.segments.empty();
            this.total.empty();
            //this.segments.append('<li/>');

        },
        _handleModify:  function(measure) {

            var measure = this.formatLength(measure);
            if(this.options.immediate) {
                this.segments.children('li').first().html(measure);
            }

            if($('body').data('mapbenderMbPopup')) {
                $("body").mbPopup('setContent', measure);
            }
        },
        _handlePartial: function(measure) {
            var measure = this.formatLength(measure);
            if(!this.options.immediate && this.featureVeriticesLength <= this.typeMap[this.options.type].startVertices + this.typeMap[this.options.type].increase) {
                return false;
            }
            if(this.options.type === 'area') {
                this.segments.html($('<li/>', {html: measure}));
            } else if(this.options.type === 'line') {

                var measureElement = $('<li/>');
                measureElement.text(measure);
                this.segments.prepend(measureElement);

            }
        },
        _handleFinal:   function(measure) {

            if(this.options.type === 'area') {
                this.segments.empty();
                var measureElement = $('<li/>');
                measureElement.text(this.formatLength(measure));
                this.segments.prepend(measureElement);
            }

            this.segments.children().first().wrap('<b>');
        },

        formatLength: function(length) {
            var unit  = (this.options.type === 'line') ? ' km' : ' km²';
            return (length / 1000).toFixed(this.options.precision) + unit;

        }
    });
})(jQuery);

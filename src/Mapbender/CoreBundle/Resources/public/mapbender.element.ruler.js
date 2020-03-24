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
        _createControl: function() {
            var nVertices = 1;
            var self = this;
            var handler = (this.options.type === 'line' ? OpenLayers.Handler.Path :
                    OpenLayers.Handler.Polygon);
            var control = new OpenLayers.Control.Measure(handler, {
                persist: true,
                immediate: !!this.options.immediate,
                displaySystemUnits: {
                    metric: ['m'],
                },
                geodesic: true
            });

            control.events.on({
                'scope': this,
                'measure': function(event) {
                    self._handleFinal(event);
                },
                'measurepartial': function(event) {
                    var nVerticesNow = event.geometry.components.length;
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
            this.control = this._createControl();
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
            if (state) {
                this.mapModel.olMap.addControl(this.control);
                this.control.activate();
            } else {
                this.control.deactivate();
                this.mapModel.olMap.removeControl(this.control);
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
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                // first point
                this._reset();
                return;
            }
            if (this.options.immediate) {
                this.segments.children('li').first().text(measure);
            }
        },
        _handlePartial: function(event){
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                // first point
                this._reset();
                return;
            }
            if (this.options.type === 'area') {
                this.segments.empty();
            }
            var measureElement = $('<li/>');
            measureElement.text(measure);
            this.segments.prepend(measureElement);
        },
        _handleFinal: function(event){
            var measure = this._getMeasureFromEvent(event);
            if(this.options.type === 'area'){
                this.segments.empty();
            }
            this.total.empty().append($('<b>').text(measure));
        },
        _getMeasureFromEvent: function(event){
            var measure = event.measure;
            if (!measure) {
                return null;
            }
            if (this.options.type === 'area' && event.geometry.components[0].components.length < 4) {
                // OpenLayers 2 Polygon Handler can create degenerate linear rings with too few components, and calculate a (very
                // small) area for them. Ignore these cases.
                return null;
            }
            return this._formatMeasure(measure);
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

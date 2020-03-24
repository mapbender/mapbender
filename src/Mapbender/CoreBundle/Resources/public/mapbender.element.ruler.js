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
            var handler = (this.options.type === 'line' ? OpenLayers.Handler.Path :
                    OpenLayers.Handler.Polygon);
            var control = new OpenLayers.Control.Measure(handler, {
                persist: true,
                immediate: !!this.options.immediate,
                geodesic: true
            });

            control.events.on({
                'scope': this,
                'measure': this._handleFinal,
                'measurepartial': this._handlePartial,
                'measuremodify': this._handleModify
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
        _reset: function(){
            this.segments.empty();
            this.total.empty();
            this.segments.append('<li/>');

        },
        _handleModify: function(event){
            if(event.measure === 0.0){
                return;
            }

            var measure = this._getMeasureFromEvent(event);

            if(this.control.immediate){
                this.segments.children('li').first().html(measure);
            }
        },
        _handlePartial: function(event){
            if(event.measure === 0){// if first point
                this._reset();
                return;
            }

            var measure = this._getMeasureFromEvent(event);
            if(this.options.type === 'area'){
                this.segments.html($('<li/>', { html: measure }));
            } else if(this.options.type === 'line'){
                var measureElement = $('<li/>');
                measureElement.html(measure);
                this.segments.prepend(measureElement);
            }
        },
        _handleFinal: function(event){
            var measure = this._getMeasureFromEvent(event);
            if(this.options.type === 'area'){
                this.segments.empty();
            }
            this.total.html('<b>'+measure+'</b>');
        },
        _getMeasureFromEvent: function(event){
            var measure = event.measure,
                    units = event.units,
                    order = event.order;

            measure = measure.toFixed(this.options.precision) + " " + units;
            if(order > 1){
                measure += "<sup>" + order + "</sup>";
            }

            return measure;
        }
    });

})(jQuery);

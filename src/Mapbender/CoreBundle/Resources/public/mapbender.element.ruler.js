(function($){

    $.widget("mapbender.mbRuler", {
        options: {
            target: null,
            click: undefined,
            icon: undefined,
            label: true,
            group: undefined,
            immediate: null,
            persist: true,
            type: 'line',
            precision: 2
        },
        control: null,
        map: null,
        segments: null,
        total: null,
        container: null,
        popup: null,
        _create: function(){
            var self = this;
            if(this.options.type !== 'line' && this.options.type !== 'area'){
                throw Mapbender.trans("mb.core.ruler.create_error");
            }
            if(!Mapbender.checkTarget("mbRuler", this.options.target)){
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the overview
         */
        _setup: function(){
            this.map = $('#' + this.options.target);
            var sm = $.extend(true, {}, OpenLayers.Feature.Vector.style, {
                'default': this.options.style
            });
            var styleMap = new OpenLayers.StyleMap(sm);

            var handler = (this.options.type === 'line' ? OpenLayers.Handler.Path :
                    OpenLayers.Handler.Polygon);
            var immediate = this.options.immediate || false;
            this.control = new OpenLayers.Control.Measure(handler, {
                callbacks: {
                    modify: function(point, feature, drawing){
                        // Monkey patching, so modify uses a different event than
                        // the point handler. Sad, but true.
                        if(drawing && this.delayedTrigger === null &&
                                !this.handler.freehandMode(this.handler.evt)){
                            this.measure(feature.geometry, "measuremodify");
                        }
                    }
                },
                // This, too, is part of the monkey patch - unregistered event
                // types wont fire
                EVENT_TYPES: OpenLayers.Events.prototype.BROWSER_EVENTS
                        .concat(['measuremodify']),
                handlerOptions: {
                    layerOptions: {
                        styleMap: styleMap,
                        name: 'rulerlayer'
                    }
                },
                persist: this.options.persist,
                immediate: immediate,
                geodesic: this._isGeodesic()
            });

            this.control.events.on({
                'scope': this,
                'measure': this._handleFinal,
                'measurepartial': this._handlePartial,
                'measuremodify': this._handleModify
            });

            this.container = $('<div/>');
            this.total = $('<div/>').appendTo(this.container);
            this.segments = $('<ul/>').appendTo(this.container);

            $(document).bind('mbmapsrschanged', $.proxy(this._mapSrsChanged, this));
            
            this._trigger('ready');
            this._ready();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },
        /**
         * This activates this button and will be called on click
         */
        activate: function(callback){
            this.callback = callback ? callback : null;
            var self = this,
                    olMap = this.map.data('mapQuery').olMap;
            olMap.addControl(this.control);
            this.control.geodesic = this._isGeodesic();
            this.control.activate();

            this._reset();
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    modal: false,
                    draggable: true,
                    resizable: true,
                    closeButton: false,
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

            (this.options.type === 'line') ?
                    $("#linerulerButton").parent().addClass("toolBarItemActive") :
                    $("#arearulerButton").parent().addClass("toolBarItemActive");
        },
        /**
         * This deactivates this button and will be called if another button of
         * this group is activated.
         */
        deactivate: function(){
            this.container.detach();
            var olMap = this.map.data('mapQuery').olMap;
            this.control.deactivate();
            olMap.removeControl(this.control);
            $("#linerulerButton, #arearulerButton").parent().removeClass("toolBarItemActive");
            if(this.popup && this.popup.$element){
                this.popup.destroy();
            }
            this.popup = null;
            this.callback ? this.callback.call() : this.callback = null;
        },
        _isGeodesic: function(){
            var mapProj = this.map.data('mapQuery').olMap.getProjectionObject();
            return mapProj.proj.units === 'degrees' || mapProj.proj.units === 'dd';
        },
        _mapSrsChanged: function(event, srs){
            if (this.control.active) { // change geodesic if a control is active
                this.control.geodesic = this._isGeodesic();
                this.control.deactivate();
                this.control.activate();
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

            var widget = this;
            var measure = widget._getMeasureFromEvent(event);

            if(widget.control.immediate){
                widget.segments.children('li').first().html(measure);
            }

            if($('body').data('mapbenderMbPopup')){
                $("body").mbPopup('setContent', measure);
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
        },

        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });

})(jQuery);

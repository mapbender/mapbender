(function($){

    $.widget("mapbender.mbSketch", {
        options: {
            target: null,
            autoOpen: false
        },
        control: {},
        activeType: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbSketch", this.options.target)){ // check if target defined
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self)); // call _setup if target ready
        },
        _createLayer: function(type){
            switch(type){
                case 'point':
                    return new OpenLayers.Layer.Vector("mbSketch.point");
                    break;
                case 'circle':
                    return new OpenLayers.Layer.Vector("mbSketch.circle");
                    break;
                case 'line':
                    return new OpenLayers.Layer.Vector("mbSketch.line");
                    break;
                case 'box':
                    return new OpenLayers.Layer.Vector("mbSketch.box");
                    break;
                case 'polygon':
                    return new OpenLayers.Layer.Vector("mbSketch.polygon");
                    break;
                default:
                    return new OpenLayers.Layer.Vector("mbSketch");
            }
        },
        _createControl: function(type, layer){
            var control = null;
            switch(type){
                case 'point':
                    control = new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Point);
                    break;
                case 'circle':
                    control = new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon,
                        {
                            handlerOptions: {
                                sides: 32,
                                radius: 0.0001,
                                irregular: false
                                ,persist: true
                            }
                        });
                    break;
                case 'line':
                    control = new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Path);
                    break;
                case 'box':
                    control = new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon,
                        {
                            handlerOptions: {
                                sides: 4,
                                irregular: true
                            }
                        });
                    break;
                case 'polygon':
                    control = new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon);
                    break;
                default:
                    control = null;
            }
            if(control){
                control.events.on({
                    'featureadded': $.proxy(this._featureAdded, this)});
            }
            return control;
        },
        _featureAdded: function(e){
            window.console && console.log(e, e.object.layer.name);
            switch(e.object.layer.name){
                case 'mbSketch.circle':
                    this._open(e);
                    break;
                default:
                    break;
            }
        },
        _activateType: function(type){
            for(tp in this.controls){
                this.controls[tp].deactivate();
            }
            if(type){
                this.controls[this.activeType].activate();
                this.activeType = type;
            }

        },
        _setup: function(){
            this.map = $('#' + this.options.target);
            this.layers = {};
            this.controls = {};
            this.activeType = this.options.defaultType;
            this._trigger('ready');
            this._ready();
        },
        /**
         * Default action for a mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },
        activate: function(callback){
            var self = this;
            window.console && console.log(this);
            this.callback = callback ? callback : null;

            var mq = this.map.data('mapQuery');
            this.baseControls = [
                new OpenLayers.Control.LayerSwitcher(),
                new OpenLayers.Control.MousePosition()];
            $.each(this.baseControls, function(idx, cntrl){
                mq.olMap.addControl(cntrl);
            });
            $.each(this.options.types, function(idx, type){
                self.layers[type] = self._createLayer(type);
                mq.olMap.addLayer(self.layers[type]);
                self.controls[type] = self._createControl(type, self.layers[type]);
                mq.olMap.addControl(self.controls[type]);
            });
            this._activateType(this.options.defaultType);
        },
        deactivate: function(){
            var self = this;
            this._activateType(null);
            var mq = this.map.data('mapQuery');
            $.each(this.options.types, function(idx, type){
                mq.olMap.removeControl(self.controls[type]);
                mq.olMap.removeLayer(self.layers[type]);
            });
            $.each(this.baseControls, function(idx, cntrl){
                cntrl.deactivate();
                mq.olMap.removeControl(cntrl);
            });
            this._close();
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         * closes a dialog
         */
        _close: function(){
            if(this.popup){
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
        },
        /**
         * opens a dialog
         */
        _open: function(e){
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    closeOnPopupCloseClick: false,
                    content: [
                        '<span>Bitte geben Sie den Radius ein:</span>'+
                        '<input type="text" value="" id="radius"/>'
                    ],
                    destroyOnClose: true,
                    width: 400,
                    buttons: {
                        'cancel': {
                            label: 'Cancel',
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self._close();
                            }
                        },
                        'ok': {
                            label: 'Save',
                            cssClass: 'button buttonYes right',
                            callback: function(){
                                var radius = parseInt($('#radius', self.popup.$element).val());
                                if(isNaN(radius)){
                                    Mapbender.error('mb.core.sketch.circle.form.radius.error');
                                } else {
                                    var bounds = e.feature.geometry.bounds,
                                        center = new OpenLayers.Geometry.Point((bounds.left + bounds.right)/2.0, (bounds.bottom + bounds.top)/2.0),
                                        geom = OpenLayers.Geometry.Polygon.createRegularPolygon(center,radius,32,0);
                    
                                    e.feature.geometry = geom;
                                    e.object.layer.drawFeature(e.feature);
                                    self._close();
                                }
                            }
                        }
                    }
                });
            }else{
                this.popup.open();
            }
        },
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });
})(jQuery);


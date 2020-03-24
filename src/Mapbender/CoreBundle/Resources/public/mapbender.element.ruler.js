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
        /**
         * Initializes the overview
         */
        _setup: function(mbMap) {
            this.mapModel = mbMap.getModel();
            var sm = $.extend(true, {}, OpenLayers.Feature.Vector.style, {
                'default': this.options.style
            });
            var immediate = this.options.immediate || false;

            var controlOptions = {
                type: this.options.type === 'line' ? 'LineString' : 'Polygon',
                source: new ol.source.Vector(),
                persist: true
            };
            var self = this;
            this.control = new ol.interaction.Draw(controlOptions);
            this.control.on('drawstart', function(event) {
                self._reset();
                var feature = event.feature;
                var geometry = feature.getGeometry();
                var nVertices = geometry.getFlatCoordinates().length;
                geometry.on('change', function() {
                    var nVerticesNow = geometry.getFlatCoordinates().length;
                    if (nVerticesNow === nVertices) {
                        // geometry change event does not have a .feature attribute like drawend, shim it
                        self._handleModify({feature: feature});
                    } else {
                        // geometry change event does not have a .feature attribute like drawend, shim it
                        self._handlePartial({feature: feature});
                        nVertices = nVerticesNow;
                    }
                });
            });
            this.control.on('drawend', function(event) {
                self._handleFinal(event);
            });
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
        /**
         * This activates this button and will be called on click
         */
        activate: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if (Mapbender.mapEngine.code === 'ol2') {
                this.mapModel.olMap.addControl(this.control);
                this.control.activate();
            } else {
                this.mapModel.olMap.addInteraction(this.control);
                this.control.setActive(true);
            }

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
            this.map.model.removeAllFeaturesFromLayer(this.id, this.layerId);
            this.map.model.removeVectorLayer(this.id, this.layerId);

            var olMap = this.mapModel.map.olMap;
            this.control.deactivate();
            olMap.removeControl(this.control);
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
            var measure = this._getMeasureFromEvent(event);

            if (this.options.immediate && measure) {
                this.segments.children('li').first().text(measure);
            }
        },
        _handlePartial: function(event) {
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                return;
            }

            if(this.options.type === 'area'){
                this.segments.html($('<li/>').text(measure));
            } else if(this.options.type === 'line'){
                var measureElement = $('<li/>');
                measureElement.text(measure);
                this.segments.prepend(measureElement);
            }
        },
        _handleFinal: function(event){
            var measure = this._getMeasureFromEvent(event);
            if (!measure) {
                return;
            }
            if(this.options.type === 'area'){
                this.segments.empty();
            }
            this.total.empty().append($('<b>').text(measure));
        },
        _getMeasureFromEvent: function(event){
            var order = (this.options.type === 'line' && 1) || 2;
            var measure, units;
            if (!event.measure && event.feature) {
                measure = this.mapModel.getFeatureSize(event.feature, this.options.type);
                units = 'km';
            } else {
                measure = event.measure;
                units = event.units;
            }
            if (!measure) {
                return null;
            }

            if (order === 2) {
                units = units + '²';
            }
            return (measure / 1000).toFixed(this.options.precision) + units;
        }
    });

})(jQuery);

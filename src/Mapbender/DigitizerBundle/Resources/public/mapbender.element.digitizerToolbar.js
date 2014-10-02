(function($){

    $.widget("mapbender.mbDigitizerToolbar", {
        options: {},
        map: null,
        layer: null,
        activeControl: null,
        selectedFeature: null,

        _create: function(){
            if(!Mapbender.checkTarget("mbDigitizerToolbar", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap').map.olMap;
            
            var selectControl = this.map.getControlsByClass('OpenLayers.Control.SelectFeature');    
            this.map.removeControl(selectControl[0]);   
            var rootContainer = this.map.getLayersByClass('OpenLayers.Layer.Vector.RootContainer');
            this.map.removeLayer(rootContainer[0]);
            
            
            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 400,
                    height: 380,
                    buttons: {
                        'cancel': {
                            label: 'Close',
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            }else{
                if(this.popupIsOpen === false){
                    this.popup.open(self.element);
                }
            }
            me.show();
            this.popupIsOpen = true;

            this._initialize();
        },
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this._deactivateControl();
                this.popupIsOpen = false;
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
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
        },
        
        
        _initialize: function(){
            
//            var defaultStyle = new OpenLayers.Style($.extend({}, OpenLayers.Feature.Vector.style["default"], {
//                    'strokeWidth': 4
//                }));       
//            var styleMap = new OpenLayers.StyleMap({'default': defaultStyle},{extendDefault: true});
            
            if(this.layer === null){
            
                this.layerList = [];
                this.layerList[0] = new OpenLayers.Layer.Vector('vLayer1');//,{styleMap: styleMap});
                this.layerList[1] = new OpenLayers.Layer.Vector('vLayer2');


                this.map.addLayer(this.layerList[1]);
                this.map.addLayer(this.layerList[0]);

                this.layer = this.layerList[0];
            }

            this.createContextmenu();
            
            $('.mbdigitizerTool').on('click', $.proxy(this._newControl, this));
            $('#mbdigitizerLayerSwitch').on('click', $.proxy(this._switchLayer, this));
            
            
            $('.mbdigitizerMerge').on('click', $.proxy(this._testMerge, this));
            $('.mbdigitizerSplit').on('click', $.proxy(this._testSplit, this));
            $('.mbdigitizerBuffer').on('click', $.proxy(this._testBuffer, this));
            
            var self = this; 
//            
//            this.featureClickEvent = function(e){
//                var wkt = new OpenLayers.Format.WKT();
//                var reader = new jsts.io.WKTReader();
//                var f = self.layer.getFeatureFromEvent(e);
//                //var a = reader.read(wkt.write(f));
//                if (f !== null){
//                    alert(a);
//                }
//            };
//            this.map.events.register("click", this.map , this.featureClickEvent);
        },
        
        _switchLayer: function(e){
            this._deactivateControl();
            if($(e.target).val() === 'Layer1'){
                $('#mbdigitizerLayerSwitch').val('Layer2');
                this.layer = this.layerList[1];
                this.map.raiseLayer(this.layer, this.map.layers.length);
            }else{
                $('#mbdigitizerLayerSwitch').val('Layer1');
                this.layer = this.layerList[0];
                this.map.raiseLayer(this.layer, this.map.layers.length);
            }
            
        },
        
        _newControl: function(e) {
            var self = this;
            
            if ($(e.target).hasClass('active') === true){
               this._deactivateControl();
               return;
            }
            
            this._deactivateControl();
            
            if (this.selectedFeature !== null){
                this.selectedFeature.renderIntent = 'default';
                this.layer.redraw();
                this.selectedFeature = null;
            }
            

            $(e.target).addClass('active');
            
            switch (e.target.name)
            {
                case 'point':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.Point);
                  break;
                case 'line':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.Path);//, {multi:true});
                  break;
                case 'polygon':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.Polygon, {
                            handlerOptions: {
                                handleRightClicks: false
                            }
                        });
                  break;
                case 'rectangle':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.RegularPolygon, {
                            handlerOptions: {
                                sides: 4,
                                irregular: true,
                                rightClick: false
                            }
                        });
                  break;
                case 'circle':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.RegularPolygon, {
                            handlerOptions: {
                                sides: 40,
                                irregular: false
                            }
                        });
                  break;
                case 'donut':
                  this.activeControl = new OpenLayers.Control.DrawFeature(this.layer,
                        OpenLayers.Handler.Polygon, {
                            handlerOptions: {
                                holeModifier: 'element'
                            }
                        });
                  break;
                case 'drag':
                  this.activeControl = new OpenLayers.Control.DragFeature(this.layer,{
                        onStart: function(f, pixel){
                            f.renderIntent = 'select';
                        },
                        onComplete: function(f, pixel){
                            f.renderIntent = 'default';
                            self.layer.redraw();
                        }
                    });
                  break;
                case 'modify':
                  this.activeControl = new OpenLayers.Control.ModifyFeature(this.layer);
                  break;
                case 'select':
                  this.activeControl = new OpenLayers.Control.SelectFeature(this.layer, {
                      clickout: true,
                      toggle: false,
                      multiple: false,
                      hover: false,
                      multipleKey: "ctrlKey",
                      toggleKey: "ctrlKey",
                      box: true
                  });
                  break;

            }


            this.map.addControl(this.activeControl);
            this.activeControl.activate();

        },
        
        createContextmenu: function(){
            var self = this;
            
            // prevent normal context menu for map
//            this.map.div.oncontextmenu = function noContextMenu(e) {
//                 e = e?e:window.event;
//                if (e.preventDefault) e.preventDefault(); // For non-IE browsers.
//                else return false; // For IE browsers.
//            };
            
            this.map.div.oncontextmenu = function noContextMenu(e) {
                
                if(!e){
                    var e = window.event;
                    e.returnValue = false;
                }

                self._deactivateControl();
                
                if (self.selectedFeature !== null){
                    self.selectedFeature.renderIntent = 'default';
                    self.layer.redraw();
                    self.selectedFeature = null;
                }          
                
                var f = self.layer.getFeatureFromEvent(e);        
                if (f !== null){             
                    self.map.events.unregister("click", self.map , self.mapClickEvent);
                    self.mapClickEvent = function(e){
                        self._removeContextMenu(); 
                        self.map.events.unregister("click", self.map , self.mapClickEvent);
                    };
                    self.map.events.register("click", self.map , self.mapClickEvent);
                    
                    
                    self._removeContextMenu();
                    $('.mb-element-map').append( '<div class="mb-digitizer-contextmenu">\n\
                                       <ul class="mb-digitizer-menucontent">\n\
                                        <li class="mb-digitizer-modify" >modify</li>\n\
                                        <li class="mb-digitizer-drag" >drag</li>\n\
                                        <li class="mb-digitizer-remove" >remove</li>\n\
                                        <hr>\n\
                                        <li class="mb-digitizer-debug" >debug</li>\n\
                                        <li class="mb-digitizer-closeMenu" >close</li>\n\
                                       </ul>\n\
                                       </div>');

                    $('.mb-digitizer-contextmenu').position({
                        my: "left+" + e.layerX + " top+" + e.layerY,
                        at: "left top",
                        of: '.mb-element-map',
                        collision: "flipfit"
                    });            

                    $('.mb-digitizer-remove').on('click', $.proxy(self.removeFeature, self, f));
                    $('.mb-digitizer-modify').on('click', $.proxy(self.modifyFeature, self, f));
                    $('.mb-digitizer-drag').on('click', $.proxy(self.dragFeature, self, f));
                    $('.mb-digitizer-debug').on('click', $.proxy(self._showFeatureAttributes, self, f));  
                    $('.mb-digitizer-closeMenu').on('click', function(){$('.mb-digitizer-contextmenu').remove();});

                }
                return false;
            }
        },
        
        modifyFeature: function(f){
            this._removeContextMenu();
            this.selectedFeature = f;
            this.activeControl = new OpenLayers.Control.ModifyFeature(this.layer, {standalone: true});        
            this.map.addControl(this.activeControl);
            this.activeControl.activate();
            this.activeControl.selectFeature(this.selectedFeature);
        },
        
        dragFeature: function(f){
            this._removeContextMenu();
            var self = this;
            this.selectedFeature = f;
            this.activeControl = new OpenLayers.Control.DragFeature(this.layer, {
                    onStart: function(f, pixel){
                        if(self.selectedFeature !== f){
                            self.activeControl.handlers.drag.deactivate();
                        }
                    }
                });
            this.map.addControl(this.activeControl);
            this.activeControl.activate();
            this.selectedFeature.renderIntent = 'select';
            this.layer.redraw();
        },
        
        removeFeature: function(f){
            this._removeContextMenu();
            this.layer.destroyFeatures([f]);
        },
        
        
        _deactivateControl: function(){
            if (this.activeControl !== null) {
                this.activeControl.deactivate();
                this.map.removeControl(this.activeControl);
                this.activeControl = null;
            }
            this._deactivateButton();
        },
        
        _deactivateButton: function(){
            $('.mbdigitizerTool').removeClass('active');
        },
        
        _removeContextMenu: function(){
            $('.mb-digitizer-contextmenu').remove(); 
        },
        
        
        
        _testMerge: function(){
            this._deactivateControl();
            
            
            if(this.layer.selectedFeatures.length > 1) {
            this.layer.addFeatures([this._merge(this.layer.selectedFeatures)]);
                while(this.layer.selectedFeatures[0]) {
                    this.removeFeature(this.layer.selectedFeatures[0]);
                }
            }  
        },
        
        _merge: function(polygonFeatures) {
            var jstsFromWkt = new jsts.io.WKTReader();
            var wktFromOl = new OpenLayers.Format.WKT();
            var olFromJsts = new jsts.io.OpenLayersParser();
            var union = false;
            var attributes = {};
            for(var i = 0; i < polygonFeatures.length; i++) {
                if(!union) {
                    union = jstsFromWkt.read(wktFromOl.write(polygonFeatures[i]));
                    attributes = polygonFeatures[i].attributes;
                } else {
                    var polygon = jstsFromWkt.read(wktFromOl.write(polygonFeatures[i]));
                    union = union.union(polygon);
                    for(var key in polygonFeatures[i].attributes) {
                        if (!attributes[key]) { 
                            attributes[key] = polygonFeatures[i].attributes[key];
                        }
                    }
                }
            }
            var feature = new OpenLayers.Feature.Vector(olFromJsts.write(union));
            feature.attributes = attributes;
            feature.state = OpenLayers.State.INSERT;
            return feature;
        },
        
        
        _testSplit: function(){
            this._deactivateControl();
            var self = this;
            
            if(this.layer.selectedFeatures.length === 0){
                return;
            }

            var drawControl = new OpenLayers.Control.DrawFeature(this.layer, OpenLayers.Handler.Path, {
                featureAdded: function (e){
                    var splitLine = e;
                    
                    if (splitLine.geometry.intersects(self.layer.selectedFeatures[0].geometry)) {
                        if(self.layer.selectedFeatures[0].geometry.CLASS_NAME === 'OpenLayers.Geometry.Polygon') {
                            self.layer.addFeatures(self._splitPolygon(splitLine, self.layer.selectedFeatures[0]));
                        } else if(self.layer.selectedFeatures[0].geometry.CLASS_NAME === 'OpenLayers.Geometry.LineString'){
                            self.layer.addFeatures(self._splitLine(splitLine, self.layer.selectedFeatures[0]));
                        }
                    }
                    self.map.removeControl(drawControl);
                    drawControl.deactivate();
                    self.removeFeature(splitLine);
                },
                handlerOptions: {
                    maxVertices: 2
                }
            });
            this.map.addControl(drawControl);
            drawControl.activate();
            
        },
        
        
        _splitPolygon: function(splitFeature, polygonFeature) {
            var jstsFromWkt = new jsts.io.WKTReader();
            var wktFromOl = new OpenLayers.Format.WKT();
            var olFromJsts = new jsts.io.OpenLayersParser();   
            
            var newFeatures = []

            var polygonizer = new jsts.operation.polygonize.Polygonizer();

            var line = jstsFromWkt.read(wktFromOl.write(splitFeature));
            var polygon = jstsFromWkt.read(wktFromOl.write(polygonFeature));

            var union = polygon.getExteriorRing().union(line);

            polygonizer.add(union);

            var polygons = polygonizer.getPolygons();
            for(var pIter = polygons.iterator(); pIter.hasNext();) {
                var polygon = pIter.next();

                var feature = new OpenLayers.Feature.Vector(olFromJsts.write(polygon), OpenLayers.Util.extend({}, polygonFeature.attributes));
                feature.attributes = polygonFeature.attributes;
                feature.state = OpenLayers.State.INSERT;
                newFeatures.push(feature);
            }
            this.removeFeature(polygonFeature);
            return newFeatures;
        },
        
        _splitLine: function(splitFeature, lineFeature) {
            var jstsFromWkt = new jsts.io.WKTReader();
            var wktFromOl = new OpenLayers.Format.WKT();
            var olFromJsts = new jsts.io.OpenLayersParser();  
            
            var newFeatures = [];

            var splitLine = jstsFromWkt.read(wktFromOl.write(splitFeature));
            var targetLine = jstsFromWkt.read(wktFromOl.write(lineFeature));

            var pointStore = [];
            var endPoint;
            for(var i = 0; i < targetLine.points.length -1; i++) {
                var startPoint = targetLine.points[i];
                endPoint = targetLine.points[i+1];

                var segment = new jsts.geom.LineString([startPoint, endPoint]);

                if(segment.intersects(splitLine)) {
                    var splitPoint = segment.intersection(splitLine).coordinate;
                    var newLine= new jsts.geom.LineString(pointStore.concat([startPoint, splitPoint]));
                    pointStore = [splitPoint];
                
                    var feature = new OpenLayers.Feature.Vector(olFromJsts.write(newLine), OpenLayers.Util.extend({}, lineFeature.attributes));
                    feature.attributes = lineFeature.attributes;
                    feature.state = OpenLayers.State.INSERT;
                    newFeatures.push(feature);
                
                } else {
                    pointStore.push(startPoint);
                }
            }
            var restLine = new jsts.geom.LineString(pointStore.concat([endPoint]));
            var feature = new OpenLayers.Feature.Vector(olFromJsts.write(restLine), OpenLayers.Util.extend({}, lineFeature.attributes));
            feature.attributes = lineFeature.attributes;
            feature.state = OpenLayers.State.INSERT;
            newFeatures.push(feature);
            
            this.removeFeature(lineFeature);
            return newFeatures;
        },
        
        _testBuffer: function(){
            
            if(this.layer.selectedFeatures.length === 0){
                return;
            }
            
            for (var i = 0; i < this.layer.selectedFeatures.length; i++){
            
                var jstsFromWkt = new jsts.io.WKTReader();
                var wktFromOl = new OpenLayers.Format.WKT();
                var olFromJsts = new jsts.io.OpenLayersParser();

                var sourceFeature = jstsFromWkt.read(wktFromOl.write(this.layer.selectedFeatures[i]));
                var radius = $( "input[name='bufferRadius']").val();

                var buffer = sourceFeature.buffer(radius);

                var bufferFeature = new OpenLayers.Feature.Vector(olFromJsts.write(buffer));
                bufferFeature.state = OpenLayers.State.INSERT;

                this.layer.addFeatures([bufferFeature]);
            
            }
            
            this.activeControl.unselectAll();
            this._deactivateControl();
        },
        
        
        _showFeatureAttributes: function(f){
            console.log(f);
            this._removeContextMenu();
        }
        
    });

})(jQuery);

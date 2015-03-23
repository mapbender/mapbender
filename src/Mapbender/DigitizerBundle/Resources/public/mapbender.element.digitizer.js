(function($){

    /**
     * Digitizer tool
     */
    $.widget("mapbender.mbDigitizerToolset", {
        options: {},
        _activeControls: [],

        refresh:function(){
            var widget = this;
            var element = $(widget.element);
            var items = widget.options.items;
            var layer = widget.options.layer;
            var map = layer.map;
            var currentController = null;
            // TODO: find map DOM element 
            var mapEl = $('#map');
            
            var controls = {
                drawPoint:     {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Point)
                },
                drawLine:   {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Path)
                },
                drawPolygon:   {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon)
                },
                drawRectangle: {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                            handlerOptions: {
                                sides:      4,
                                irregular:  true
                            }
                        })
                },
                drawCircle: {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                            handlerOptions: {
                                sides:      40
                            }
                        })
                },
                drawEllipse: {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                            handlerOptions: {
                                sides:      40,
                                irregular:  true,
                            }
                        })
                },
                donut: {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon, {
                            handlerOptions: {
                                holeModifier: 'element'
                            }
                        })
                },
                edit:{
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'crosshair'});
                        } else {
                            mapEl.css({cursor: 'default'});
                        }

                    },
                    control: new OpenLayers.Control.ModifyFeature(layer)
                },
                drag:  {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(switchController(el.data('control'))) {
                            mapEl.css({cursor: 'default'});
                        }
                        mapEl.css({cursor: 'default'});
                    },
                    control:  new OpenLayers.Control.DragFeature(layer,{
                            onStart: function(feature) {
                                    feature.renderIntent = 'select';
                                },
                            onComplete: function(feature) {
                                    feature.renderIntent = 'default';
                                    feature.layer.redraw();
                                }
                    })
                },
                select:   {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        switchController(el.data('control'));
                        mapEl.css({cursor: 'default'});

                    },
                    control:  new OpenLayers.Control.SelectFeature(layer, {
                        clickout:    true,
                        toggle:      true,
                        multiple:    true,
                        hover:       false,
                        box:         true,
                        toggleKey:   "ctrlKey", // ctrl key removes from selection
                        multipleKey: "shiftKey" // shift key adds to selection
                    })
                },
                removeSelected:  {
                    listener: function(e) {
                        layer.removeFeatures(layer.selectedFeatures);
                    }
                },
                removeAll:       {
                    listener: function(e) {
                        layer.removeAllFeatures();
                    }
                }
            };
            
            /**
             * Switch between current and element controller.
             * Returns true if last controller was different to given one
             *
             * @param controller
             * @returns {boolean}
             */
            function switchController(controller) {

                if(controller) {
                    controller.activate();
                }

                if(currentController) {
                    if(currentController instanceof OpenLayers.Control.SelectFeature){
                        currentController.unselectAll();
                    }
                    currentController.deactivate();
                    if(controller === currentController) {
                        currentController = null;
                        return false;
                    }
                }
                currentController = controller;
                return true;
            }
            
            // clean controllers
            for (var key in widget._activeControls) {
                var control = widget._activeControls[key];
                control.deactivate();
                
                mapEl.css({cursor: 'default'});
                map.removeControl(control);
            }        
            widget._activeControls = [];

            // clean navigation
            element.empty();
            
            // build navigation
            $.each(items, function(idx, button) {
                var item = this;
                var button = $("<button/>");
                var type = item.type;
                
                button.html(item.type);
                button.data(item);
                
                if(controls.hasOwnProperty(type)){
                    var controlDefiniton = controls[type];
                    button.on('click', controlDefiniton.listener);

                    if(controlDefiniton.hasOwnProperty('control')) {
                        button.data('control', controlDefiniton.control);
                        widget._activeControls.push(controlDefiniton.control);
                        
                        var drawControlEvents = controlDefiniton.control.events;
                        drawControlEvents.register('activate', button, function(e, obj) {
                            button.addClass('active');
                        });
                        drawControlEvents.register('deactivate', button, function(e, obj) {
                            button.removeClass('active');
                        });
                    }
                }

                element.append(button);
            });
            
            // Init map controllers
            for (var key in widget._activeControls) {
                map.addControl(widget._activeControls[key]);
            }
            
            widget._trigger('ready', null, this);
        },

        _create: function() {
            this.refresh();
        },
        
        _setOptions: function( options ) {
            this._super( options );
            this.refresh();
        }
    });


    $.widget("mapbender.mbDigitizer", {
        options: {},
        toolsets: {
            point: [
              {type: 'drawPoint', icon: 'drawPoint'},
              {type: 'edit', icon: 'edit'},
              {type: 'drag', icon: 'drag'},
              {type: 'select', icon: 'select'},
              {type: 'removeSelected', icon: 'removeSelected'},
              {type: 'removeAll', icon: 'removeAll'}
            ],
            line: [
              {type: 'drawLine', icon: 'drawLine'},
              {type: 'edit', icon: 'edit'},
              {type: 'drag', icon: 'drag'},
              {type: 'select', icon: 'select'},
              {type: 'removeSelected', icon: 'removeSelected'},
              {type: 'removeAll', icon: 'removeAll'}
            ],
            polygon: [
              {type: 'drawPolygon', icon: 'drawPolygon'},
              {type: 'drawRectangle', icon: 'drawRectangle'},
              {type: 'drawCircle', icon: 'drawRectangle'},
              {type: 'drawEllipse', icon: 'drawRectangle'},
              {type: 'donut', icon: 'drawRectangle'},
              {type: 'edit', icon: 'edit'},
              {type: 'drag', icon: 'drag'},
              {type: 'select', icon: 'select'},
              {type: 'removeSelected', icon: 'removeSelected'},
              {type: 'removeAll', icon: 'removeAll'}
            ]
        },
        map: null,

        _create: function(){
            if(!Mapbender.checkTarget("mbDigitizer", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        
        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap').map.olMap;

            var self = this;
            var element = $(self.element);
            var toolset = $(".tool-set", element);
            var selector = self.selector =  $("select.selector", element);

            var defaultStyle = new OpenLayers.Style($.extend({}, OpenLayers.Feature.Vector.style["default"], {
                'strokeWidth': 2
//                'strokeColor': '#FF0000',
//                'fillColor':  '#FF0000'
            }));
            var selectStyle = new OpenLayers.Style($.extend({}, OpenLayers.Feature.Vector.style["select"], {
                strokeWidth: 2
//                strokeColor: '#FFFF00',
//                fillColor: '#0000FF'
            }));

            var styleMap = new OpenLayers.StyleMap({'default': defaultStyle,
                'select': selectStyle}, {extendDefault: true});  
            
            $.each(this.options.schemes, function(key){
                var settings = this;
                var option = $("<option/>");
                option.val(key).html(settings.label);
                
                settings.layer = new OpenLayers.Layer.Vector(settings.label, {styleMap: styleMap});
                self.map.addLayer(settings.layer);
                
                option.data(settings);
                selector.append(option);
            });
            
            function onSelectorChange(){
                var option = selector.find(":selected");
                var settings = option.data();
                self.activeSchemaName = option.val();
                self.activeSchema = settings;
                self.activeLayer = settings.layer;

                $(toolset).mbDigitizerToolset({items: self.toolsets[settings.geomType], layer: settings.layer});
                
                self._getData();
            }
            
            selector.on('change',onSelectorChange);
            
            // render selector
            //element.append(selector);
            //element.append(toolset);
            
            onSelectorChange();
                    
            this.map.events.register('click', this, this._mapClick);

            this._trigger('ready');
        },
        
        _mapClick: function(evt) {
            var self = this;
            var x = evt.pageX;
            var y = evt.pageY;
            
            // return if modifycontrol is active
            var controls = this.map.getControlsByClass('OpenLayers.Control.ModifyFeature');
            for (var i = 0; i <  controls.length; i++){
                if(controls[i].active === true) {
                    return;
                }
            }
            
            // getFeatures from Event
            var features = this._getFeaturesFromEvent(x, y);
            if(features.length === 0) {
                console.log('no features');
                return;
            }
            
            $.notify('feature clicked: ' + features[0].id,'info')
            console.log(features[0]);
            //TODO open form popup
            
        },
        
        _getFeaturesFromEvent: function(x, y) {       
            var features = [], targets = [], layers = [];
            var layer, target, feature, i, len;
            this.map.resetLayersZIndex();
            // go through all layers looking for targets
            for (i=this.map.layers.length-1; i>=0; --i) {
                layer = this.map.layers[i];
                if (layer.div.style.display !== "none") {
                    if (layer === this.activeLayer) {               
                        target = document.elementFromPoint(x, y);
                        while (target && target._featureId) {
                            feature = layer.getFeatureById(target._featureId);
                            if (feature) {
                                features.push(feature);
                                target.style.visibility = 'hidden';
                                targets.push(target);
                                target = document.elementFromPoint(x, y);
                            } else {
                                // sketch, all bets off
                                target = false;
                            }
                        }
                    }
                    layers.push(layer);
                    layer.div.style.display = "none";
                }
            }
            // restore feature visibility
            for (i=0, len=targets.length; i<len; ++i) {
                targets[i].style.display = "";
                targets[i].style.visibility = 'visible';
            }
            // restore layer visibility
            for (i=layers.length-1; i>=0; --i) {
                layers[i].div.style.display = "block";
            }

            this.map.resetLayersZIndex();
            return features;
        },
        
        _getData: function(){
            var self = this;
            
            // develop
            if(self.activeSchemaName !== 'table1'){
                return;
            }
            
            var proj = this.map.getProjectionObject();
            var newProj = new OpenLayers.Projection("EPSG:"+self.activeSchema.srid);
            var extent = this.map.getExtent();
            extent.transform(proj, newProj);

            var data = {
                clientSrid: proj.proj.srsProjNumber,
                bottom: extent.bottom,
                left: extent.left,
                top: extent.top,
                right: extent.right,
                maxResults: 10
            };

            self.query('select', data).done(function(response) {
                if(response) {
                    self.activeLayer.removeAllFeatures();
                    var geojson_format = new OpenLayers.Format.GeoJSON();
                    var features = geojson_format.read(response);
                    self.activeLayer.addFeatures(features);
                }
            });
        },

        /**
         * Element controller XHR query
         *
         * @param uri
         * @param request
         * @return {*}
         */
        query: function(uri, request) {
            var widget = this;
            request.schema = this.activeSchemaName;
            return $.ajax({
                url:         widget.elementUrl + uri,
                type:        'POST',
                contentType: "application/json; charset=utf-8",
                dataType:    "json",
                data:        JSON.stringify(request),
            }).error(function(xhr) {
                $.notify("XHR error:" + JSON.stringify(xhr.responseText));
                console.log("XHR Error:", xhr);
            });
        }
    });

})(jQuery);

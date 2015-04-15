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
                    control: new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Point, {
                        featureAdded: function (feature) {
                            widget._trigger("featureAdded",null, feature);
                        }
                    })
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
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Path, {
                        featureAdded: function (feature) {
                            widget._trigger("featureAdded",null, feature);
                        }
                    })
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
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon, {
                        featureAdded: function (feature) {
                            widget._trigger("featureAdded", null, feature);
                        }
                    })
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
                var button = $("<button class='btn'/>");
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
              //{type: 'drag', icon: 'drag'},
              //{type: 'select', icon: 'select'},
              //{type: 'removeSelected', icon: 'removeSelected'},
              //{type: 'removeAll', icon: 'removeAll'}
            ],
            line: [
              {type: 'drawLine', icon: 'drawLine'},
              {type: 'edit', icon: 'edit'},
              //{type: 'drag', icon: 'drag'},
              //{type: 'select', icon: 'select'},
              //{type: 'removeSelected', icon: 'removeSelected'},
              //{type: 'removeAll', icon: 'removeAll'}
            ],
            polygon: [
              {type: 'drawPolygon', icon: 'drawPolygon'},
              //{type: 'drawRectangle', icon: 'drawRectangle'},
              //{type: 'drawCircle', icon: 'drawRectangle'},
              //{type: 'drawEllipse', icon: 'drawRectangle'},
              //{type: 'donut', icon: 'drawRectangle'},
              {type: 'edit', icon: 'edit'},
              //{type: 'drag', icon: 'drag'},
              //{type: 'select', icon: 'select'},
              //{type: 'removeSelected', icon: 'removeSelected'},
              //{type: 'removeAll', icon: 'removeAll'}
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
            var frames = [];
            var activeFrame = null;
            var self = this;
            var element = $(self.element);
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
            
            // Hide selector if only one schema defined
            if(_.size(this.options.schemes) === 1){
                selector.css('display','none');
            }
            
            // build select options
            $.each(this.options.schemes, function(schemaName){
                var settings = this;
                var option = $("<option/>");
                option.val(schemaName).html(settings.label);

                var layer =  settings.layer = new OpenLayers.Layer.Vector(settings.label, {styleMap: styleMap});
                self.map.addLayer(layer);

                var frame = settings.frame = $("<div/>").addClass('frame').data(settings);
                var tools = settings.tools = $("<div/>").mbDigitizerToolset({items: self.toolsets[settings.featureType.geomType], layer: layer});
                var checkbox = $('<div class="checkbox">\n\
                                <label><input class="onlyExtent'+schemaName+'" type="checkbox" checked="true">current extent</label>\n\
                             </div>');
                
                var columns = [];
                var newFeatureDefaultProperties = {};
                
                $.each(settings.tableFields, function(fieldName){
                    newFeatureDefaultProperties[fieldName] = ""; 
                    columns.push({data: "properties."+fieldName, title: this.label});
                });
                
                var table = settings.table = $("<div/>").resultTable({
                    lengthChange: false,
                    pageLength: 10,
                    searching: false,
                    info: false,
                    processing: false,
                    ordering: true,
                    paging: true,
                    selectable: false,
                    autoWidth: false,
                    columns:  columns,
                    buttons: [
                        {
                            title: 'E',
                            className: 'edit',
                            onClick: function(feature, ui) {
                                var olFeature;
                                if(feature.hasOwnProperty('isNew') ){
                                    olFeature =  layer.getFeatureById(feature.id);
                                }else{
                                    olFeature = self.activeLayer.getFeatureByFid(feature.id);
                                }                         
                                self._openFeatureEditDialog(olFeature);
                            }
                        },
                        {
                            title: 'X',
                            className: 'delete',
                            onClick: function(feature, ui) {
                                var tr = ui.closest('tr');
                                var tableApi = table.resultTable('getApi');
                                var row = tableApi.row(tr);
                                var olFeature;
                                
                                if(feature.hasOwnProperty('isNew')){
                                    olFeature =  layer.getFeatureById(feature.id);
                                }else{
                                    olFeature =  layer.getFeatureByFid(feature.id);
                                    if(!Mapbender.confirm('Aus der Datenbank löschen?')){
                                        return;
                                    };

                                    self.query('delete',{
                                        schema: schemaName,
                                        feature: feature
                                    }).done(function(fid){                 
                                        $.notify('erfolgreich gelöscht','info');
                                    });
                                }
                                
                                // remove from map
                                olFeature.layer.removeFeatures(olFeature);
                                
                                // remove from table
                                row.remove().draw();                         
                            }
                        }
                ]
                });

                settings.schemaName = schemaName;

                frame.append(tools);
                
                frame.append(checkbox);
                frame.append(table);
                
                frames.push(settings);
                frame.css('display','none');
                
                frame.data(settings);
                
                element.append(frame);
                option.data(settings);
                
                selector.append(option);
                
                tools.bind('mbdigitizertoolsetfeatureadded',function(event,feature){
                    var geoJSON = new OpenLayers.Format.GeoJSON();
                    var srid = feature.layer.map.getProjectionObject().proj.srsProjNumber;
                    var properties = jQuery.extend(true, {}, newFeatureDefaultProperties); // clone from newFeatureDefaultProperties
                    var jsonGeometry;
                    
                    eval("jsonGeometry="+geoJSON.write(feature.geometry));
                    
                    var jsonFeature = {
                        id: feature.id,
                        isNew: true,
                        properties: properties,
                        geometry:  jsonGeometry,
                        type: "Feature",
                        srid: srid
                    };

                    var tableApi = table.resultTable('getApi');
                    tableApi.rows.add([jsonFeature]);
                    tableApi.draw();
                    
                    if(settings.openFormAfterEdit === true){
                        self._openFeatureEditDialog(feature);
                    }
                    
                });
                
                checkbox.delegate('input','change',function(){
                    self._getData();
                });
                
                
            });

            function onSelectorChange(){
                var option = selector.find(":selected");
                var settings = option.data();
                var frame = settings.frame;
                var table = settings.table;   

                if(activeFrame){
                    activeFrame.css('display','none');
                    var tableApi = activeFrame.data("table").resultTable('getApi');
                    var layer = activeFrame.data("layer");
                    
                    layer.removeAllFeatures();
                    tableApi.clear();
                }
                
                activeFrame = frame;
                activeFrame.css('display','block');
                
                self.activeLayer = settings.layer;
                self.schemaName = settings.schemaName;
                self.currentSettings = settings;
                
                var table = self.currentSettings.table;
                var tableApi = table.resultTable('getApi');
            
                table.off('mouseenter','mouseleave','click');
            
                table.delegate("tbody > tr", 'mouseenter', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }
                    self._highlightFeature(jsonData,true);
                });

                table.delegate("tbody > tr", 'mouseleave', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }
                    self._highlightFeature(jsonData,false);
                });

                table.delegate("tbody > tr", 'click', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }                  
                    self._zoomToFeature(jsonData);
                });
  
                self._getData();
            }

            selector.on('change',onSelectorChange);
            onSelectorChange();
            
            // register events
            this.moveEndEvent = function(){
                self._getData();
            };
            this.map.events.register("moveend", this.map, this.moveEndEvent);          
            this.map.events.register('click', this, this._mapClick);
            
            var featureoverEvent = function(e){
                var feature = e.feature;
                var table = self.currentSettings.table;
                var tableApi = table.resultTable('getApi');
                if(feature.layer.name === self.currentSettings.label){
                    $.each(tableApi.data(),function(idx, jsonFeature){
                        if(jsonFeature.id === feature.fid){
                            $(tableApi.rows(idx).nodes()).css('color','red');
                        }
                    });
                    feature.layer.drawFeature(feature,'select');
                }
            };
            
            var featureoutEvent = function(e){
                var feature = e.feature;
                var table = self.currentSettings.table;
                var tableApi = table.resultTable('getApi');
                if(feature.layer.name === self.currentSettings.label){
                    $.each(tableApi.data(),function(idx, jsonFeature){
                        if(jsonFeature.id === feature.fid){
                            $(tableApi.rows(idx).nodes()).css('color','green');
                        }
                    });
                    feature.layer.drawFeature(feature,'default');
                }
            };

            this.map.events.register('featureover', this, featureoverEvent);
            this.map.events.register('featureout', this, featureoutEvent);
            this.map.resetLayersZIndex();
            this._trigger('ready');
        },
        
        _openFeatureEditDialog: function (feature) {
            var self = this;

            if(self.currentPopup){
                self.currentPopup.popupDialog('close');
            }

            var popup= $("<div/>").popupDialog({
                title: 'Attribute',
                width: "423px",
                buttons: [{
                        text: "Speichern",
                        click: function() {
                            var form = $(this).closest(".ui-dialog-content");
                            var formData = form.formData();
                            var wkt = new OpenLayers.Format.WKT().write(feature);
                            var srid = self.map.getProjectionObject().proj.srsProjNumber;
                            var jsonFeature = {
                                    properties: formData,
                                    geometry:   wkt,
                                    srid: srid
                                };

                            if(feature.fid){
                                jsonFeature.id = feature.fid;
                            }
                            form.disableForm();
                            
                            self.query('save',{
                                schema: self.schemaName,
                                feature: jsonFeature
                            }).done(function(featureCollection){
                                
                                var dbFeature = featureCollection.features[0];
                                var table = self.currentSettings.table;
                                var tableApi = table.resultTable('getApi');
                                var isNew = !feature.hasOwnProperty('fid');
                                var tableJson = null;

                                // search jsonData from table
                                $.each(tableApi.data(),function(i,jsonData){
                                    if(isNew){
                                       if(jsonData.id == feature.id){
                                           delete jsonData.isNew;
                                           tableJson = jsonData;
                                           return false
                                       }
                                    }else{
                                        if(jsonData.id == feature.fid){
                                           tableJson = jsonData;
                                           return false
                                        }
                                    }
                                })

                                // Merge object2 into object1
                                $.extend( tableJson, dbFeature );
                                
                                // Redraw table fix
                                // TODO: find how to drop table cache...
                                $.each(tableApi.$("tbody > tr"), function (i, tr) {
                                    var row = tableApi.row(tr);
                                    if(row.data() == tableJson){
                                        row.data(tableJson);
                                        return false;
                                    }
                                })     
                                tableApi.draw();
                                
                                // Update open layer feature to...
                                feature.fid = tableJson.id;
                                feature.data = tableJson.properties;
                                feature.attributes = tableJson.properties;

                                form.enableForm();
                                self.currentPopup.popupDialog('close');
                                $.notify('erfolgreich gespeichert','info');
                            });
                        }
                    }]
            });
            
            self.currentPopup = popup;
            popup.generateElements({items: self.currentSettings.formItems});
            console.log(feature.data);
            popup.formData(feature.data); 
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
            var feature = features[0];
            
            self._openFeatureEditDialog(feature);
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
            var settings = self.currentSettings;
            var proj = this.map.getProjectionObject();
            var extent = this.map.getExtent();
            var tableApi = settings.table.resultTable('getApi');
           
                    
            var request = {
                srid: proj.proj.srsProjNumber,
                //intersectGeometry: extent.toGeometry().toString(),
                maxResults: 100,
                schema: settings.schemaName
            };
            
            if($('.onlyExtent'+settings.schemaName).prop('checked')){
                request.intersectGeometry = extent.toGeometry().toString();
            }

            self.query('select', request).done(function(geoJson) {
                if(geoJson) {
                    
                    // - find all new (not saved) features  
                    // - collect it to the select result list
                    $.each(tableApi.data(),function(i, tableJson){
                        if(tableJson.hasOwnProperty('isNew')){
                            geoJson.features.push(tableJson);
                        }
                    });
       
                    settings.layer.removeAllFeatures();
                    var geojson_format = new OpenLayers.Format.GeoJSON();
                    var olFeatures = geojson_format.read(geoJson);
                    settings.layer.addFeatures(olFeatures);

                    tableApi.clear();
                    tableApi.rows.add(geoJson.features);
                    tableApi.draw();
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
            //request.schema = this.activeSchemaName;
            return $.ajax({
                url:         widget.elementUrl + uri,
                type:        'POST',
                contentType: "application/json; charset=utf-8",
                dataType:    "json",
                data:        JSON.stringify(request)
            }).error(function(xhr) {
                $.notify("XHR error:" + JSON.stringify(xhr.responseText));
                console.log("XHR Error:", xhr);
            });
        },
        
        _highlightFeature: function(jsonFeature,highlight){
            var layer = this.activeLayer;
            var feature = jsonFeature.hasOwnProperty('isNew') ? layer.getFeatureById(jsonFeature.id): layer.getFeatureByFid(jsonFeature.id);
            
            if(!feature){
                return;
            }
            
            if(highlight === true){
                feature.renderIntent = 'select';
            }else{
                feature.renderIntent = 'default';
            }
            this.activeLayer.redraw();
        },
        
        _zoomToFeature: function(jsonFeature){
            var layer = this.activeLayer;
            var feature = jsonFeature.hasOwnProperty('isNew') ? layer.getFeatureById(jsonFeature.id): layer.getFeatureByFid(jsonFeature.id);
            var bounds = feature.geometry.getBounds();
            this.map.zoomToExtent(bounds);
        },
        
    });

})(jQuery);

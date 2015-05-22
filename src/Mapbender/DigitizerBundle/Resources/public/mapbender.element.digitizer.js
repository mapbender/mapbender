(function($){

    /**
     * Example:
     *     confirmDialog({html: "Feature löschen?", title: "Bitte bestätigen!", onSuccess:function(){
                  return false;
           }});
     * @param options
     * @returns {*}
     */
    function confirmDialog(options) {
        var dialog = $("<div class='confirm-dialog'>" + (options.hasOwnProperty('html') ? options.html : "") + "</div>").popupDialog({
            title:       options.hasOwnProperty('title') ? options.title : "",
            maximizable: false,
            dblclick:    false,
            minimizable: false,
            resizable:   false,
            collapsable: false,
            buttons:     [{
                text:  "OK",
                click: function(e) {
                    if(!options.hasOwnProperty('onSuccess') || options.onSuccess(e)) {
                        dialog.popupDialog('hide');
                    }
                    return false;
                }
            }, {
                text:    "Abbrechen",
                'class': 'critical',
                click:   function(e) {
                    if(!options.hasOwnProperty('onCancel') || options.onSuccess(e)) {
                        dialog.popupDialog('hide');
                    }
                    return false;
                }
            }]
        });
        return dialog;
    }

    /**
     * Digitizing tool set
     *
     * @author Andriy Oblivantsev <eslider@gmail.com>
     * @author Stefan Winkelmann <stefan.winkelmann@wheregroup.com>
     *
     * @copyright 20.04.2015 by WhereGroup GmbH & Co. KG
     */
    $.widget("mapbender.mbDigitizer", {
        options: {},
        toolsets: {
            point: [
              {type: 'drawPoint'},
              {type: 'modifyFeature'},
              //{type: 'moveFeature'},
              //{type: 'selectFeature'},
              //{type: 'removeSelected'}
              //{type: 'removeAll'}
            ],
            line: [
              {type: 'drawLine'},
              {type: 'modifyFeature'},
              //{type: 'moveFeature'},
              //{type: 'selectFeature'},
              //{type: 'removeSelected'},
              //{type: 'removeAll'}
            ],
            polygon: [
              {type: 'drawPolygon'},
              //{type: 'drawRectangle'},
              //{type: 'drawCircle'},
              //{type: 'drawEllipse'},
              //{type: 'drawDonut'},
              {type: 'modifyFeature'},
              //{type: 'moveFeature'},
              //{type: 'selectFeature'},
              //{type: 'removeSelected'},
              //{type: 'removeAll'}
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

        _setup: function() {
            var frames = [];
            var activeFrame = null;
            var widget = this;
            var element = $(widget.element);
            var titleElement = $("> div.title", element);
            var selector = widget.selector =  $("select.selector", element);
            var map = widget.map = $('#' + widget.options.target).data('mapbenderMbMap').map.olMap;
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
            var styleMap = new OpenLayers.StyleMap({
                'default': defaultStyle,
                'select':  selectStyle
            }, {extendDefault: true});

            // Hide selector if only one schema defined
            if(_.size(this.options.schemes) === 1){
                titleElement.html(_.toArray(this.options.schemes)[0].label);
                selector.css('display','none');
            }else{
                titleElement.css('display','none');
            }

            // build select options
            $.each(widget.options.schemes, function(schemaName){
                var settings = this;
                var option = $("<option/>");
                var layer =  settings.layer = new OpenLayers.Layer.Vector(settings.label, {styleMap: styleMap});

                option.val(schemaName).html(settings.label);
                widget.map.addLayer(layer);

                var frame = settings.frame = $("<div/>").addClass('frame').data(settings);
                var columns = [];
                var newFeatureDefaultProperties = {};
                if( !settings.hasOwnProperty("tableFields")){
                    console.error("Digitizer table fields isn't defined!",settings );
                }

                $.each(settings.tableFields, function(fieldName){
                    newFeatureDefaultProperties[fieldName] = "";
                    columns.push({data: "properties."+fieldName, title: this.label});
                });

                var table = settings.table = $("<div/>").resultTable({
                    lengthChange: false,
                    pageLength: 10,
                    searching: false,
                    info: true,
                    processing: false,
                    ordering: true,
                    paging: true,
                    selectable: false,
                    autoWidth: false,
                    columns:  columns,
                    buttons: [
                        {
                            title: 'Edit',
                            className: 'edit',
                            onClick: function(feature, ui) {
                                var olFeature;
                                if(feature.hasOwnProperty('isNew') ){
                                    olFeature =  layer.getFeatureById(feature.id);
                                }else{
                                    olFeature = widget.activeLayer.getFeatureByFid(feature.id);
                                }
                                widget._openFeatureEditDialog(olFeature);
                            }
                        },
                        {
                            title: 'Remove',
                            className: 'remove',
                            cssClass: 'critical',
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

                                    widget.query('delete',{
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

                var toolset = widget.toolsets[settings.featureType.geomType];
                if(settings.hasOwnProperty("toolset")){
                    toolset = settings.toolset;
                }

                frame.generateElements({
                    children: [{
                        type:           'digitizingToolSet',
                        children:       toolset,
                        layer:          layer,
                        onFeatureAdded: function(event,feature) {
                            var geoJSON = new OpenLayers.Format.GeoJSON();
                            var srid = feature.layer.map.getProjectionObject().proj.srsProjNumber;
                            var properties = jQuery.extend(true, {}, newFeatureDefaultProperties); // clone from newFeatureDefaultProperties
                            var jsonGeometry;

                            eval("jsonGeometry=" + geoJSON.write(feature.geometry));

                            var jsonFeature = {
                                id:         feature.id,
                                isNew:      true,
                                properties: properties,
                                geometry:   jsonGeometry,
                                type:       "Feature",
                                srid:       srid
                            };

                            var tableApi = table.resultTable('getApi');
                            tableApi.rows.add([jsonFeature]);
                            tableApi.draw();

                            if(settings.openFormAfterEdit) {
                                widget._openFeatureEditDialog(feature);
                            }
                        }
                    }, {
                        type:     'checkbox',
                        cssClass: 'onlyExtent' + schemaName,
                        title:    'current extent',
                        change:   function() {
                            widget._getData();

                        }
                    }]
                });

                frame.append(table);

                frames.push(settings);
                frame.css('display','none');

                frame.data(settings);

                element.append(frame);
                option.data(settings);

                selector.append(option);
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

                widget.activeLayer = settings.layer;
                widget.schemaName = settings.schemaName;
                widget.currentSettings = settings;

                var table = widget.currentSettings.table;
                var tableApi = table.resultTable('getApi');

                table.off('mouseenter','mouseleave','click');

                table.delegate("tbody > tr", 'mouseenter', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }
                    widget._highlightFeature(jsonData,true);
                });

                table.delegate("tbody > tr", 'mouseleave', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }
                    widget._highlightFeature(jsonData,false);
                });

                table.delegate("tbody > tr", 'click', function() {
                    var tr = this;
                    var row = tableApi.row(tr);
                    var jsonData = row.data();
                    if(!jsonData){
                        return;
                    }
                    widget.zoomToJsonFeature(jsonData);
                });

                widget._getData();
            }

            selector.on('change',onSelectorChange);
            onSelectorChange();

            // register events
            this.moveEndEvent = function(){
                widget._getData();
            };
            this.map.events.register("moveend", this.map, this.moveEndEvent);
            this.map.events.register('click', this, this._mapClick);

            var featureoverEvent = function(e){
                var feature = e.feature;
                var table = widget.currentSettings.table;
                var tableWidget = table.data('visUiJsResultTable');

                if(feature.layer.name === widget.currentSettings.label){

                    var jsonFeature = tableWidget.getDataById(feature.fid);
                    var domRow = tableWidget.getDomRowByData(jsonFeature);
                    if(!domRow){
                        return;
                    }
                    tableWidget.showByRow(domRow);
                    domRow.addClass('hover');
                    feature.layer.drawFeature(feature,'select');
//                    debugger;
                }
            };

            var featureoutEvent = function(e){
                var feature = e.feature;
                var table = widget.currentSettings.table;
                var tableWidget = table.data('visUiJsResultTable');

                if(feature.layer.name === widget.currentSettings.label){
                    var jsonFeature = tableWidget.getDataById(feature.fid);
                    var domRow = tableWidget.getDomRowByData(jsonFeature);
                    if(!domRow){
                        return;
                    }
                    domRow.removeClass('hover');
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
            var popupConfiguration = {
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

                            var errorInputs = $(".has-error", dialog);
                            var hasErrors = errorInputs.size() > 0;

                            if( !hasErrors ){
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
                        }
                    }]
            };

            if(self.currentSettings.hasOwnProperty('popup')){
                $.extend(popupConfiguration,self.currentSettings.popup);
            }

            var dialog= $("<div/>");
            dialog.generateElements({children: self.currentSettings.formItems});
            dialog.popupDialog(popupConfiguration);
            self.currentPopup = dialog;
            dialog.formData(feature.data);
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
                maxResults: settings.hasOwnProperty('maxResults')?settings.maxResults:1000,
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

        /**
         * Zoom to JSON feature
         *
         * @param jsonFeature
         */
        zoomToJsonFeature: function(jsonFeature){
            var layer = this.activeLayer;
            var feature = jsonFeature.hasOwnProperty('isNew') ? layer.getFeatureById(jsonFeature.id): layer.getFeatureByFid(jsonFeature.id);
            var bounds = feature.geometry.getBounds();
            this.map.zoomToExtent(bounds);
        }
    });

})(jQuery);

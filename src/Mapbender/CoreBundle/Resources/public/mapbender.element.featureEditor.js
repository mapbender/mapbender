(function($) {

$.widget("mapbender.mbFeatureEditor", $.ui.dialog, {
    options: {
        width: 'auto',
        height: 600,
        zIndex: 1020,
        position: {
            my: 'left top',
            at: 'left top',
            offset: '20 20'
        }
    },

    map: null,
    fields: null,
    data: null,
    pointLayer: null,
    feature: null,
    control: null,
    
    _create: function() {
        this._super('_create');
        this._super('option', 'width', this.options.width);
        this._super('option', 'height', this.options.height);
        this._super('option', 'zIndex', this.options.zindex);
        this._super('option', 'title', this.options.title);
        this._super('option', 'position', $.extend({}, this.options.position, {
            of: window
        }));
    },

    open: function() {     
        this._super('open');

        this.map = $('#' + this.options.target).data('mbMap');
        
        this._createTableHead();
        this._getData();
        this._createForm();
        this._createFeatureLayer();
        this._drawFeatures();
    },
    
    close: function() {
        this._super('close');
        this.map.map.olMap.removeLayer(this.pointLayer);
    },
    
    _createTableHead: function() {
        var fields = this.options.fields;
        var tablehead = $('#tablehead', this.element);
        tablehead.empty();
        for(key in fields) {
            tablehead.append($('<th></th>', {
                'html': fields[key].label
            }));
        }
    },
    
   _fillTable: function(data) {
        var self = this;
        this.data = data;
        $("#page2").hide();$("#page1").show(); 
        var fields = this.options.fields;
        var tablecontent = $('#tablecontent', this.element);
        tablecontent.empty();

        for(var i=0;i<data.length;i++) {
            $('#tablecontent').append('<tr id=row'+i+'></tr>');
            for(key in fields) {
                $('#row'+i).append('<td>'+data[i][key]+'</td>');
            }
            $('#row'+i).append('<td id="buttontd"><button class="editbtn" name="editbtn'+i+'">edit</button></td>');
        }
        $('.editbtn').unbind('click');
        $('.editbtn').click(function () {
            $("#page1").hide();
            $("#page2").show();
            var featureName = $(this).attr('name');  
            self._selectFeature(featureName.substr(7));
        });    
    },
    
    _createForm: function () {
        var self = this;
        $('#form').empty();
        var fields = this.options.fields;
        for(key in fields) {
            $('#form').append('<tr><td>'+fields[key].label+':</td>\n\
                               <td><input name="'+key+'" size="10" type="text" /></td></tr>');  
        }
        $('#savebutton').click(function () {
            self._updateDB();
            self._unselectFeature();
        });
        $('#delbutton').unbind('click');
        $('#delbutton').click(function () {         
            self._deleteConfirmDialog('Do you want to delete this record ?',
                        function () {
                            console.log('You clicked OK');
                            self._deleteFeature();
                        },
                        function () {
                            console.log('You clicked Cancel');
                        },
                        'Confirm Delete');
            self._unselectFeature();
        });
        $("#backbutton").click(function () {
             $("#page2").hide();
             $("#page1").show();
             $("#featureposition").hide();
             self._unselectFeature();
        });
        $('#showpositiondiv').click(function () {
            $("#featureposition").show();
            $('input[name="xposition"]').val("");
            $('input[name="yposition"]').val("");
            self._registerMapClickEvent();
        });
        $('#setposition').click(function () {
            self._updatePosition();
            self._unselectFeature();
            $("#featureposition").hide();
        });
    },
    
    _fillForm: function (i) {
        var uniqueId = this.options.unique_id;
        $('#form').append('<input name="'+uniqueId+'" type="hidden" />');
        $('input[name="'+uniqueId+'"]').val(this.data[i][uniqueId]);
        var fields = this.options.fields;
        for(key in fields) {
            $('input[name="'+key+'"]').val(this.data[i][key]);
        }
    },
    
    _getData: function () {
        var self = this;
        var olmap = this.map.map.olMap;
        var proj = olmap.getProjectionObject();
        var newProj = new OpenLayers.Projection("EPSG:"+this.options.srs);
        var extent = olmap.getExtent();
        
        extent.transform(proj, newProj);
        var data = {
            srs: proj.proj.srsProjNumber,
            bottom: extent.bottom,
            left: extent.left,
            top: extent.top,
            right: extent.right
        };
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id')+ '/select';
            $.ajax({
                url: url,
                type: 'POST',
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                async: false,
                data: JSON.stringify(data),
                success: function(data) {
                    if(data){
                        self._fillTable(data);
                    }
                }
            })
    },
    
    _updateDB: function () {
        var self = this;
        var data = {};
        var uniqueId = this.options.unique_id;
        data[uniqueId] = $('input[name="'+uniqueId+'"]').val();
        var fields = this.options.fields;
        for(key in fields) {
            data[key] = $('input[name="'+key+'"]').val();
        }         
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id')+ '/update';
        $.ajax({
            url: url,
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(data),
            success: function(data) {
                $("#page2").hide();
                $("#page1").show();
            }
        })
    },
    
    _updatePosition: function () {
        var self = this;
        var olmap = this.map.map.olMap;
        var proj = olmap.getProjectionObject();
        var newProj = new OpenLayers.Projection("EPSG:"+this.options.srs);
        var p = new OpenLayers.LonLat(this.feature.geometry.x,this.feature.geometry.y);
        p.transform(proj, newProj);
        
        var data = {
                x:  p.lon,
                y:  p.lat
        };
        var uniqueId = this.options.unique_id;
        data[uniqueId] = $('input[name="'+uniqueId+'"]').val();
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id')+ '/position';
        $.ajax({
            url: url,
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(data),
            success: function(data) {
                $("#page2").hide();
                $("#page1").show();
                self._redrawLayers();
            }
        })
    },
    
    _deleteFeature: function () {
        var self = this;
        var data = {};
        var uniqueId = this.options.unique_id;
        data[uniqueId] = $('input[name="'+uniqueId+'"]').val();     
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id')+ '/delete';
        $.ajax({
            url: url,
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            data: JSON.stringify(data),
            success: function(data) {
                $("#page2").hide();
                $("#page1").show();
                self._redrawLayers();
            }
        })
    },
    
    _drawFeatures: function () {
        this.pointLayer.removeAllFeatures();
        var featureArray = [];
        for(var i=0;i<this.data.length;i++){
            var point = new OpenLayers.Geometry.Point(this.data[i]['x'],this.data[i]['y']);
            var feature = new OpenLayers.Feature.Vector( point,{fid: i},null);
            feature.fid = i; 
            featureArray[i] = feature;                                                           
        }
        this.pointLayer.addFeatures(featureArray);
    },
    
    _createFeatureLayer: function () {
        var self = this;
        var defaultStyle = new OpenLayers.Style({
            'pointRadius': 10,
            'fillOpacity': 0.2,
            'strokeOpacity': 0
            //'graphicOpacity' : 0,
            //'externalGraphic': ""
        });
        var selectStyle = new OpenLayers.Style({
            'pointRadius': 11,
            'graphicOpacity' : 1,
            'externalGraphic': this.options.marker
        });
        var styleMap = new OpenLayers.StyleMap({'default': defaultStyle,
                                                'select': selectStyle});
        this.pointLayer = new OpenLayers.Layer.Vector("Layer Name", {styleMap: styleMap});
        this.map.map.olMap.addLayer(this.pointLayer);
        
        this.control  = new OpenLayers.Control.SelectFeature(this.pointLayer, 
                            {onSelect: function(feature){self._selectFeature(feature.fid)}}) 
        this.map.map.olMap.addControl(this.control);
        this.control.activate();  
        
        this.map.map.olMap.events.register("moveend", this.map.map.olMap , 
                                                function() {self._getData();
                                                            self._drawFeatures();});
    },
    
    _selectFeature: function (i) {
        var self = this;
        var olmap = this.map.map.olMap;
        $("#featureposition").hide();
        if (this.feature != null){
            this.feature.renderIntent = 'default';
        }
        this.feature = this.pointLayer.getFeatureByFid(i);
        this.feature.renderIntent = 'select';
        this.pointLayer.redraw();
        var p = new OpenLayers.LonLat(this.feature.geometry.x,this.feature.geometry.y);
        //olmap.setCenter(p, olmap.getZoom(), false, true);
        this._fillForm(i);
        $("#page1").hide();$("#page2").show();
    },
    
    _unselectFeature: function () {    
         this.feature.renderIntent = 'default';
         this.pointLayer.redraw();
         this.map.map.olMap.events.unregister("click", this.map.map.olMap, this.mapClickEvent );
    },
    
    _registerMapClickEvent: function () {
        var self = this;
        this.mapClickEvent = function(e){
            var lonlat = self.map.map.olMap.getLonLatFromViewPortPx(e.xy);
            self.feature.geometry.x = lonlat.lon;
            self.feature.geometry.y = lonlat.lat;
            $('input[name="xposition"]').val(lonlat.lon);
            $('input[name="yposition"]').val(lonlat.lat);
            self.feature.renderIntent = 'select';
            self.pointLayer.redraw();       
        };  
        this.map.map.olMap.events.register("click", this.map.map.olMap , this.mapClickEvent);
    },
    
    _redrawLayers: function (){
        var layers = this.map.map.olMap.layers;
        for(var i = 0; i < layers.length; i++) {
            var layer = layers[i];
            layer.redraw(true);
        }
    },
    
    _deleteConfirmDialog: function (dialogText, okFunc, cancelFunc, dialogTitle){
        $('<div style="padding: 10px; max-width: 500px; word-wrap: break-word;">' + dialogText + '</div>').dialog({
            draggable: false,
            modal: true,
            resizable: false,
            width: 'auto',
            title: dialogTitle || 'Confirm',
            minHeight: 75,
            buttons: {
                OK: function () {
                    if (typeof (okFunc) == 'function') { setTimeout(okFunc, 50); }
                    $(this).dialog('destroy');
                },
                Cancel: function () {
                    if (typeof (cancelFunc) == 'function') { setTimeout(cancelFunc, 50); }
                    $(this).dialog('destroy');
                }
            }
        });
    }
    
    
});

})(jQuery);
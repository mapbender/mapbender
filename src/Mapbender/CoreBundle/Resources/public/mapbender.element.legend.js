(function($) {

$.widget("mapbender.mbLegend", {
    options: {
        title: 'Legende',
        autoOpen: true,
        target: null,
        nolegend: "No legend available",
        maxDialogWidth: $(window).width() - 100,
        maxDialogHeight: $(window).height() - 100,
    
        maxImgWidth: 0,
        maxImgHeight: 0
    },

    _create: function() {
        if(this.options.target === null
            || this.options.target.replace(/^\s+|\s+$/g, '') === ""
            || !$('#' + this.options.target)){
            alert('The target element "map" is not defined for a Legend Dialog.');
            return;
        }
        var self = this;
        $(document).one('mapbender.setupfinished', function() {
            $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
        });
    },

    _setup: function() {
        var self = this;
        var me = $(this.element);
        if(this.options.elementType === "dialog") {
            this.element.dialog({
                width: 500,
                autoOpen: false,
                 heightStyle: "content",
                title: self.options.title
            });
        }
        if(this.options.autoOpen){
            this.open();
        }
    },
    
    _checkMaxImgWidth: function(val){
        if(this.options.maxImgWidth < val)
            this.options.maxImgWidth = val;
    },
    
    _checkMaxImgHeight: function(val){
        if(this.options.maxImgHeight < val)
            this.options.maxImgHeight = val;
    },
    _getLayers: function() {
        var self = this;
        var mbMap = $('#' + this.options.target).data('mbMap');
        var layers = mbMap.map.layers();
        var allLayers = [];fi
        $.each(layers, function(idx, val){
            if (!val.visible()){return ;}
            allLayers.push(self._getLayer(val));
        });
//        for(var i = 0; i < layers.length; i++){
//            if (!layers[i].visible()){return ;}
//            allLayers.push(self._getLayer(layers[i]));
//        }
        return allLayers;
    },
    
    _getLayer: function(layer) {
        return {title: layer.label, sublayers: this._getSublayers(layer)};
    },
    
    _getSublayers: function(layer){
        var self = this;
        var sublayers = [];
        if(layer.options.type == "wms") { // wms & wmc
            if(layer.options.wms_parameters) { // wmc
                //@TODO
            } else { // wms
//                $.each(layer.options.allLayers, function(idx, val_) {
//                    sublayers.push(self._getSublayer(val_, "wms"));
//                });
                for(var i = 0; i < layer.options.allLayers.length; i++){
                    sublayers.push(self._getSublayer(layer.options.allLayers[i], "wms"));
                }
            }
        } else  if(layer.options.type == "wmts") { // wmts
            //@TODO
        }
        return sublayers;
    },
    
    _getSublayer: function(sublayer, type){
        var self = this;
        var sublayerLeg = {title: sublayer.title};
        if(type === "wmc"){
            //@TODO
        } else if(type === "wms"){
            if(sublayer.legend) {
                sublayerLeg["legend"] = sublayer.legend;
            }
        } else if(type === "wtms"){
            //@TODO
        }
        return sublayerLeg;
    },
    
    _createLegend: function(layers, layidx, sublayidx, html, reshtml) {
        var self = this;
        if(layers.length > layidx){
                var layer = layers[layidx];
                if(layers[layidx].sublayers.length > sublayidx){
                    if(layers[layidx].sublayers[sublayidx].legend){
                        $(self.element).find("#imgtest").html('<img id="testload" style="display: none;" src="' + layers[layidx].sublayers[sublayidx].legend.url + '"></img>');
                        $(self.element).find("#imgtest #testload").load(function() {
//                            var width = this.width, height = this.height;
                            self._checkMaxImgWidth(this.width);
                            self._checkMaxImgHeight(this.height);
    //                        window.console && console.log( sublayer.legend.url);
                            html += '<h3><a href="#">' + layers[layidx].sublayers[sublayidx].title + '</a></h3>';
                            html += '<div class="legend-img-div"><img src="' + layers[layidx].sublayers[sublayidx].legend.url + '"></img></div>';
                            self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                        }).error(function() {
                            html += '<h3><a href="#">' + layers[layidx].sublayers[sublayidx].title + '</a></h3>';
                            html += '<div class="legend-text-div">' + self.options.nolegend + ' </div>';
                            self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                        });
                    } else {
                        html += '<h3><a href="#">' + layers[layidx].sublayers[sublayidx].title + '</a></h3>';
                        html += '<div class="legend-text-div">' + self.options.nolegend + ' </div>';
                        self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                    }
                } else {
                    var html_ = ' <div class="layerlegends">'
                         +  ' <h3><a href="#">' + layer.title + '</a></h3>'
                         +  ' <div class="sublayerlegends">';
                    html_+=  html;
                    html_+=' </div>'
                         +  ' </div>';
                    reshtml += html_;
                    self._createLegend(layers, ++layidx, 0, "", reshtml);
                }
        } else {
            $(self.element).find("#imgtest").html("");
            $(this.element).find('#legends:eq(0)').html(reshtml);
            if(this.options.elementType === "dialog") {
                this.element.dialog("option", "maxHeight", self.options.maxDialogHeight + "px");
                this.element.dialog("option", "maxWidth", self.options.maxDialogWidth);
                this.element.dialog("option", "minWidth", self.options.maxImgWidth != 0 ? self.maxImgWidth + 100 : 300);
                this.element.dialog("option", "width", self.options.maxImgWidth != 0 ? self.maxImgWidth + 200 : 300);
//                this.element.dialog({
//                    maxHeight: self.options.maxDialogHeight,
//                    maxWidth: self.options.maxDialogWidth,
//                    minWidth: self.options.maxImgWidth != 0 ? self.maxImgWidth + 100 : 300,
//                    width: self.options.maxImgWidth != 0 ? self.maxImgWidth + 200 : 600});
                this.element.dialog('open');
                $(this.element).css({
                    "max-height": (this.options.maxDialogHeight - 50) +"px"
                });
            }
            if(this.options.displayType === 'accordion'){
                $(this.element).find('.sublayerlegends').each(function(){
                    $(this).accordion({autoHeight: false, collapsible: true, active: false});
                });
                $(this.element).find('.layerlegends').each(function(){
                    $(this).accordion({autoHeight: false, collapsible: true, active: false});
                });
            }
        }
    },
    
    open: function() {
        var layers = this._getLayers();
        this._createLegend(layers, 0, 0, "", "");
    }

});

})(jQuery);


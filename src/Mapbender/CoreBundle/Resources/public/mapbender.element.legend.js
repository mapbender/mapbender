(function($) {

    $.widget("mapbender.mbLegend", {
        options: {
            title: 'Legende',
            autoOpen: true,
            target: null,
            noLegend: "No legend available",
            
            elementType: "dialog",
            displayType: "list",
            hideEmptyLayers: true,
            generateGetLegendGraphicUr: false,
            showWmsTitle: true,
            showLayerTitle: true,
            showGroupedLayerTitle: true,
            
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
            var allLayers = [];
            $.each(layers, function(idx, val){
//                if (!val.visible()){
//                    return ;
//                }
                allLayers.push(self._getLayer(val, 1));
            });
            return allLayers;
        },
    
        _getLayer: function(layer, level) {
            return {
                visible: layer.visible() ? '' : ' notvisible',
                title: layer.label, 
                level: level,
                sublayers: this._getSublayers(layer, level + 1)
                };
        },
    
        _getSublayers: function(layer, level){
            var self = this;
            var sublayers = [];
            if(layer.options){
                if(layer.options.type == "wms") { // wms & wmc
                    if(layer.options.wms_parameters) { // wmc
                    //@TODO
                    } else { // wms
                        $.each(layer.options.allLayers.reverse(), function(idx, val_) {
                            sublayers.push(self._getSublayer(val_, "wms", level));
                        });
                    }
                } else  if(layer.options.type == "wmts") { // wmts
                //@TODO
                }
            }
            return sublayers;
        },
    
        _getSublayer: function(sublayer, type, level){
            var self = this;
            var sublayerLeg = {
                visible: '', // visible
                title: sublayer.title,
                level: level,
                isNode: sublayer.sublayers && sublayer.sublayers.length > 0 ? true : false
                };
            if(type === "wmc"){
            //@TODO
            } else if(type === "wms"){
                if(sublayer.legend) {
                    if(sublayer.legend.url){
                        sublayerLeg["legend"] = sublayer.legend;
                    } else if(self.options.generateGetLegendGraphicUrl && sublayer.legend.graphic ){
                        sublayerLeg["legend"] = {url: sublayer.legend.graphic};
                    }
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
                            if(layers[layidx].sublayers[sublayidx].isNode){
                                if(self.options.showGroupedLayerTitle){
                                    html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                }
                            } else {
                                if(self.options.showLayerTitle){
                                    html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                }
                            }
                            html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' image"><img src="' + layers[layidx].sublayers[sublayidx].legend.url + '"></img></li>';
                            self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                        }).error(function() {
                            if(!self.options.hideEmptyLayers){
                                if(layers[layidx].sublayers[sublayidx].isNode){
                                    if(self.options.showGroupedLayerTitle){
                                        html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                    }
                                } else {
                                    if(self.options.showLayerTitle){
                                        html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                    }
                                }
                                html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' text">' + self.options.noLegend + '</li>';
                            } else {
                                if(layers[layidx].sublayers[sublayidx].isNode){
                                    if(self.options.showGroupedLayerTitle){
                                        html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                    }
                                }
                            }
                            self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                        });
                    } else {
                        if(!self.options.hideEmptyLayers){
                            if(layers[layidx].sublayers[sublayidx].isNode){
                                if(self.options.showGroupedLayerTitle){
                                    html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                }
                            } else {
                                if(self.options.showLayerTitle){
                                    html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                }
                            }
                            html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' text">' + self.options.noLegend + '</li>';
                        } else {
                            if(layers[layidx].sublayers[sublayidx].isNode){
                                if(self.options.showGroupedLayerTitle){
                                    html += '<li class="ebene' + layers[layidx].sublayers[sublayidx].level + layers[layidx].sublayers[sublayidx].visible + ' title">' + layers[layidx].sublayers[sublayidx].title + '</li>';
                                }
                            }
                        }
                        self._createLegend(layers, layidx, ++sublayidx, html, reshtml);
                    }
                } else {
                    var html_ = '';
                    if(self.options.showWmsTitle){
                        html_ += '<li class="ebene' + layer.level + ' title">' + layer.title + '</li>';
                    }
                    if(html.length > 0) {
                        html_ += '<ul class="ebene' + layer.level + '">';
                        html_+=  html;
                        html_+=  '</ul>';
                    }
                    reshtml += html_;
                    self._createLegend(layers, ++layidx, 0, "", reshtml);
                }
            } else {
                if(self.options.showWmsTitle){
                    reshtml = '<ul>' + reshtml + '</ul>';
                }
                $(self.element).find("#imgtest").html("");
                $(this.element).find('#legends:eq(0)').html(reshtml);
                if(this.options.elementType === "dialog") {
                    this.element.dialog("option", "maxHeight", self.options.maxDialogHeight + "px");
                    this.element.dialog("option", "maxWidth", self.options.maxDialogWidth);
                    this.element.dialog("option", "minWidth", self.options.maxImgWidth != 0 ? self.maxImgWidth + 100 : 300);
                    this.element.dialog("option", "width", self.options.maxImgWidth != 0 ? self.maxImgWidth + 200 : 300);
                    this.element.dialog('open');
                    $(this.element).css({
                        "max-height": (this.options.maxDialogHeight - 50) +"px"
                    });
                }
                if(this.options.displayType === 'accordion'){
                    $(this.element).find('ul.ebene1').each(function(){
                        $(this).accordion({
                            header: "li.title",
                            autoHeight: false, 
                            collapsible: true, 
                            active: false
                        });
                    });
                    $(this.element).find('.layerlegends').each(function(){
                        $(this).accordion({
                            autoHeight: false, 
                            collapsible: true, 
                            active: false
                        });
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


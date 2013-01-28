(function($) {

$.widget("mapbender.mbLayertree", {
    options: {
        title: 'Table of Contents',
        autoOpen: false,
        target: null
    },
    dlg: null,
    elementUrl: null,
    layerconf : null,
    consts: {service: "service", node: "node", wmslayer: "wmslayer"},

    _create: function() {
        var self = this;
        var me = this.element;
        if(self.options.type === 'dialog'){
            self._initDialog();
        }
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
        // FIXME: fix to on when updating jquery 
        me.find('input[name="enabled"]').live("change",{self:self},self.on_layer_toggle_enabled);
        me.find('input[name="queryable"]').live("change",{self:self},self.on_layer_toggle_queryable);
        $(document).one('mapbender.setupfinished', function() {
            $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
        });
    },
    
    _setup: function(){
        var self = this;
        if(self.options.type === 'dialog' && self.options.autoOpen === true){
            self.open();
        }
        var me = this.element;
        var $map = $("#" + self.options.target).data().mapQuery;
//        $.data($("#" + self.options.target), mapQuery);
        var layers = $map.layers();
        var li_s = "";
        for(var i =0; i < layers.length; i++){
            li_s += self._layerNode(layers[i], self.consts.service, layers[i].olLayer.type);
        }
        me.find("ul").append($(li_s));
        me.find("slider").slider();
        $("ul.layers").each(function(){
            $(this).sortable({cancel: ".scrollable"});
        });
    },

    on_layer_toggle_enabled: function(evt){
        var self = evt.data.self;
        var parent = $(this).parent("li");
        var layerid = parent.attr("data-rootlayerid");
        var layers = $("#" + self.options.target).data().mapQuery.layers();
        var layer = null;
        for(var i=0; i < layers.length; i++){
            if(layers[i].id == layerid){
                layer = layers[i];
            }
        }
        $(this).removeClass("checked");
        
        if(parent.attr('data-type') === self.consts.service){
            if( $(this).is(":checked") ){
                $(this).addClass("checked");
                parent.find('li input[name="enabled"]').attr('checked', true).addClass("checked");
                layer.visible(1);
            }else{
                parent.find('li input[name="enabled"]').attr('checked', false).removeClass("checked");
                layer.visible(0);
            }
        } else if(parent.attr('data-type') === self.consts.node){
            
        } else if(parent.attr('data-type') === self.consts.wmslayer){
            
        }
        
    },
    on_layer_toggle_queryable: function(evt,data){
        var self = evt.data.self;
        $(this).removeClass("checked");
        if( $(this).is(":checked") ){
            $(this).addClass("checked");
        }
    },
    
    _getNodeConfigWms: function(conf) {
        return {
            sel: conf.selected ? 'checked="checked"' : '',
            selable: conf.allow.selected ? '' : 'disabled="disabled"',
            info: conf.info ? 'checked="checked"' : '',
            infoable: conf.allow.info ? '' : 'disabled="disabled"',
            toggle: conf.toggle ? ' opened' : ' closed',
            toggleable: conf.allow.toggle ? ' toggleable' : '',
            reorder: conf.allow.reorder ? 'reorderable' : ''
        };
    },
    
    _layerNodeWms: function(rootlayer, nodetype, config, layer){
        if(nodetype === this.consts.service){
            var li = "";
            var visible_checked = rootlayer.visible() ? 'checked="checked"':''; // @TODO
            li ='<li data-type="'+nodetype+'" data-rootlayerid="'+rootlayer.id+'" title="'+ rootlayer.label +'" class="queryable' + config.toggleable + '" >'
//                    +   '<div>'
                    +   '<input type="checkbox" title="enabled" name="enabled" '+ config.sel +' ' + config.selable + '/>'
                    +   '<input type="checkbox" title="query" name="queryable" '+ config.info +' ' + config.infoable + '/>'
                    +   '<a href="#">' + rootlayer.label + '</a>'
                    +   '<a href="#">&#9776;</a>';
                    +   '<div class="menu">'
                    +   '</div>'
//                    +   '</div>';
             li +=     '<ul class="layers">';
            for(var i = 0; i < rootlayer.olLayer.allLayers.length; i++){
                var sublayer = rootlayer.olLayer.allLayers[i];
                li += this._layerNodeWms(rootlayer, sublayer.sublayers.length > 0 ? this.consts.node : this.consts.wmslayer, config, sublayer);
            }
            li +=      '</ul>'
                +     '</li>';
            return li;
        } else if(nodetype === this.consts.node){
            var conf = rootlayer.options.configuration.configuration;
            var selfconfig =  this._getNodeConfigWms(conf.layertree[layer.id]);
            var li_ =  '<li data-type="'+nodetype+'" data-rootlayerid="'+rootlayer.id+'" title="'+ layer.title +'" class="queryable' + config.toggle + config.toggleable + '" >'
//                    +   '<div>'                    
                    +   '<input type="checkbox" title="enabled" name="enabled" '+ selfconfig.sel +' ' + selfconfig.selable + '/>'
                    +   '<input type="checkbox" title="query" name="queryable" '+ selfconfig.info +' ' + selfconfig.infoable + '/>'
                    +   '<a href="#">' + layer.title + '</a>'
                    +   '<a href="#">&#9776;</a>';
                    +       '<div class="menu">'
                    +       '</div>';
//                    +   '</div>';
                
             li_ +=     '<ul class="layers">';
                for(var i = 0; i < layer.sublayers.length; i++){
                    for(var j = 0; j < rootlayer.olLayer.allLayers.length; j++){
                        if(layer.sublayers[i] == rootlayer.olLayer.allLayers[j].id){
                            var sublayer = rootlayer.olLayer.allLayers[j];
                            li_ += this._layerNodeWms(rootlayer, sublayer.sublayers.length > 0 ? this.consts.node : this.consts.wmslayer, config, sublayer);
                        }
                    }
                }
            li_ +=      '</ul>'
                +     '</li>';
            return li_;
        } else if(nodetype === this.consts.wmslayer){
            var conf = rootlayer.options.configuration.configuration;
            var selfconfig =  this._getNodeConfigWms(conf.layertree[layer.id]);
            return '<li data-type="'+nodetype+'" data-rootlayerid="'+rootlayer.id+'" title="'+ layer.title +'" class="queryable ' + config.toggle + '" >'
//                    +   '<div>'                    
                    +   '<input type="checkbox" title="enabled" name="enabled" '+ selfconfig.sel +' ' + selfconfig.selable + '/>'
                    +   '<input type="checkbox" title="query" name="queryable" '+ selfconfig.info +' ' + selfconfig.infoable + '/>'
                    +   '<span>' + layer.title + '</span>'
                    +   '<a href="#">&#9776;</a>';
                    +       '<div class="menu">'
                    +       '</div>'
//                    +   '</div>'
                    +   '</li>';
        } else {
            return "";
        }
    },

    _layerNode: function(layer){
        if(layer.olLayer.type == "wms"){
            var conf = layer.options.configuration.configuration;
            var li = this._layerNodeWms(layer, this.consts.service, 
                    this._getNodeConfigWms(conf.layertree[conf.id]));
            return li;
        } else {
            return "";
        }
//        return $(
//             '<li data-layerid="'+layer.id+'" title="'+ layer.label +'" class="queryable" ">'
//            +   '<input type="checkbox" title="enabled" name="enabled" '+ visible_checked +'/>'
//            +   '<input type="checkbox" title="query" name="queryable" />'
//            +   '<a href="#">' + layer.label + '</a>'
//            +   '<a href="#">&#9776;</a>'
//            +   '<div class="menu">'
//            +    '</div>'
//            +'</li>'
//        );
        
    },
    
    open: function(){
        if(this.options.type === 'dialog' && this.dlg !== null){
            this.dlg.dialog('open');
        }
    },
    
    _initDialog: function() {
        var self = this;
        if(this.dlg === null) {
            this.dlg = $('<div></div>')
                .attr('id', 'mb-about-dialog')
                .appendTo($('body'))
                .dialog({
                    title: 'About Mapbender',
                    autoOpen: false,
                    modal: true
                });
                self.dlg.html($(self.element));
        }
    },
    
    _destroy: $.noop
});

})(jQuery);


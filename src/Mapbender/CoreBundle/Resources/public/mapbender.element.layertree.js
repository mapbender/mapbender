(function($) {

$.widget("mapbender.mbLayertree", {
    options: {},

    elementUrl: null,
    layerconf : null,

    _create: function() {
        var self = this;
        var me = this.element;
        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
        window.setTimeout(function(){
            var $map = $("#" + self.options.target).data().mapQuery;
            var layers = $map.layers();
            for(var i =0; i < layers.length; i++){
                me.find("ul").append(self._layerNode(layers[i]));
            }
            me.find("slider").slider();
        },1000);
        // FIXME: fix to on when updating jquery 
        me.find('input[name="enabled"]').live("change",{self:self},self.on_layer_toggle_enabled);
        me.find('input[name="queryable"]').live("change",{self:self},self.on_layer_toggle_queryable);
    },

    on_layer_toggle_enabled: function(evt){
        var self = evt.data.self;
        var layerid = $(this).parent("li").attr("data-layerid");
        var layers = $("#" + self.options.target).data().mapQuery.layers();
        var layer = null;
        for(var i=0; i < layers.length; i++){
            if(layers[i].id == layerid){
                layer = layers[i];
            }
        }
        $(this).removeClass("checked");
        if( $(this).is(":checked") ){
            $(this).addClass("checked");
            layer.visible(1);
        }else{
            layer.visible(0);
        }
    },
    on_layer_toggle_queryable: function(evt,data){
        var self = evt.data.self;
        $(this).removeClass("checked");
        if( $(this).is(":checked") ){
            $(this).addClass("checked");
        }
    },

    _layerNode: function(layer, layer_conf){
        if(!layer_conf) {
            layer_conf = layer.olLayer.configuration.configuration;
        }
        var visible_checked = layer.visible() ? 'checked="checked"':'';
        return $(
             '<li data-layerid="'+layer.id+'" title="'+ layer.label +'" class="queryable" ">'
            +   '<input type="checkbox" title="enabled" name="enabled" '+ visible_checked +'/>'
            +   '<input type="checkbox" title="query" name="queryable" />'
            +   '<a href="#">' + layer.label + '</a>'
            +   '<a href="#">&#9776;</a>'
            +   '<div class="menu">'
            +    '</div>'
            +'</li>'
        )
    },
    _destroy: $.noop
});

})(jQuery);


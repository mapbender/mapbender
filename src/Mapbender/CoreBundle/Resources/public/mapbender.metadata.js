/**
 * 
 * Mapbender metadata connector to call metadata
 */
var Mapbender = Mapbender || {};
Mapbender.Metadata = Mapbender.Metadata || {};
Mapbender.Metadata.call = function(mapElementId, sourceOptions, layerOptions){
    var map = $("#" + mapElementId).data("mapbenderMbMap");
    if(map){
        var source = map.getModel().findSource(sourceOptions),
            layerH = map.getModel().findLayer(sourceOptions, layerOptions),
            layer = null;
        if(layerH && layerH.layer){
            layer = layerH.layer;
        }
        if(source && source.length === 1){
            if(layer.options.metadata){
                // TODO check the layer configuration -> layer.options.metadata.url ???
                var link = $('<a href="' + layer.options.metadata.url + '" target="_BLANK"></a>');
                link.appendTo($('body'));
                link.click();
                link.remove();
            }else{
                $.ajax({
                    type: "POST",
                    url: map.elementUrl + "metadata",
                    data: {
                        sourceId: source[0].origId,
                        sourceType: source[0].type,
                        layerId: layer.options.name && layer.options.name !== '' ? layer.options.name : ''
                    },
                    dataType: 'html',
                    success: function(data, textStatus, jqXHR){
                        Mapbender.Metadata.show(data);
                    },
                    error: function(jqXHR, textStatus, errorThrown){
                        Mapbender.error(errorThrown);
                    }
                });
            }
        }
    }
};
Mapbender.Metadata.show = function(content){
    /*if(!Mapbender.Metadata.popup){
        Mapbender.Metadata.popup = */
            new Mapbender.Popup2({
            title: Mapbender.trans("mb.core.metadata.popup.title"),
            modal: false,
            resizable: true,
            draggable: true,
            closeButton: false,
            closeOnESC: false,
            content: [content],
            destroyOnClose: true,
            width: 850,
            height: 600
        });
    /*}else{
        Mapbender.Metadata.popup.content = [content];
    }*/

};


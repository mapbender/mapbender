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
                var metadata_popup = null;
                metadata_popup = new Mapbender.Popup2({
                    title: Mapbender.trans("mb.core.metadata.popup.title"),
                    modal: false,
                    resizable: true,
                    draggable: true,
                    closeButton: false,
                    closeOnESC: false,
                    content: [
                        $.ajax({
                            type: "POST",
                            url: map.elementUrl + "metadata",
                            data: {
                                sourceId: source[0].origId,
                                sourceType: source[0].type,
                                layerName: layer.options.name && layer.options.name !== '' ? layer.options.name : ''
                            },
                            dataType: 'html',
                            complete: function(data){
//                                console.log(metadata_popup.$element);
//                                $(".tabContainer, .tabContainerAlt", $(metadata_popup.$element)).on('click', '.tab', function() {
//                                    var me = $(this);
//                                    me.parent().parent().find(".active").removeClass("active");
//                                    me.addClass("active");
//                                    $("#" + me.attr("id").replace("tab", "container")).addClass("active");
//                                });
                            },
//                            success: function(data, textStatus, jqXHR){
//                                Mapbender.Metadata.show(data);
//                            },
                            error: function(jqXHR, textStatus, errorThrown){
                                Mapbender.error(errorThrown);
                            }
                        })
                    ],
                    destroyOnClose: true,
                    width: 850,
                    height: 600
                });
            }
        }
    }
};
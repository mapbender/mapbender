/**
 * 
 * Mapbender metadata connector to call metadata
 */
var Mapbender = Mapbender || {};
Mapbender.Metadata = Mapbender.Metadata || {};
Mapbender.Metadata.call = function(mapElementId, sourceOptions, layerOptions) {
    var map = $("#" + mapElementId).data("mapbenderMbMap");
    if (map) {
        var source = map.getModel().findSource(sourceOptions),
                layerH = map.getModel().findLayer(sourceOptions, layerOptions),
                layer = null;
        if (layerH && layerH.layer) {
            layer = layerH.layer;
        }
        if (source && source.length === 1) {
            if (layer.options.metadata) {
                // TODO check the layer configuration -> layer.options.metadata.url ???
                var link = $('<a href="' + layer.options.metadata.url + '" target="_BLANK"></a>');
                link.appendTo($('body'));
                link.click();
                link.remove();
            } else {
                var metadata_popup = null;
                metadata_popup = new Mapbender.Popup2({
                    title: Mapbender.trans("mb.core.metadata.popup.title"),
                    modal: false,
                    resizable: true,
                    draggable: true,
                    closeButton: true,
                    closeOnESC: false,
                    cssClass: 'metadataDialog',
                    content: [
                        $.ajax({
                            type: "POST",
                            url: Mapbender.configuration.application.urls['metadata'],
                            data: {
                                sourceId: source[0].origId,
                                layerName: layer.options.name && layer.options.name !== '' ? layer.options.name : ''
                            },
                            dataType: 'html',
                            complete: function(data) {
                                if (initTabContainer) {
                                    initTabContainer(metadata_popup.$element);
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Mapbender.error(errorThrown);
                            }
                        })
                    ],
                    destroyOnClose: true,
                    width: 850,
                    height: 600,
                    buttons: {
                        'ok': {
                            label: Mapbender.trans('mb.core.metadata.popup.btn.ok'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function() {
                                this.close();
                            }
                        }
                    }
                });
            }
        }
    }
};
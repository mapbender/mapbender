/**
 * 
 * Mapbender metadata connector to call metadata
 */
var Mapbender = Mapbender || {};
Mapbender.Metadata = Mapbender.Metadata || {};
Mapbender.Metadata.call = function(mapElementId, sourceOptions, layerOptions) {
    var map = $("#" + mapElementId).data("mapbenderMbMap");
    if (map) {
        var source = map.getModel().getSource(sourceOptions);
        var layerH = source && map.getModel().getSourceLayerById(source, layerOptions.id);
        if (source && layerH && layerH.layer) {
            $.ajax({
                type: "GET",
                url: Mapbender.configuration.application.urls['metadata'],
                data: {
                    sourceId: source.origId,
                    layerId: layerH.layer.options.origId || null
                },
                dataType: 'html',
                error: function(jqXHR, textStatus, errorThrown) {
                    Mapbender.error(errorThrown);
                }
            }).then(function(html) {
                var nodes = $(html);
                var metadataPopup = new Mapbender.Popup2({
                    title: Mapbender.trans("mb.core.metadata.popup.title"),
                    modal: false,
                    resizable: true,
                    draggable: true,
                    closeOnESC: false,
                    cssClass: 'metadataDialog',
                    content: nodes,
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
                if (initTabContainer) {
                    initTabContainer(metadataPopup.$element);
                }
            });
        }
    }
};
Mapbender.Model = {
    map : null,
    mapElement: null,
    initMap: function(mbMap) {



    this.map =  new ol.CanvasMap({
            view: new ol.View({
                center: [0, 0],
                zoom: 1
            }),
            layers: [
                new ol.layer.Tile({
                    source: new ol.source.OSM()
                })
            ],
            target: 'Map'
        });

    return true;
    }
}
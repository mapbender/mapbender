document.addEventListener('DOMContentLoaded', function() {
    if (document.readyState === 'interactive' || document.readyState === 'complete' ) {
        var featureIdFromElement = function(element) {
            return [sourceId, element.getAttribute('id')].join('-');
        }

        var pmOrigin = '*';
        var nodes = document.querySelectorAll('[data-geometry]') || [];
        var ewkts = Array.from(nodes).map(function (node) {
            return {
                srid: node.getAttribute('data-srid'),
                wkt: node.getAttribute('data-geometry'),
                id: featureIdFromElement(node)
            };
        });
        Array.from(nodes).forEach(function (node) {
            node.addEventListener('mouseover', function (event) {
                var id = featureIdFromElement(node);
                window.parent.postMessage({command: 'hover', state: true, id: id}, pmOrigin);
            });
            node.addEventListener('mouseout', function (event) {
                var id = featureIdFromElement(node);
                window.parent.postMessage({command: 'hover', state: false, id: id}, pmOrigin);
            });
        });
        window.parent.postMessage({ewkts: ewkts}, pmOrigin);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Only parse document features once.
    // Unguarded, this listener would run again when the iframe is removed from the DOM, readding
    // features we want to remove when deactivating the FeatureInfo element.
    var parsed = false;
    return function() {
        if (parsed || (document.readyState !== 'interactive' && document.readyState !== 'complete')) {
            return;
        }
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
        parsed = true;
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
}());

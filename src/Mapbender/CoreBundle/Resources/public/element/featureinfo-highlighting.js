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
        var features = Array.from(nodes).map(function (node) {
            return {
                srid: node.getAttribute('data-srid'),
                wkt: node.getAttribute('data-geometry'),
                id: featureIdFromElement(node)
            };
        });
        parsed = true;
        var sendHoverCommand = function(node, state) {
            var id = featureIdFromElement(node);
            var messageData = {
                command: 'hover',
                state: state,
                id: id,
                elementId: elementId
            };
            window.parent.postMessage(messageData, pmOrigin);
        };
        Array.from(nodes).forEach(function (node) {
            node.addEventListener('mouseover', function (event) {
                sendHoverCommand(node, true);
            });
            node.addEventListener('mouseout', function (event) {
                sendHoverCommand(node, false);
            });
        });
        window.parent.postMessage({command: 'features', features: features, elementId: elementId, sourceId: sourceId}, pmOrigin);
    }
}());

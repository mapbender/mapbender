// Hack to prevent DOMException when loading jquery
var replaceState = window.history.replaceState;
window.history.replaceState = function(){ try { replaceState.apply(this,arguments); } catch(e) {} };
document.addEventListener('DOMContentLoaded', function() {
    if (document.readyState === 'interactive' || document.readyState === 'complete' ) {
        var featureIdFromElement = function(element) {
            return [sourceId, element.getAttribute('id')].join('-');
        }

        var origin = '*';
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
                window.parent.postMessage({command: 'hover', state: true, id: id}, origin);
            });
            node.addEventListener('mouseout', function (event) {
                var id = featureIdFromElement(node);
                window.parent.postMessage({command: 'hover', state: false, id: id}, origin);
            });
        });
        window.parent.postMessage({ewkts: ewkts}, origin);

        var mbActionLinks = document.querySelectorAll("[mb-action]");
        mbActionLinks.forEach(function(actionLink) {
            actionLink.addEventListener('click',  function(e) {
                var element= e.target;
                var actionValue = element.getAttribute('mb-action');
                var attributesMap = {};
                for (var i = 0; i < element.attributes.length; i++) {
                    var attrib = element.attributes[i];
                    attributesMap[attrib.name] = attrib.value;
                }
                e.preventDefault();
                window.parent.postMessage({
                    actionValue: actionValue,
                    element: {
                        attributes: attributesMap
                    }
                },origin);
                return false;
            });
        });

    }
});

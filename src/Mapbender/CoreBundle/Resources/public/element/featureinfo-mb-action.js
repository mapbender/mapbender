document.addEventListener('DOMContentLoaded', function() {
    var pmOrigin = '*';
    if (document.readyState === 'interactive' || document.readyState === 'complete' ) {
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
                }, pmOrigin);
                return false;
            });
        });
    }
});

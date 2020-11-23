document.addEventListener('DOMContentLoaded', function() {
    var pmOrigin = '*';
    if (document.readyState === 'interactive' || document.readyState === 'complete' ) {
        var mbActionLinks = document.querySelectorAll("[mb-action]");
        mbActionLinks.forEach(function(actionLink) {
            actionLink.addEventListener('click',  function(e) {
                e.preventDefault();
                var element= e.target;
                var attributesMap = {};
                for (var i = 0; i < element.attributes.length; i++) {
                    var attrib = element.attributes[i];
                    attributesMap[attrib.name] = attrib.value;
                }
                window.parent.postMessage({
                    command: 'mb-action',
                    action: element.getAttribute('mb-action'),
                    attributes: attributesMap
                }, pmOrigin);
                return false;
            });
        });
    }
});

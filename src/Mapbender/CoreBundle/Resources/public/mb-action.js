// Handler for "mb-action" clickable Elements, triggering "declarative" actions
// NOTE: the only supported shipping action is named "source.add.wms", implemented in WmsLoader

window.addEventListener("message", function(event) {
    var data = event.data;
    if (data && data.command === 'mb-action' && data.attributes && data.attributes['mb-action']) {
        // Can't pass / receive Element in postMessage. Reconstruct from attributes.
        var element = $(document.createElement('a')).attr(data.attributes);
        var action = data.attributes['mb-action'];
        if (Mapbender.declarative && typeof Mapbender.declarative[action] === 'function') {
            Mapbender.declarative[action](element);
        }
    }
});

!(function($) {
    $(document).on('click', '[mb-action]', function(event) {
        event.preventDefault();
        // Extract / freeze attributes (NamedNodeMap cannot be sent in a postMessage)
        var attributesMap = {};
        for (var i = 0; i < this.attributes.length; i++) {
            var attrib = this.attributes[i];
            attributesMap[attrib.name] = attrib.value;
        }
        window.postMessage({command: 'mb-action', attributes: attributesMap}, '*');
        return false;
    });
})(jQuery);

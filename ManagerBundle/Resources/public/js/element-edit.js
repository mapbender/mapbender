ElementAssetLoader = function(assets) {
    var head = $('head');
    var body = $('body');

    $.each(assets.css, function(idx, path) {
        $('<link/>', {
            href: path,
            rel: 'stylesheet'
        }).appendTo(head);
    });

    $.each(assets.js, function(idx, path) {
        $('<script></script>', {
            src: path
        }).appendTo(body);
    });
};

;!(function($) {
    var configUrl = window.location.pathname + '/config' + window.location.search;
    $.ajax({
        url: configUrl,
        contentType: 'json'
    }).done(function (data, textStatus, jqXHR) {
        Mapbender.configuration = data;
        $(Mapbender.setup);
    }).fail(function (jqXHR, textStatus, errorThrown) {
        Mapbender.info("Load application's configuration: " + errorThrown);
    });
})(jQuery);

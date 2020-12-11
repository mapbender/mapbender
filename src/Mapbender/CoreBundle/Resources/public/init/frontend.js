;!(function($) {
    var configUrl = window.applicationConfigUrl + window.location.search;
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

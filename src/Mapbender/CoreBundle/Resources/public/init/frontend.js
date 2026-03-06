;!(function ($) {
    var configUrl = (function (location) {
        var search = location.search;
        // Forward query params to config url, but exclude all params handled purely client-side
        var handledParams = Mapbender.MapModelBase.prototype.getHandledUrlParams.call(null);
        for (var i = 0; i < handledParams.length; ++i) {
            var paramName = handledParams[i];
            // Remove param; support scalar and array-style params
            // This must be done with a loop because adjacent array-style param RegExp matches technically overlap,
            // and cannot be stripped in a single .replace call, even with the 'g' flag
            var paramPattern = ['([?&])', paramName, '([%=][^&]*)?(&|$)'].join('');
            var paramRxp = new RegExp(paramPattern, 'g');
            var without = search;
            do {
                search = without;
                without = search.replace(paramRxp, '$1');
            } while (without !== search);
            search = without;
            search = search.replace(/[?&]+$/, '');
            if (!search) {
                break;
            }
        }
        return window.applicationConfigUrl + search;
    })(window.location);

    const loadConfig = (configUrl) => {
        $.ajax({
            url: configUrl,
            contentType: 'application/json'
        }).done(function (data, textStatus, jqXHR) {
            Mapbender.configuration = data;
            $(Mapbender.setup);
        }).fail(function (jqXHR, textStatus, errorThrown) {
            Mapbender.handleAjaxError(jqXHR, () => loadConfig(configUrl));
            Mapbender.info("Load application's configuration: " + errorThrown);
        });
    }
    loadConfig(configUrl);


    $(document).one('click', '.js-splashscreen', function (e) {
        $(e.target).closest('.js-splashscreen').remove();
    });

})(jQuery);

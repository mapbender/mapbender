((function($) {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Manager = Mapbender.Manager || {};
    window.Mapbender.Manager.confirmDelete = function($el, deleteUrl, strings, popupContent) {
        var defaultContent = $el && ($('<div/>').text($el.attr('title') + '?').html());
        var deleteUrl_ = deleteUrl || ($el && ($el.attr('data-url') || $el.attr('href')));
        var deferred = $.Deferred();
        var popupOptions = {
            title: Mapbender.trans(strings.title),
            subTitle: strings.subTitle && (' - ' + Mapbender.trans(strings.subTitle)),
            buttons: [
                {
                    label: Mapbender.trans(strings.confirm),
                    cssClass: 'btn btn-danger btn-sm -js-confirm'
                },
                {
                    label: Mapbender.trans(strings.cancel),
                    cssClass: 'btn btn-default btn-sm popupClose'
                }
            ]
        };
        var $modal = Mapbender.bootstrapModal(popupContent || defaultContent, popupOptions);
        $modal.on('click', '.-js-confirm', function() {
            if (deleteUrl_) {
                $.ajax({
                    url: deleteUrl_,
                    type: 'POST'
                }).then(function() {
                    deferred.resolve(arguments);
                    window.location.reload();
                }, function() {
                    $modal.modal('hide');
                    deferred.resolve(arguments);
                });
            } else {
                $modal.modal('hide');
                deferred.resolve();
            }
        });
        $modal.on('click', '.popupClose', function() {
            $modal.modal('hide');
            deferred.reject();
        });
        return deferred.promise();
    };
})(jQuery));

((function($) {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Manager = Mapbender.Manager || {};
    window.Mapbender.Manager.confirmDelete = function($el, deleteUrl, strings, popupContent) {
        var defaultContent = $el && ($('<div/>').text(($el.attr('data-title') || $el.attr('title')) + '?').html());
        var deleteUrl_ = deleteUrl || ($el && ($el.attr('data-url') || $el.attr('href')));
        var csrfToken = $el && $el.attr('data-token');
        var deferred = $.Deferred();
        var popupOptions = {
            title: Mapbender.trans(strings.title),
            subTitle: strings.subTitle && (' - ' + Mapbender.trans(strings.subTitle)),
            buttons: [
                {
                    label: Mapbender.trans(strings.confirm),
                    cssClass: 'btn btn-danger btn-sm -js-confirm',
                    dataTest: 'mb-submit'
                },
                {
                    label: Mapbender.trans(strings.cancel),
                    cssClass: 'btn btn-light btn-sm popupClose',
                    dataTest: 'mb-cancel'
                }
            ]
        };
        var $modal = Mapbender.bootstrapModal(popupContent || defaultContent, popupOptions);
        const $form = $modal.find('form');
        if ($form.length) {
            // This is actually a form that can be submitted.
            // Form submit, unlike jQuery / XMLHttpRequest-based Ajax, can follow redirects properly
            // This is useful for delete requests starting from an item view page, where on success, the
            // item is deleted, and the previous URL becomes a 404.
            $('button.-js-confirm', $modal)
                .removeClass('-js-confirm')
                .one('click', () => $form[0].submit())
            ;
        }
        $modal.on('click', '.-js-confirm', function() {
            if (deleteUrl_) {
                $.ajax({
                    url: deleteUrl_,
                    data: {token: csrfToken},
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
        $modal.modal('show');
        return deferred.promise();
    };
})(jQuery));

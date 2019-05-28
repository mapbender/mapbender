((function($) {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Manager = Mapbender.Manager || {};
    window.Mapbender.Manager.confirmDelete = function($el, deleteUrl, strings, popupContent) {
        var defaultContent = $el && ($('<div/>').text($el.attr('title') + '?').html());
        var deleteUrl_ = deleteUrl || ($el && ($el.attr('data-url') || $el.attr('href')));
        if (false && !deleteUrl_) {
            console.error("Could not url of final delete action", $el);
            throw new Error("Could not url of final delete action");
        }
        var deferred = $.Deferred();
        var popupOptions = {
            title: Mapbender.trans(strings.title),
            subTitle: strings.subTitle && (' - ' + Mapbender.trans(strings.subTitle)),
            modal: true,
            destroyOnClose: true,
            content: popupContent || defaultContent,
            buttons: [
                {
                    label: Mapbender.trans(strings.confirm),
                    cssClass: 'button',
                    callback: function() {
                        if (deleteUrl_) {
                            var popup = this;
                            $.ajax({
                                url: deleteUrl_,
                                type: 'POST'
                            }).then(function() {
                                deferred.resolve(arguments);
                                window.location.reload();
                            }, function() {
                                popup.close();
                                deferred.resolve(arguments);
                            });
                        } else {
                            this.close();
                            deferred.resolve();
                        }
                    }
                },
                {
                    label: Mapbender.trans(strings.cancel),
                    cssClass: 'button buttonCancel critical',
                    callback: function() {
                        this.close();
                        deferred.reject();
                    }
                }
            ]
        };
        (new Mapbender.Popup(popupOptions));
        return deferred.promise();
    };
})(jQuery));
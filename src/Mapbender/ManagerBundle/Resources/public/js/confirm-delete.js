((function($) {
    window.Mapbender = Mapbender || {};
    window.Mapbender.Manager = Mapbender.Manager || {};
    window.Mapbender.Manager.confirmDelete = function($el, deleteUrl, strings, popupContent) {
        var defaultContent = $('<div/>').text($el.attr('title') + '?').html();
        var deleteUrl_ = deleteUrl || $el.attr('data-url') || $el.attr('href');
        if (!deleteUrl_) {
            console.error("Could not url of final delete action", $el);
            throw new Error("Could not url of final delete action");
        }
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
                        $.ajax({
                            url: deleteUrl_,
                            type: 'POST',
                            success: function() {
                                window.location.reload();
                            }
                        });
                    }
                },
                {
                    label: Mapbender.trans(strings.cancel),
                    cssClass: 'button buttonCancel critical',
                    callback: function() {
                        this.close();
                    }
                }
            ]
        };
        return new Mapbender.Popup(popupOptions);
    };
})(jQuery));
(function ($) {
    "use strict";
    /**
     * Example:
     *     Mapbender.confirmDialog({html: "Feature löschen?", title: "Bitte bestätigen!", onSuccess:function(){
                  return false;
           }});
     * @param options
     * @returns {*}
     */
    Mapbender.confirmDialog = function (options) {
        var dialog = $("<div class='confirm-dialog'>" + (options.html || "") + "</div>").popupDialog({
            title: options.hasOwnProperty('title') ? options.title : "",
            maximizable: false,
            dblclick: false,
            minimizable: false,
            resizable: false,
            collapsable: false,
            modal: true,
            buttons: options.buttons || [{
                text: options.okText || "OK",
                click: function (e) {
                    if (!options.hasOwnProperty('onSuccess') || options.onSuccess(e) !== false) {
                        dialog.popupDialog('close');
                    }
                    return false;
                }
            }, {
                text: options.cancelText || "Abbrechen",
                'class': 'critical',
                click: function (e) {
                    if (!options.hasOwnProperty('onCancel') || options.onCancel(e) !== false) {
                        dialog.popupDialog('close');
                    }
                    return false;
                }
            }]
        });
        return dialog;
    };


})(jQuery);

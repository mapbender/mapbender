function SymfonyAjaxManager(ajaxUrl) {

    /**
     * Query
     *
     * @param uri
     * @param request
     * @returns xhr
     */
    this.query = function(uri, request) {
        return $.ajax({
            url:         ajaxUrl + '/' + uri,
            type:        'POST',
            contentType: "application/json; charset=utf-8",
            dataType:    "json",
            data:        JSON.stringify(request)
        }).error(function(xhr) {
            var errorMessage = "";
            var errorDom = $(xhr.responseText);

            if(errorDom.size()) {
                if(errorDom.is(".sf-reset")) {
                    errorMessage += "\n" + errorDom.find(".block_exception h2").text() + "\n";
                    errorMessage += "Trace:\n";
                    _.each(errorDom.find(".traces li"), function(li) {
                        errorMessage += $(li).text() + "\n";
                    });

                } else if(errorDom.is("title")) {
                    errorMessage = errorDom.closest("title").text();
                }

            } else {
                errorMessage += JSON.stringify(xhr.responseText);
            }

            $.notify(errorMessage, {
                autoHide: false
            });
            console.log(errorMessage, xhr);
        });
    }
}

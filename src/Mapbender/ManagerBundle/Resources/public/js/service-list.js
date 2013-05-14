$(function() {
    // Delete element
    $('.iconRemove').bind("click", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - service",
                    content:"Delete " + title + "?"
                },
                function(){
                    $.ajax({
                        url: me.attr('data-url'),
                        data : {'id': me.attr('data-id')},
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });
});
$(function() {
    // init tabcontainers --------------------------------------------------------------------
    $(".tabContainer, .tabContainerAlt").on('click', '.tab', function() {
        var me = $(this);
        me.parent().parent().find(".active").removeClass("active");
        me.addClass("active");
        $("#" + me.attr("id").replace("tab", "container")).addClass("active");
    });
});

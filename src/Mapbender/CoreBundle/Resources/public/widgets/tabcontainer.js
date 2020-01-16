/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/frontend
 */
var initTabContainer = function ($context) {

    $(".tabContainer, .tabContainerAlt", $context).on('click', '.tab', function () {
        var me = $(this);
        var $cnt = $(this).parent().parent();
        $('>.tabs >.tab, >.container', $cnt).removeClass('active');
        $('>.container#' + me.attr('id').replace("tab", "container"), $cnt).addClass('active');
        me.addClass("active");
    });

    $(".accordionContainer", $context).on('click', '.accordion', function() {
        var me = $(this);
        var $group = $(this).closest('.accordionContainer');
        if (me.hasClass('active')) {
            return;
        }
        var previous = $('> .container-accordion.active', $group);
        // remove .active from both accordion headers and accordion content containers
        $('> .active', $group).not(me).removeClass('active');
        me.addClass('active');
        $("#" + me.attr("id").replace("accordion", "container"), $group).addClass("active");

        me.trigger('selected', {
            current:    me,
            currentTab: $group,
            previous: previous
        });
    });
};

$(function () {
    // init tabcontainers --------------------------------------------------------------------
    initTabContainer($('body'));
});

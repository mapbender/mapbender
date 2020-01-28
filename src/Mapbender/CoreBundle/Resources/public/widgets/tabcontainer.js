/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/frontend
 */
var initTabContainer = function ($context) {
    $('.tabContainer, .tabContainerAlt', $context)
        .filter(function() {
            return (typeof ($(this).data('tabcontainer-initialized')) === 'undefined');
        })
        .on('click', '.tab', function () {
            var $tabHeader = $(this);
            var $cnt = $(this).parent().parent();
            $('>.tabs >.tab, >.container', $cnt).removeClass('active');
            $('>.container#' + $tabHeader.attr('id').replace("tab", "container"), $cnt).addClass('active');
            $tabHeader.addClass("active");
        })
        .each(function() {
            $(this).data('tabcontainer-initialized', true)
        })
    ;
    $(".accordionContainer", $context)
        .filter(function() {
            return (typeof ($(this).data('accordion-initialized')) === 'undefined');
        })
        .on('click', '.accordion', function() {
            var $header = $(this);
            if ($header.hasClass('active')) {
                return;
            }
            var $group = $(this).closest('.accordionContainer');
            var previous = $('> .container-accordion.active', $group);
            // remove .active from both accordion headers and accordion content containers
            $('> .active', $group).not($header).removeClass('active');
            $header.addClass('active');
            $("#" + $header.attr("id").replace("accordion", "container"), $group).addClass("active");

            $header.trigger('selected', {
                // @todo: this event data is completely confusing
                //   'current' is the now active header
                //   'previous' is the previously active content pane (NOT the header)
                //   'currentTab' is the entire parent accordion container (neither header nor content pane)
                // figure out which event consumer uses what, and if it's safe to fix the
                // inconsistencies
                current:    $header,
                currentTab: $group,
                previous: previous
            });
        })
        .each(function() {
            $(this).data('accordion-initialized', true);
        })
    ;
};

$(function () {
    // init tabcontainers --------------------------------------------------------------------
    initTabContainer($('body'));
});

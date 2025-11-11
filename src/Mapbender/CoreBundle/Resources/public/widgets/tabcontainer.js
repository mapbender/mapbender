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
            $tabHeader.trigger('mb.shown.tab');
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
    $(".listContainer", $context)
        .filter(function() {
            return (typeof ($(this).data('list-initialized')) === 'undefined');
        })
        .on('click', '.list-group-item', function() {
            var $header = $(this);
            if ($header.hasClass('active')) {
                return;
            }
            var $group = $(this).closest('.listContainer');
            var $sideContent = $group.closest('.sideContent');
            var $previous = $('.list-group .list-group-item.active', $group);

            $previous.removeClass('active');

            $header.addClass('active');

            // Get the container ID by replacing list_group_item with list_group_item_container
            var containerId = $header.attr("id").replace("list_group_item", "list_group_item_container");
            var $container = $("#" + containerId, $sideContent);

            // Hide all containers first
            $('.container-list-group-item', $sideContent).removeClass('active');

            $container.addClass('active');

            // Slide the listContainer to the left and container to the right
            $group.addClass('list-shifted');
        })
        .each(function() {
            $(this).data('list-initialized', true);
        })
    ;

    // Handle back button clicks
    $context.on('click', '.list-back-btn', function(e) {
        var $sideContent = $(this).closest('.sideContent');
        var $group = $('.listContainer', $sideContent);
        var $activeContainer = $('.container-list-group-item.active', $sideContent);

        $('.list-group-item.active', $group).removeClass('active');
        $activeContainer.removeClass('active');

        // Slide back to original position
        $group.removeClass('list-shifted');

        // Trigger event to notify elements to deactivate
        $sideContent.trigger('listgroup:back', [$activeContainer]);

        return false;
    })
    ;
};

$(function () {
    // init tabcontainers --------------------------------------------------------------------
    initTabContainer($('body'));
});

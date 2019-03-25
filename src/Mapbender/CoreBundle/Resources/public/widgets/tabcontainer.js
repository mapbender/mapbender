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

    var accordion = $.extend($(".accordionContainer", $context), {
        hasOutsideScroll: function() {
            return this.parent().height() < this.height();
        },
        updateHeight:       function() {
            var contentCells = $("> .container-accordion > .accordion-cell", this);
            var tabCells = $("> .accordion", this);
            var tabCellsHeight = this.parent().height();

            $.each(tabCells, function(idx, el) {
                var tabCell = $(el);
                tabCellsHeight -= tabCell.height();
            });
            contentCells.height(tabCellsHeight);
        }
    });

    // IE Scroll BugFix
    if(accordion.hasOutsideScroll()){
        accordion.updateHeight();
        $(window).on('resize', $.proxy(accordion.updateHeight, accordion));
    }

    accordion.on('click', '.accordion', function(event) {
        var me = $(this);
        var tab = $(event.delegateTarget);
        var isActive = me.hasClass('active');

        if(isActive) {
            return;
        }

        var previous = tab.find("> .active");
        previous.removeClass("active");

        if(me.hasClass('accordion')) {
            if(isActive) {
                me.removeClass('active');
            } else {
                me.addClass('active');
                $("#" + me.attr("id").replace("accordion", "container"), tab).addClass("active");
            }
        } else {
            me.addClass("active");
            $("#" + me.attr("id").replace("tab", "container"), tab).addClass("active");
        }

        me.trigger('selected', {
            current:    me,
            currentTab: tab,
            previous:   previous
        });
    });

    accordion.bind('select', function(e, title) {
        return $(e.currentTarget).find('.accordion > .tablecell:contains("' + title + '")').trigger('click');
    });

    accordion.data('ready',true);
    accordion.trigger('ready');
};

$(function () {
    // init tabcontainers --------------------------------------------------------------------
    initTabContainer($('body'));
});

/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initDropdown = function () {
    var me = $(this);
    var dropdownList = me.find(".dropdownList");
    if (dropdownList.children().length === 0) {
        me.find("option").each(function (i, e) {
            $(e).addClass("opt-" + i);
            var node = $('<li class="item-' + i + '"></li>');
            dropdownList.append(node);
            node.text($(e).text());
        });
    }
    var select = me.find("select").val();
    me.find(".dropdownValue").text(me.find('option[value="'+select+'"]').text())
};
$(function () {
    // init dropdown list --------------------------------------------------------------------

    var toggleList = function () {
        var me = $(this);
        var list = me.find(".dropdownList");
        var opts = me.find(".hiddenDropdown");
        if (list.css("display") == "block") {
            list.hide();
        } else {
            $(".dropdownList").hide();
            list.show();
            list.find("li").one("click", function (event) {
                event.stopPropagation();
                list.hide().find("li").off("click");
                var me2 = $(this);
                var opt = me2.attr("class").replace("item", "opt");
                me.find(".dropdownValue").text(me2.text());
                var val = opts.find("." + opt).prop("selected", true).val();
                opts.val(val).trigger('change');
            })
        }

        $(document).one("click", function () {
            list.hide().find("li").off("mouseout").off("click");
        });
        return false;
    }
    $('.dropdown').each(function () {
        initDropdown.call(this);
    });
    $(document).on("click", ".dropdown", toggleList);
    $(document).on('mousewheel scroll DOMMouseScroll', '.dropdownList', function(e) {
        var delta = e.originalEvent.detail;
        var height = this.scrollTopMax;
        var atTop = this.scrollTop === 0;
        var atBottom = this.scrollTop === this.scrollTopMax;

        if (!this.scrollTopMax) {
            atBottom = this.scrollHeight === this.clientHeight + this.scrollTop;
        }
        if (e.originalEvent.deltaY) {
            delta = e.originalEvent.deltaY;
        }
        if (e.originalEvent.wheelDelta) {
            delta = -e.originalEvent.wheelDelta;
        }
        if (atTop && delta < 0 || atBottom && delta > 0) {
            return false;
        }
        return undefined;
    });

});

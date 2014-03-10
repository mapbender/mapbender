var initDropdown = function(){
    var me = $(this);
    var dropdownList = me.find(".dropdownList");
    var childCount = dropdownList.children().length;

    if(childCount == 0){
        var newElement, select;

        me.find("option").each(function(i, e){
            $(e).addClass("opt-" + i);
            newElement = $('<li class="item-' + i + '">' + $(e).text() + '</li>');
            dropdownList.append(newElement);
        });
    }

    select = (me.find("option[selected=selected]").length > 0) ? "option[selected=selected]"
        : "option:first";

    me.find(".dropdownValue").text(me.find(select).text())

    if(me.css("minWidth").replace("px", "") == "0"){
        width = dropdownList.width();
        me.width(width + 60); // space * 3
    }
};
$(function(){
    // init dropdown list --------------------------------------------------------------------

    var toggleList = function(){
        var me = $(this);
        var list = me.find(".dropdownList");
        var opts = me.find(".hiddenDropdown");
        if(list.css("display") == "block"){
            list.hide();
        }else{
            $(".dropdownList").hide();
            list.show();
            list.find("li").one("click", function(event){
                event.stopPropagation();
                list.hide().find("li").off("click");
                var me2 = $(this);
                var opt = me2.attr("class").replace("item", "opt");
                me.find(".dropdownValue").text(me2.text());
                opts.find("[selected=selected]").removeAttr("selected");
                var val = opts.find("." + opt).attr("selected", "selected").val();
                opts.val(val).trigger('change');
            })
        }

        $(document).one("click", function(){
            list.hide().find("li").off("mouseout").off("click");
        });
        return false;
    }
    $('.dropdown').each(function(){
        initDropdown.call(this);
    });
    $(document).on("click", ".dropdown", toggleList);
});

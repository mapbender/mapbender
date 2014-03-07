var initCheckbox = function(){
    var me = $(this);
    var parent = me.parent(".checkWrapper");

    if(me.is(":checked")){
        parent.addClass("iconCheckboxActive");
    }else{
        parent.removeClass("iconCheckboxActive");
    }

    if(me.is(":disabled")){
        parent.addClass("checkboxDisabled");
    }else{
        parent.removeClass("checkboxDisabled");
    }
};
$(function(){
    var toggleCheckBox = function(){
        var me = $(this);
        var checkbox = me.find(".checkbox");

        if(checkbox.is(":disabled")){
            me.addClass("checkboxDisabled");
        }else{
            if(checkbox.is(":checked")){
                me.removeClass("iconCheckboxActive");
                checkbox.get(0).checked = false;
            }else{
                me.addClass("iconCheckboxActive");
                checkbox.get(0).checked = true;
            }
        }

        checkbox.trigger('change');
    };
    $('.checkbox').each(function(){
        initCheckbox.call(this);
    });
    $(document).on("click", ".checkWrapper", toggleCheckBox);
});
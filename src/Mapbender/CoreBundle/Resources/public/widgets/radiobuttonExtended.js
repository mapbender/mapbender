/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initRadioButton = function(nullable, newIcon) {
    var me = $(this);
    var parent = me.parent(".radioWrapper");
    if(newIcon){
        var def = parent.attr('data-icon') ? parent.attr('data-icon') : "iconRadio";
        parent.removeClass(def).removeClass(def + "Active").attr("data-icon", newIcon).addClass(newIcon);
    }
    if (me.is(":checked")) {
        parent.addClass(parent.attr('data-icon') + "Active");
    } else {
        parent.removeClass(parent.attr('data-icon') + "Active");
    }

    if (me.is(":disabled")) {
        parent.addClass("radioboxDisabled");
    } else {
        parent.removeClass("radioboxDisabled");
    }
    if(!parent.attr("title") && parent.parent().find('label[for="'+me.attr('id')+'"]').text()){
        parent.attr("title", parent.parent().find('label[for="'+me.attr('id')+'"]').text());
    }
};
$(function() {
    var toggleRadioBox = function() {
        var me = $(this);
        var radiobox = me.find(".radiobox");
        $('input[type="radio"][name="' + radiobox.attr('name') + '"]').each(function() {
            var rdb = $(this);
            var rbgwrp = rdb.closest('.radioWrapper');
            if (rdb.is(":disabled")) {
                rbgwrp.addClass("radioboxDisabled");
            } else {
                var checked = rdb.attr('id') === radiobox.attr('id');
                rbgwrp.toggleClass(rbgwrp.attr('data-icon') + "Active", checked);
                rdb.prop('checked', checked);
            }
        });
        radiobox.trigger('change');
    };
    $('.radiobox').each(function() {
        initRadioButton.call(this);
    });
    $(document).on("click", ".radioWrapper", toggleRadioBox);
});

/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initRadioButton = function(nullable, newIcon) {
    var me = $(this);
    var parent = me.parent(".radioWrapper");
    if (nullable) {
        parent.attr('data-nullable', true);
    }
    if(newIcon){
        var def = parent.attr('data-icon') ? parent.attr('data-icon') : "iconRadio";
        parent.removeClass(def).removeClass(def + "Activ").attr("data-icon", newIcon).addClass(newIcon);
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
            var rbgwrp = rdb.parents('.radioWrapper:first');
            var nullable = rbgwrp.attr("data-nullable");
            if (rdb.is(":disabled")) {
                rbgwrp.addClass("radioboxDisabled");
            } else {
                if (rdb.attr('id') === radiobox.attr('id')) {
                    if (nullable) {
                        if (rdb.is(":checked")) {
                            rbgwrp.removeClass(rbgwrp.attr('data-icon') + "Active");
                            rdb.get(0).checked = false;
                        } else {
                            rbgwrp.addClass(rbgwrp.attr('data-icon') + "Active");
                            rdb.get(0).checked = true;
                        }
                    } else {
                        rbgwrp.addClass(rbgwrp.attr('data-icon') + "Active");
                        rdb.get(0).checked = true;
                    }
                } else {
                    rbgwrp.removeClass(rbgwrp.attr('data-icon') + "Active");
                    rdb.get(0).checked = false;
                }
            }
        });
        radiobox.trigger('change');
    };
    $('.radiobox').each(function() {
        initRadioButton.call(this);
    });
    $(document).on("click", ".radioWrapper", toggleRadioBox);
});

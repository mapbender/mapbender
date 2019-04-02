/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initCheckbox = function(){
    var $checkbox = $(this);
    var $wrapper = $checkbox.parent(".checkWrapper");
    var iconOnDefault = "iconCheckboxActive";
    var iconOffDefault = "iconCheckbox";
    var iconOnAttrib = $wrapper.attr('data-icon-on');
    var iconOffAttrib = $wrapper.attr('data-icon-off');
    var iconOn = (typeof iconOnAttrib !== 'undefined') ? iconOnAttrib : iconOnDefault;
    var iconOff = (typeof iconOffAttrib !== 'undefined') ? iconOffAttrib : iconOffDefault;

    if ($checkbox.prop("checked")) {
        $wrapper.addClass(iconOn);
        $wrapper.removeClass([iconOff, iconOffDefault].join(' '));
    } else {
        $wrapper.addClass(iconOff);
        $wrapper.removeClass([iconOn, iconOnDefault].join(' '));
    }
    $wrapper.toggleClass("checkboxDisabled", $checkbox.prop("disabled"));
};
$(function(){
    var toggleCheckBox = function(){
        var me = $(this);
        var checkbox = me.find(".checkbox");
        if (!checkbox.prop('disabled')) {
            checkbox.prop('checked', !checkbox.prop('checked'));
            initCheckbox.call(checkbox);
            checkbox.trigger('change');
        } else {
            initCheckbox.call(checkbox);
        }
    };
    $('.checkWrapper > .checkbox').each(function(){
        initCheckbox.call(this);
    });
    $(document).on("click", ".checkWrapper", toggleCheckBox);
});
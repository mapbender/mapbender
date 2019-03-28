/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initCheckbox = function(){
    var $checkbox = $(this);
    var $wrapper = $checkbox.parent(".checkWrapper");
    var iconOnDefault = "iconCheckboxActive", iconOn = $wrapper.attr('data-icon-on') || iconOnDefault;
    var iconOffDefault = "iconCheckbox", iconOff = $wrapper.attr('data-icon-off') || iconOffDefault;

    if ($checkbox.prop("checked")) {
        $wrapper.addClass(iconOn || iconOnDefault);
        $wrapper.removeClass([iconOff, iconOffDefault].join(' '));
    } else {
        $wrapper.addClass(iconOff || iconOffDefault);
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
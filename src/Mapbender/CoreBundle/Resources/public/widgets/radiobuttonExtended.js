/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initRadioButton = function() {
    var me = $(this);
    var wrapper = me.parent(".radioWrapper");
    wrapper.toggleClass(wrapper.attr('data-icon') + "Active", me.prop('checked'));
    wrapper.toggleClass("radioboxDisabled", me.prop('disabled'));
};
$(function() {
    var toggleRadioBox = function() {
        var $clickedRadio = $(".radiobox", this);
        $clickedRadio.prop('checked', true);
        $('input[type="radio"][name="' + $clickedRadio.attr('name') + '"]').each(initRadioButton);
    };
    $('.radiobox').each(function() {
        initRadioButton.call(this);
    });
    $(document).on("click", ".radioWrapper", toggleRadioBox);
});

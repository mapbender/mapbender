/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
var initCheckbox = function() {
    $(this).mbCheckbox({listen: false});
};
$(function(){
    var toggleCheckBox = function(){
        var checkbox = $('> input[type="checkbox"]', this);
        if (!checkbox.prop('disabled')) {
            checkbox.prop('checked', !checkbox.prop('checked'));
            initCheckbox.call(checkbox);
            checkbox.trigger('change');
        } else {
            initCheckbox.call(checkbox);
        }
    };
    $('.checkWrapper > input[type="checkbox"]').each(initCheckbox);
    $(document).on("click", ".checkWrapper", toggleCheckBox);
});

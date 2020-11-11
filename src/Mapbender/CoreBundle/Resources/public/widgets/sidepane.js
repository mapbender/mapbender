/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/frontend
 */
$(function() {

    var switchButton = $(".toggleSideBar");
    var sidePane = switchButton.closest("div.sidePane");

    if (sidePane.hasClass('closed')) {
        if (sidePane.hasClass("right")) {
            sidePane.css({right: (sidePane.outerWidth(true) * -1) + "px"});
        } else {
            sidePane.css({left: (sidePane.outerWidth(true) * -1) + "px"});
        }
    }

    switchButton.on('click', function() {
        var wasOpen = !sidePane.hasClass('closed');
        var animation = {};
        var align = sidePane.hasClass('right') ? 'right' : 'left';
        if (wasOpen) {
            animation[align] = "-" + sidePane.outerWidth(true) + "px";
        } else {
            animation[align] = "0px";
        }

        sidePane.animate(animation, {
            duration: 300,
            complete: function() {
                sidePane.toggleClass('closed', wasOpen);
            }
        });
    });
});

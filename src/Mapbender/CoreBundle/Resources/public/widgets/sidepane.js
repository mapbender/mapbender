/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/frontend
 */
$(function() {

    var switchButton = $(".toggleSideBar");
    var sidePane = switchButton.closest("div.sidePane");
    var speed = 300;
    var animation = {};

    sidePane.css({
        '-webkit-transition': 'none !important',
        '-moz-transition':    'none !important',
        '-o-transition':      'none !important',
        '-ms-transition':     'none !important',
        'transition':         'none !important'
    });

    // closing bugfix
    sidePane.width(sidePane.width());

    if (sidePane.hasClass('closed')) {
        sidePane.css({
            'transition': 'none'
        });

        if (sidePane.hasClass("right")) {
            sidePane.css({right: (sidePane.outerWidth(true) * -1) + "px"});
        } else {
            sidePane.css({left: (sidePane.outerWidth(true) * -1) + "px"});
        }
    }
    sidePane.width(sidePane.width());
    sidePane.show(0);

    switchButton.on('click', function() {
        var wasOpen = !sidePane.hasClass('closed');
        var align = sidePane.hasClass('right') ? 'right' : 'left';
        var onProgress = function(now, fx) {
            sidePane.trigger("animate");
        };
        if (wasOpen) {
            animation[align] = "-" + sidePane.outerWidth(true) + "px"; //, "swing"];
        } else {
            animation[align] = "0px";
        }

        sidePane.animate(animation, {
            duration: speed,
            progress: onProgress,
            complete: function() {
                sidePane.toggleClass('closed', wasOpen);
                sidePane.trigger("animate");
                sidePane.trigger("switchSidepane");
            }
        });
    });
});

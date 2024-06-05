$(function () {
    const $switchButton = $(".toggleSideBar");
    const $sidePane = $switchButton.closest("div.sidePane");
    const sidePane = $sidePane[0];
    const sidePaneLeft = $sidePane.hasClass('left');

    let pointerPosition = 0;
    const BORDER_SIZE = 'ontouchstart' in document ? 12 : 6;
    // if you want to customize the max/min size use custom css (min-width/max-width on .sidePane.resizable),
    const MAX_SIZE_WINDOW_PERCENTAGE = 0.95;
    const MIN_SIZE_PX = 120;

    // TOGGLE SIDEPANE FUNCTIONALITY
    if ($sidePane.hasClass('closed')) {
        if ($sidePane.hasClass("right")) {
            $sidePane.css({right: ($sidePane.outerWidth(true) * -1) + "px"});
        } else {
            $sidePane.css({left: ($sidePane.outerWidth(true) * -1) + "px"});
        }
    }

    $switchButton.on('click', function () {
        var wasOpen = !$sidePane.hasClass('closed');
        var animation = {};
        var align = $sidePane.hasClass('right') ? 'right' : 'left';
        if (wasOpen) {
            animation[align] = "-" + $sidePane.outerWidth(true) + "px";
        } else {
            animation[align] = "0px";
        }

        $sidePane.addClass('animating');
        $sidePane.animate(animation, {
            duration: 300,
            complete: function () {
                $sidePane.removeClass('animating').toggleClass('closed', wasOpen);
            }
        });
    });

    // RESIZE SIDEPANE FUNCTIONALITY
    const sidePaneWidth = function () {
        return parseInt(getComputedStyle(sidePane, '').width);
    }

    const resize = function (e) {
        if (e.buttons === 0) {
            // catch pointer released outside the window
            document.removeEventListener("pointermove", resize, false);
            return;
        }

        // some touch devices do not expose e.x in pointerdown, so use the first pointermove event as reference
        if (pointerPosition === undefined) {
            pointerPosition = e.x;
            return;
        }

        const dx = pointerPosition - e.x;
        pointerPosition = e.x;
        let calculatedWidth = sidePaneWidth() + (sidePaneLeft ? -1 : 1) * dx;

        // make sure sidepane does not become unreasonably big or small
        if (calculatedWidth > Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE)) {
            const overflow = calculatedWidth - Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE);
            calculatedWidth -= overflow;
            pointerPosition -= overflow;
        }

        if (calculatedWidth < MIN_SIZE_PX) {
            const underflow = MIN_SIZE_PX - calculatedWidth;
            calculatedWidth += underflow;
            pointerPosition += underflow;
        }

        sidePane.style.width = calculatedWidth + "px";
    }

    const constrainSize = function () {
        if (!sidePane) return;
        const allowedWidth = Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE);
        if (sidePaneWidth() > allowedWidth) {
            sidePane.style.width = allowedWidth + "px";
            if (sidePane.style.left) {
                sidePane.style.left = "-" + allowedWidth + "px";
            }
        }
    }

    // make sure sidebar is resizable even when making the window smaller
    window.addEventListener("resize", constrainSize, false);

    $(document).on('pointerdown', '.sidePane.resizable', function (e) {
        if ((sidePaneLeft && sidePaneWidth() - e.offsetX < BORDER_SIZE) || (!sidePaneLeft && e.offsetX < BORDER_SIZE)) {
            pointerPosition = e.x;
            document.addEventListener("pointermove", resize);
        }

        $(document).one('pointercancel pointerup', function (e) {
            document.removeEventListener("pointermove", resize);
        });
    });

});

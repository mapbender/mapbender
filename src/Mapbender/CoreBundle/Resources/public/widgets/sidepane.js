$(function () {
    const $switchButton = $(".toggleSideBar");
    const $sidePane = $switchButton.closest("div.sidePane");
    const sidePane = $sidePane[0];

    let mousePosition = 0;
    const BORDER_SIZE = 6;
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

        $sidePane.animate(animation, {
            duration: 300,
            complete: function () {
                $sidePane.toggleClass('closed', wasOpen);
            }
        });
    });

    // RESIZE SIDEPANE FUNCTIONALITY
    const sidePaneWidth = function () {
        return parseInt(getComputedStyle(sidePane, '').width);
    }
    const resize = function (e) {
        if (e.buttons === 0) {
            // catch mouse released outside the window
            document.removeEventListener("mousemove", resize, false);
            return;
        }

        const dx = mousePosition - e.x;
        mousePosition = e.x;
        let calculatedWidth = sidePaneWidth() - dx;

        // make sure sidepane does not become unreasonably big or small
        if (calculatedWidth > Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE)) {
            const overflow = calculatedWidth - Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE);
            calculatedWidth -= overflow;
            mousePosition -= overflow;
        }

        if (calculatedWidth < MIN_SIZE_PX) {
            const underflow = MIN_SIZE_PX - calculatedWidth;
            calculatedWidth += underflow;
            mousePosition += underflow;
        }

        sidePane.style.width = calculatedWidth + "px";
    }

    const constrainSize = function () {
        if (!sidePane) return;
        if (sidePaneWidth() > Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE)) {
            sidePane.style.width = Math.floor(window.innerWidth * MAX_SIZE_WINDOW_PERCENTAGE) + "px";
        }
    }

    // make sure sidebar is resizable even when making the window smaller
    window.addEventListener("resize", constrainSize, false);

    $(document).on('mousedown', '.sidePane.resizable', function (e) {
        if (sidePaneWidth() - e.offsetX < BORDER_SIZE) {
            mousePosition = e.x;
            document.addEventListener("mousemove", resize, false);
        }
    });

    $(document).on('mouseup', '.sidePane.resizable', function (e) {
        document.removeEventListener("mousemove", resize, false);
    });
});

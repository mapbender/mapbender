$(function () {
    class SidePane {
        constructor(element) {
            this.$element = $(element);
            this.$switchButton = this.$element.find(".toggleSideBar");
            this.element = this.$element[0];
            this.isLeft = this.$element.hasClass('left');
            this.pointerPosition = 0;

            this.BORDER_SIZE = 'ontouchstart' in document ? 12 : 6;
            // if you want to customize the max/min size use custom css (min-width/max-width on .sidePane.resizable),
            this.MAX_SIZE_WINDOW_PERCENTAGE = 0.95;
            this.MIN_SIZE_PX = 120;


            this.setupInitialState();
            this.setupEvents();
        }

        toggle() {
            var wasOpen = !this.$element.hasClass('closed');
            var animation = {};
            var align = this.$element.hasClass('right') ? 'right' : 'left';
            if (wasOpen) {
                animation[align] = "-" + this.$element.outerWidth(true) + "px";
            } else {
                animation[align] = "0px";
            }

            this.$element.addClass('animating');
            this.$element.animate(animation, {
                duration: 300,
                complete: () => {
                    this.$element.removeClass('animating').toggleClass('closed', wasOpen);
                }
            });
        };

        setupEvents() {
            this.$switchButton.on('click', () => this.toggle());

            $(document).on('pointerdown', '.sidePane.resizable', (e) => {
                const paneRect = e.target.getBoundingClientRect();
                const offsetX = e.clientX - paneRect.left;

                if ((this.isLeft && this.sidePaneWidth() - offsetX < this.BORDER_SIZE) || (!this.isLeft && offsetX < this.BORDER_SIZE)) {
                    this.pointerPosition = e.x;
                    $("body").addClass("prevent-selection");
                    document.addEventListener("pointermove", this.resize.bind(this));
                }

                $(document).one('pointercancel pointerup', () => {
                    document.removeEventListener("pointermove", this.resize.bind(this));
                    $("body").removeClass("prevent-selection");
                });
            });

            // make sure sidebar is resizable even when making the window smaller
            window.addEventListener("resize", this.constrainSize, false);
        }

        setupInitialState() {
            // TOGGLE SIDEPANE FUNCTIONALITY
            if (this.$element.hasClass('closed')) {
                if (this.$element.hasClass("right")) {
                    this.$element.css({right: (this.$element.outerWidth(true) * -1) + "px"});
                } else {
                    this.$element.css({left: (this.$element.outerWidth(true) * -1) + "px"});
                }
            }
        }

        // RESIZE SIDEPANE FUNCTIONALITY
        sidePaneWidth() {
            return parseInt(getComputedStyle(this.element, '').width);
        }

        resize(e) {
            if (e.buttons === 0) {
                // catch pointer released outside the window
                document.removeEventListener("pointermove", this.resize.bind(this), false);
                return;
            }

            // some touch devices do not expose e.x in pointerdown, so use the first pointermove event as reference
            if (this.pointerPosition === undefined) {
                this.pointerPosition = e.x;
                return;
            }

            const dx = this.pointerPosition - e.x;
            this.pointerPosition = e.x;
            let calculatedWidth = this.sidePaneWidth() + (this.isLeft ? -1 : 1) * dx;

            // make sure sidepane does not become unreasonably big or small
            if (calculatedWidth > Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE)) {
                const overflow = calculatedWidth - Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE);
                calculatedWidth -= overflow;
                this.pointerPosition -= overflow;
            }

            if (calculatedWidth < this.MIN_SIZE_PX) {
                const underflow = this.MIN_SIZE_PX - calculatedWidth;
                calculatedWidth += underflow;
                this.pointerPosition += underflow;
            }

            this.element.style.width = calculatedWidth + "px";
        }

        constrainSize() {
            if (!this.element) return;
            const allowedWidth = Math.floor(window.innerWidth * this.MAX_SIZE_WINDOW_PERCENTAGE);
            if (this.sidePaneWidth() > allowedWidth) {
                this.element.style.width = allowedWidth + "px";
                if (this.element.style.left) {
                    this.element.style.left = "-" + allowedWidth + "px";
                }
            }
        }
    }

    window.Mapbender.sidePane = new SidePane($('.sidePane'));

});

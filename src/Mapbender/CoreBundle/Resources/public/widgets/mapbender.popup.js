;!(function() {
    "use strict";
    window.Mapbender = window.Mapbender || {};

    var currentModal_ = null;

    window.Mapbender.Popup = function Popup(options) {
        this.mobileBreakpoint = 599;     // If this value is changed, it must also be adjusted in _popup.scss
        this.mobileResizeMinHeight = 50;
        this.mobileResizeMaxHeight = window.innerHeight;
        this.mobileAutoSize = true;       // Auto-size popup based on screen size
        this.mobileMaxHeightRatio = 0.8;  // Maximum height as ratio of viewport (0.8 = 80%)
        this.options = Object.assign({}, this.defaults, options);
        delete this.options['__dummy__'];

        // Create DOM element
        this.$element = $(document.createElement(this.options.tagName));
        this.$element.addClass('popup mapbender-popup');
        this.$element.append(this.options.template);

        if (this.options.modal) {
            this.$modalWrap = $('<div class="popupContainer modal"><div class="overlay"></div></div>');
        }
        if (this.options.scrollable) {
            $('.popup-body', this.$element).addClass('popupScroll');
        } else {
            if (this.options.height) {
                console.warn("Ignoring height option on non-scrollable popup");
            }
            this.options.height = null;
        }
        if (this.options.resizable) {
            var resizableOptions = (typeof (this.options.resizable) !== 'object') && this.options.resizable || null;
            this.$element.resizable(resizableOptions);
        }
        if (!this.options.closeButton) {
            $('.popupHead .popupClose', this.$element).remove();
        }
        if (!this.addButtons(this.options.buttons || [])) {
            this.$element.find('.footer').remove();
        }

        var unusedOptions = {};
        if (this.options.content) {
            this.setContent(this.options.content);
            delete(this.options.content);
        }

        var self = this;
        let hasIcon = false;
        Object.keys(this.options).forEach((optionName) => {
            if (this.staticOptions_.indexOf(optionName) >= 0) return;

            var value = self.options[optionName];
            switch(optionName) {
                case 'icon':
                    hasIcon = true;
                    self.icon(value);
                    break;
                case 'title':
                    self.title(value);
                    break;
                case 'subTitle':
                case 'subtitle':    // special snowflake misspelling
                    self.subtitle(value);
                    break;
                case 'cssClass':
                    self.cssClass(value);
                    break;
                case 'height':
                    self.height(value);
                    break;
                case 'width':
                    self.width(value);
                    break;
                default:
                    unusedOptions[optionName] = value;
                    break;
            }
        });

        if (Object.keys(unusedOptions).length) {
            console.warn("Ignoring unknown options", unusedOptions);
        }
        this.registerEvents_(this.$element);
        if (!hasIcon) {
            this.$element.find('.element-icon-wrapper').remove();
        }
        this.open();
    };

    Object.assign(window.Mapbender.Popup.prototype, {
        constructor: window.Mapbender.Popup,
        defaults: {
            container: null,
            draggable: false,
            resizable: false,
            width: null,
            height: null,
            cssClass: null,
            title: null,
            subtitle: null,
            closeButton: true,
            closeOnESC: true,
            detachOnClose: true,
            destroyOnClose: false,
            modal: true,
            content: null,
            tagName: 'div',
            scrollable: true,
            template: [
                '    <div class="popupHead element-title active">',
                '      <span class="element-icon-wrapper"><i class="popupIcon"></i></span>',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right" tabindex="0"><i class="fa-solid fa-xmark"></i></span>',
                '      <div class="clear"></div>',
                '    </div>',
                '   <div class="popup-body">',
                '      <div class="popupContent"></div>',
                '      <div class="footer row no-gutters">',
                '        <div class="popupButtons"></div>',
                '        <div class="clear"></div>',
                '      </div>',
                '   </div>',
                '   <div class="popup-mobile-resize">',
                '     <i class="fa fa-angle-up"></i>',
                '     <i class="fa fa-angle-down"></i>',
                '   </div>'
            ].join("\n"),
            buttons: [],
            __dummy__: null
        },
        staticOptions_: [
            'tagName',
            'template', 'modal',
            'header', 'closeButton',
            'buttons',
            'container',
            'content',
            'destroyOnClose', 'detachOnClose',
            'closeOnOutsideClick', 'closeOnESC',
            'scrollable', 'resizable', 'draggable'
        ],
        previouslyFocusedElement_: null,
        focus: function () {
            // Store the currently focused toolbar item before focusing the popup
            if (!this.previouslyFocusedElement_) {
                this.previouslyFocusedElement_ = document.activeElement;
            }

            if (this.$element) {
                var $others = $('.popup').not(this.$element);
                $others.css('z-index', 100); // Same as .ui-front
                this.$element.css("z-index", 101);  // One more than .ui-front

                // Focus the first visible and interactive element in the dialog window
                const $visibleElements = this.$element.find('input:visible, select:visible, textarea:visible, button:visible, [tabindex]:not([tabindex="-1"]):visible');
                const $firstFocusable = $visibleElements.first();
                if ($firstFocusable.length) {
                    const $activeRadioButton = this.$element.find('input[type="radio"]:checked:visible');
                    if ($activeRadioButton.length) {
                        $activeRadioButton.focus();
                    } else {
                    $firstFocusable.focus();
                    }
                }
            }

            // Close toolbar menu on mobile when popup opens
            this.closeToolbarMenu_();
        },
        close: function() {
            if (!this.$element) {
                return true;
            }
            var token = {
                cancel: false
            };
            this.$element.trigger('close', token);
            if (token.cancel) {
                return false;
            }
            if (this.$modalWrap) {
                this.$modalWrap.detach();
            }
            if (this.$element && (this.options.detachOnClose || this.options.destroyOnClose)) {
                this.$element.detach();
            }
            if (this.options.destroyOnClose) {
                this.destroy();
            }
            if (this.options.modal && this === currentModal_) {
                currentModal_ = null;
            }

            // Restore focus to the button that opened the popup
            if (this.previouslyFocusedElement_ && typeof this.previouslyFocusedElement_.focus === 'function') {
                this.previouslyFocusedElement_.focus();
            }
            this.previouslyFocusedElement_ = null;

            return true;
        },
        destroy: function() {
            if (this.$element) {
                this.$element.remove();
                this.$element = null;
            }
            if (this.$modalWrap) {
                this.$modalWrap.remove();
                this.$modalWrap = null;
            }
        },
        icon: function(icon) {
            if(icon) {
              $('.popupIcon', this.$element).addClass(icon);
            } else {
              $('.iconBig', this.$element).addClass('noIconFound');
            }

        },
        title: function(title) {
            $('.popupTitle', this.$element).html(title || '');
        },
        subtitle: function(subtitle) {
            $('.popupSubTitle', this.$element).text(subtitle || '');
        },
        width: function(width) {
            if (typeof width === 'undefined') {
                return this.options.width;
            }
            this.$element.css('width', (null === width ? '' : width));
        },
        height: function(height) {
            if (typeof height === 'undefined') {
                return this.options.height;
            }
            this.$element.css('height', (null === height ? '' : height));
        },
        cssClass: function(cssClass) {
            if (typeof cssClass === 'undefined') {
                return this.options.cssClass || null;
            }
            if (!cssClass) {
                this.$element.removeClass(this.options.cssClass);
            } else {
                this.$element.addClass(cssClass || '');
            }
            this.options.cssClass = cssClass || '';
        },
        getContainer_: function() {
            if (!this.container_) {
                if (!this.options.container || typeof (this.options.container) === 'string') {
                    this.container_ = this.options.container || $(this.options.container).get(0) || document.body;
                } else {
                    if (typeof (this.options.container.nodeType) !== 'undefined') {
                        this.container_ = this.options.container;
                    } else {
                        this.container_ = this.options.container.get(0) || document.body;
                    }
                }
            }
            return this.container_;
        },
        registerEvents_: function($target) {
            var self = this;
            $target.on('click', '.popupClose', function(evt) {
                evt.stopPropagation();
                self.close();
            });
            $target.on('keydown', '.popupClose', function(evt) {
                if (evt.key === 'Enter') {
                    evt.preventDefault();
                    evt.stopPropagation();
                    self.close();
                }
            });
            $(document).on('keyup', function(event) {
                if (self.options.closeOnESC && event.keyCode === 27) {
                    self.close();
                }
                return true;
            });

            // Mobile resize functionality
            this.setupMobileResize_($target);
        },
        setupMobileResize_: function($target) {
            var self = this;
            var isDragging = false;
            var startY = 0;
            var startHeight = 0;

            $target.on('mousedown touchstart', '.popup-mobile-resize', function(evt) {
                if (window.innerWidth > self.mobileBreakpoint) return; // Only on mobile

                evt.preventDefault();
                isDragging = true;
                startY = evt.type === 'touchstart' ? evt.originalEvent.touches[0].clientY : evt.clientY;
                startHeight = self.$element.height();

                $('body').addClass('popup-resizing').css('user-select', 'none');
            });

            $(document).on('mousemove touchmove', function(evt) {
                if (!isDragging) return;

                evt.preventDefault();
                var currentY = evt.type === 'touchmove' ? evt.originalEvent.touches[0].clientY : evt.clientY;
                var deltaY = currentY - startY;
                const newHeight = Math.max(
                    self.mobileResizeMinHeight,
                    Math.min(
                        self.mobileResizeMaxHeight,
                        startHeight + deltaY,
                        window.innerHeight - self.$element[0].getBoundingClientRect().y
                    )
                );
                self.$element.css('height', newHeight + 'px');
            });

            $(document).on('mouseup touchend', function(evt) {
                if (isDragging) {
                    isDragging = false;
                    $('body').removeClass('popup-resizing').css('user-select', '');
                }
            });
        },
        addButtons: function(buttons) {
            const buttonset = $('.popupButtons', this.$element);
            const buttons_ = Array.isArray(buttons) && buttons || Object.values(buttons || {});
            for (let i = 0; i < buttons_.length; ++i) {
                var confOrNode = buttons_[i];
                var $btn;
                if (typeof confOrNode.nodeType !== 'undefined') {
                    $btn = $(confOrNode);
                } else {
                    $btn = $(document.createElement('button'))
                        .attr('type', 'button')
                        .text(confOrNode.label)
                        .addClass(confOrNode.cssClass || '');
                    if (confOrNode.attrDataTest !== undefined) {
                        $btn.attr('data-test', confOrNode.attrDataTest);
                    }
                    if (confOrNode.title !== undefined) {
                        $btn.attr('title', confOrNode.title);
                    }
                    if (confOrNode.callback) {
                        $btn.on('click', confOrNode.callback.bind(this));
                    }
                    if(confOrNode.iconClass !== undefined) {
                        var $icon = $('<i>').addClass(confOrNode.iconClass);
                        $btn.prepend($icon).prepend(' ');
                    }
                }
                buttonset.append($btn);
            }
            return buttons_.length > 0;
        },
        setContent: function(content) {
            $('.popupContent', this.$element).html(content);
        },
        open: function() {
            if (this.options.modal) {
                if (currentModal_ && this !== currentModal_) {
                    currentModal_.close();
                }
                currentModal_ = this;
            }

            // Set toolbar bottom position for mobile layouts
            this.setToolbarBottomPosition_();

            var container = this.getContainer_();
            if(!this.options.detachOnClose || !$.contains(document, this.$element[0])) {
                if (this.$modalWrap) {
                    this.$modalWrap.prepend(this.$element);
                    this.$modalWrap.appendTo(container);
                } else {
                    this.$element.appendTo(container);
                }
            }

            // Auto-size popup based on screen size
            this.adjustPopupSizeForScreen_();

            if (this.options.draggable) {
                var containment = (this.options.modal && this.$modalWrap) || this.options.container && container || false;
                this.$element.draggable({
                    handle: $('.popupHead', this.$element),
                    containment: containment,
                    scroll: false
                });
                Mapbender.restrictPopupPositioning(this.$element);
            }
            this.focus();
        },
        closeToolbarMenu_: function () {

            if (window.innerWidth > this.mobileBreakpoint) {
                return;
            }
            // Trigger click on open menu button to close it
            var $openMenu = $('.toolBar .menu-wrapper.open > button');
            if ($openMenu.length) {
                $openMenu.trigger('click');
            }
        },
        adjustPopupSizeForScreen_: function() {

            var isMobile = window.innerWidth <= this.mobileBreakpoint;

            if (!this.mobileAutoSize || !isMobile) {
                return;
            }

            var viewportHeight = window.innerHeight;

            // Calculate available space considering toolbars
            var toolbarHeight = $('.toolBar.top')[0].getBoundingClientRect().height + $('.toolBar.bottom')[0].getBoundingClientRect().height;
            var maxAvailableHeight = (viewportHeight * this.mobileMaxHeightRatio) - toolbarHeight;

            // Check if popup is currently larger than available space and adjust height if necessary
            var currentHeight = this.$element.height();
            if (currentHeight > maxAvailableHeight) {
                this.$element.css('height', Math.max(this.mobileResizeMinHeight, maxAvailableHeight) + 'px');
            }

            // Ensure popup content is scrollable if needed
            var $popupBody = this.$element.find('.popup-body');
            if ($popupBody.length && this.$element.height() < currentHeight) {
                $popupBody.css('overflow-y', 'auto');
            }
        },
        setToolbarBottomPosition_: function() {
            // Set CSS property for toolbar bottom position on mobile
            if (window.innerWidth <= this.mobileBreakpoint) {
                var $toolbar = $('.toolBar').first();
                if ($toolbar.length) {
                    var toolbarRect = $toolbar[0].getBoundingClientRect();
                    var toolbarBottom = toolbarRect.bottom;
                    document.documentElement.style.setProperty('--toolbar-bottom', toolbarBottom - 1 + 'px');
                }
            }
        }
    });
}());

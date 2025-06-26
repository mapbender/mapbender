;!(function() {
    "use strict";
    window.Mapbender = window.Mapbender || {};

    var currentModal_ = null;

    window.Mapbender.Popup = function Popup(options) {
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
        this.addButtons(this.options.buttons || []);

        var unusedOptions = {};
        if (this.options.content) {
            this.setContent(this.options.content);
            delete(this.options.content);
        }

        var self = this;
        Object.keys(this.options).forEach((optionName) => {
            if (this.staticOptions_.indexOf(optionName) >= 0) return;

            var value = self.options[optionName];
            switch(optionName) {
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
                '    <div class="popupHead">',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right" tabindex="0"><i class="fas fa-xmark fa-lg"></i></span>',
                '      <div class="clear"></div>',
                '    </div>',
                '   <div class="popup-body">',
                '      <div class="popupContent"></div>',
                '   </div>',
                '   <div class="footer row no-gutters">',
                '       <div class="popupButtons"></div>',
                '       <div class="clear"></div>',
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
                console.log(this.previouslyFocusedElement_);
            }

            if (this.$element) {
                var $others = $('.popup').not(this.$element);
                $others.css('z-index', 100); // Same as .ui-front
                this.$element.css("z-index", 101);  // One more than .ui-front

                // Focus the first visible and interactive element in the dialog window
                const $visibleElements = this.$element.find('input:visible, select:visible, textarea:visible, button:visible, [tabindex]:not([tabindex="-1"]):visible').not('.popupClose');
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
            $(document).on('keyup', function(event) {
                if (self.options.closeOnESC && event.keyCode === 27) {
                    self.close();
                }
                return true;
            });
        },
        addButtons: function(buttons) {
            var self = this;
            var buttonset = $('.popupButtons', this.$element);
            var buttons_ = Array.isArray(buttons) && buttons || Object.values(buttons || {});
            for (var i = 0; i < buttons_.length; ++i) {
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
                    if (confOrNode.callback) {
                        $btn.on('click', confOrNode.callback.bind(self));
                    }
                }
                buttonset.append($btn);
            }
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

            var container = this.getContainer_();
            if(!this.options.detachOnClose || !$.contains(document, this.$element[0])) {
                if (this.$modalWrap) {
                    this.$modalWrap.prepend(this.$element);
                    this.$modalWrap.appendTo(container);
                } else {
                    this.$element.appendTo(container);
                }
            }
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
        }
    });
}());

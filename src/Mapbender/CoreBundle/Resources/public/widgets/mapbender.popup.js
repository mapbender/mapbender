/**
 * These popup is not jQuery UI widgets initialized on an existing DOM node.
 * Instead the constructor creates its own DOM node around the content passed
 * inside options.
 *
 * Content can be a variety of things:
 *   - Simple string
 *   - DOM Node
 *   - jQuery wrapped DOM nodes
 *   - Ajax promise
 *   - A array of all the above
 */
;!(function() {
    "use strict";
    window.Mapbender = window.Mapbender || {};

    /**
     * @typedef {Object} PopupButtonConfig
     * @property {String} label
     * @property {function} [callback]
     * @property {String} [cssClass]
     */

    var currentModal_ = null;

    function PopupCommon(options) {
        this.options = Object.assign({}, this.defaults, options);
        delete this.options['__dummy__'];
    }
    PopupCommon.prototype = {
        constructor: PopupCommon,
        $element: null,
        container_: null,
        defaults: {
            container: null,
            draggable: false,
            // Resizable, you can pass true or an object of resizable options
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
            __dummy__: null
        },
        focus: function () {
            if (this.$element) {
                var $others = $('.popup').not(this.$element);
                $others.css('z-index', 100); // Same as .ui-front
                this.$element.css("z-index", 101);  // One more than .ui-front
            }
        },
        /**
         * Close the popup, removing it from the container.
         * If the token passed with the close event returns
         * true for the cancel property, closing the popup
         * will be aborted.
         */
        close: function() {
            if (!this.$element) {
                return true;
            }
            // Allow listeners to abort closing by modifying event data
            var token = {
                cancel: false
            };
            this.$element.trigger('close', token);
            return !token.cancel;
        },
        destroy: function() {
            if (this.$element) {
                this.$element.remove();
                this.$element = null;
            }
        },
        /**
         * Update title html
         * @param {string} title
         */
        title: function(title) {
            $('.popupTitle', this.$element).html(title || '');
        },
        /**
         * Update subtitle text
         * @param {string} subtitle
         */
        subtitle: function(subtitle) {
            $('.popupSubTitle', this.$element).text(subtitle || '');
        },
        /**
         * Set or get popup width inline style
         * @param {Number|String} [width] null will unset
         */
        width: function(width) {
            if (typeof width === 'undefined') {
                return this.options.width;
            }
            this.$element.css('width', (null === width ? '' : width));
        },
        /**
         * Set or get popup height inline style
         * @param {Number|String} [height] null will unset
         */
        height: function(height) {
            if (typeof height === 'undefined') {
                return this.options.height;
            }
            this.$element.css('height', (null === height ? '' : height));
        },
        /**
         * Set or get css class on popup
         * @param {string}  cssClass, null unsets, undefined gets
         */
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
                        // Hope for jQuery object, fail if it isn't, fall back to body if
                        // it's empty
                        this.container_ = this.options.container.get(0) || document.body;
                    }
                }
            }
            return this.container_;
        },
        registerEvents_: function($target) {
            var self = this;
            // Focus on click
            $target.on('click', function() {
                // avoid focusing after dom destruction
                if ((self.$element || []).length) {
                    self.focus();
                }
            });
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
        renderButton_: function(confOrNode, key, buttons) {
            if (typeof confOrNode.nodeType !== 'undefined') {
                return confOrNode;
            }
            var $btn;
            if (buttons && !Array.isArray(buttons)) {
                console.warn("Generating link-style popup button because buttons option is an object", confOrNode, key);
                $btn = $(document.createElement('a'))
                    .attr('href', '#' + this.$element.attr('id') + '/button/' + key)
                ;
            } else {
                $btn = $(document.createElement('button'))
                    .attr('type', 'button')
                ;
            }
            $btn.text(confOrNode.label);
            $btn.addClass(confOrNode.cssClass || '');
            if (confOrNode.attrDataTest !== undefined){
                $btn.attr('data-test', confOrNode.attrDataTest);
            }
            return $btn.get(0);
        },
    };

    window.Mapbender.Popup = function Popup(options) {
        PopupCommon.apply(this, arguments);

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
    window.Mapbender.Popup.prototype = Object.create(PopupCommon.prototype);
    Object.assign(window.Mapbender.Popup.prototype, {
        constructor: window.Mapbender.Popup,
        defaults: Object.assign({}, PopupCommon.prototype.defaults, {
            content: null,
            tagName: 'div',
            scrollable: true,
            // Content, can be simple string, DOM nodes, jQuery nodes
            template: [
                '    <div class="popupHead">',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right"><i class="fas fa-xmark fa-lg"></i></span>',
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
            /**@type {Array<PopupButtonConfig>} */
            buttons: [],
            __dummy__: null
        }),
        option: function(key, value) {
            // Handle bad option capitalization for special snowflakes
            var method = this[key] || this[key.toLowerCase()];
            if (typeof method == 'function') {
                method.call(this, value);
            } else {
                console.error('No accessor for "' + key + '"');
            }
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
        },
        close: function() {
            if (!PopupCommon.prototype.close.apply(this, arguments)) {
                return false;
            }
            // NOTE: event may have called destroy or removed the $element some other way
            if (this.$modalWrap) {
                if (this.$element) {
                    this.$element.detach();
                }
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
        },
        destroy: function() {
            PopupCommon.prototype.destroy.apply(this, arguments);
            if (this.$modalWrap) {
                this.$modalWrap.detach();
            }
        },
        addButtons: function(buttons) {
            var self = this;
            var buttonset = $('.popupButtons', this.$element);
            var buttons_ = Array.isArray(buttons) && buttons || Object.values(buttons || {});
            for (var i = 0; i < buttons_.length; ++i) {
                var confOrNode = buttons_[i];
                var $btn = $(this.renderButton_(confOrNode));
                if (confOrNode.callback) {
                    $btn.on('click', (function(conf) {
                        return function(event) {
                            return conf.callback.call(self, event);
                        }
                    }(confOrNode)));
                }
                buttonset.append($btn);
            }
            buttonset.parent().toggleClass('hidden', !buttonset.children().length);
        },
        /**
         * Contents may be:
         *   - simple string
         *   - DOM Nodes
         *   - jQuery wrappend DOM nodes
         *   - Ajax promise
         *   - Array of all the above
         *
         * @param  {*} content
         */
        setContent: function(content) {
            if (!content) {
                return;
            }
            if (Array.isArray(content)) {
                for (var i=0; i < content.length; i++) {
                    this.addContent(content[i], 0 === i);
                }
            } else {
                this.addContent(content, true);
            }
        },
        /**
         * Add content. Can be string, DOM node, jQuery node or Ajax
         * @param {*} content
         * @param {boolean} [emptyFirst]  Empty content container before adding
         */
        addContent: function(content, emptyFirst) {
            var contentContainer = $('.popupContent', this.$element.get(0));

            if (emptyFirst) {
                contentContainer.empty();
            }

            var contentItem = $('<div class="contentItem"/>');

            if (typeof content.then === 'function') {
                // xhr promise
                console.warn("Deprecated xhr passed as content. Wait for response before opening popups or adding content");
                content.then(function(response) {
                    contentItem.append(response);
                }, function() {
                    contentItem.empty().append('<p class="error">Ajax error</p>');
                });
            } else {
                contentItem.append(content);
            }
            contentContainer.append(contentItem);
        },
        __dummy__: null
    });
}());

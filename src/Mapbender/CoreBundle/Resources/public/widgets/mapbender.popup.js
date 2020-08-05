/**
 * Mapbender Popup
 * Replaces FOM Popup
 *
 * This popup is not a jQuery UI widget, as thus would require to have the DOM
 * setup before. Instead you call the Mapbender.Popup constructor with a options
 * object and a JavaScript object will be created which knows it's own DOM,
 * ready to be used.
 *
 * Everything can be configured at the start or set to a new value later on
 * (except for the DOM template which is fixed after initialization).
 *
 * For all available default options which can be overriden in the constructor
 * options, see the Popup.prototype.defaults object.
 *
 * Content can be a variety of things:
 *   - Simple string
 *   - DOM Node
 *   - jQuery wrapped DOM nodes
 *   - Ajax promise
 *   - A array of all the above
 *
 * Event available on the element which you can get with .$element
 *   - close   - before closing the dialog
 *
 */
(function($) {
    var counter = 0;
    var currentZindex = 10000;
    var currentModal_ = null;
    var container_ = null;

    function initContainer_() {
        if (!container_) {
            container_ = document.body;
            var positionRule = $(container_).css('position');
            if (!positionRule || positionRule === 'static') {
                $(container_).css('position', 'relative');
            }
        }
        return container_;
    }

    $(document).on('click', '.popupContainer.modal .overlay', function(e) {
        if (currentModal_ && currentModal_.options.closeOnOutsideClick) {
            currentModal_.close();
        }
    });

    /**
     * Popup constructor.
     *
     * @param  {Object} options   Non-Default Options
     * @return {Popup}  Popup instance
     */
    var Popup = function(options) {
        var self = this;

        // Create final options
        this.options = $.extend({}, this.defaults, options);
        if (this.options.closeOnPopupCloseClick) {
            console.warn("Merging deprecated option 'closeOnPopupCloseClick' into 'closeButton'");
            this.options.closeButton = true;
            delete this.options['closeOnPopupCloseClick'];
        }

        // Create DOM element
        this.$element = $(this.options.template)
            .attr('id', 'mbpopup-' + counter++);
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
            var resizableOptions = this.options.resizable;
            if (!$.isPlainObject(resizableOptions)) {
                resizableOptions = null;
            }
            this.$element.resizable(resizableOptions);
        }
        this.$element.on('click', '.popupClose', function(evt) {
            evt.stopPropagation();
            self.close();
        });
        if (!this.options.closeButton) {
            $('.popupHead .popupClose', this.$element).remove();
        }
        this.addButtons(this.options.buttons || []);

        var staticOptions = [
            'template', 'modal',
            'header', 'closeButton',
            'buttons',
            'content',
            'destroyOnClose', 'detachOnClose',
            'closeOnOutsideClick', 'closeOnESC',
            'scrollable', 'resizable', 'draggable'
        ];
        var unusedOptions = {};
        if (this.options.content) {
            this.setContent(this.options.content);
            delete(this.options.content);
        }

        _.difference(Object.keys(this.options), staticOptions).forEach(function(optionName) {
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

        // focused on popup click
        this.$element.on('click', function() {
            // avoid focusing after dom destruction
            if ((self.$element || []).length) {
                self.focus();
            }
        });

        $(document).on('keyup', this.handleKeyUp.bind(this));
        this.open();
    };

    Popup.prototype = {
        // Reference to the created popup
        $element: null,

        /**
         * Default options
         * @type {Object}
         */
        defaults: {
            template: [
                '  <div class="popup mapbender-popup">',
                '    <div class="popupHead">',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right iconCancel iconBig"></span>',
                '      <div class="clear"></div>',
                '    </div>',
                '   <div class="popup-body">',
                '      <div class="popupContent"></div>',
                '   </div>',
                '   <div class="footer row no-gutters">',
                '       <div class="popupButtons"></div>',
                '       <div class="clear"></div>',
                '   </div>',
                '  </div>'
                ].join("\n"),

            draggable: false,
            // Resizable, you can pass true or an object of resizable options
            resizable: false,

            closeButton: true,

            closeOnESC: true,
            detachOnClose: true,
            destroyOnClose: false,
            modal: true,

            scrollable: true,
            width: null,
            // Height, if not set, use custom CSS for cssClass
            height: null,
            // CSS class(es) to give to popup
            cssClass: null,

            title: null,
            subtitle: null,
            // Content, can be simple string, DOM nodes, jQuery nodes
            content: null,
            /**
             * @typedef {Object} PopupButtonConfig
             * @property {String} label
             * @property {callback} callback
             * @property {String} cssClass
             */
            /**
             * @type {Array<PopupButtonConfig>}
             */
            buttons: []
        },
        option: function(key, value) {
            switch(key) {
                default:
                    // Handle bad option capitalization for special snowflakes
                    var fct = this[key] || this[key.toLowerCase()];
                    if(typeof fct == 'function') {
                        fct.call(this, value);
                    } else {
                        if(window.console) {
                            console.error('No accessor for "' + key + '"');
                        }
                    }
            }
        },

        /**
         * Open the popup.
         */
        open: function() {
            if (this.options.modal) {
                if (currentModal_ && this !== currentModal_) {
                    currentModal_.close();
                }
                currentModal_ = this;
            }

            var container = initContainer_();
            if(!this.options.detachOnClose || !$.contains(document, this.$element[0])) {
                if (this.$modalWrap) {
                    this.$modalWrap.prepend(this.$element);
                    this.$modalWrap.appendTo(container);
                } else {
                    this.$element.appendTo(container);
                }
            }
            if (this.options.draggable) {
                var containment = (this.options.modal && this.$modalWrap) || $('body');
                this.$element.draggable({
                    handle: $('.popupHead', this.$element),
                    containment: containment
                });
            }
            this.focus();
        },

        /**
         * Raise popup z index to top.
         */
        focus: function () {
            this.$element.css("z-index",++currentZindex);
        },

        /**
         * Close the popup, removing it from the container.
         * If the token passed with the close event returns
         * true for the cancel property, closing the popup
         * will be aborted.
         */
        close: function() {
            if (!this.$element) {
                return;
            }
            var token = { cancel: false };
            this.$element.trigger('close', token);
            if (token.cancel) {
                return;
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
            if(this.options.destroyOnClose) {
                this.destroy();
            }
            if (this.options.modal && this === currentModal_) {
                currentModal_ = null;
            }
        },

        /**
         * Destructor.
         */
        destroy: function() {
            if(this.$element){
                this.$element.remove();
                this.$element = null;
            }
        },

        addButtons: function(buttons) {
            var self = this,
                buttonset = $('.popupButtons', this.$element);

            $.each(buttons, function(key, conf) {
                var button = $('<button/>', {
                    type: 'button',
                    text: conf.label
                });

                if(conf.cssClass) {
                    button.addClass(conf.cssClass);
                }

                if(conf.callback) {
                    button.on('click', function(event) {
                        conf.callback.call(self, event);
                        return false;
                    });
                }
                buttonset.append(button);
            });
            buttonset.parent().toggleClass('hidden', !buttonset.children().length);
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
        handleKeyUp: function(event) {
            if (this.options.closeOnESC && event.keyCode === 27) {
                this.close();
            }
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

            if($.isArray(content)) {
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

            if(emptyFirst) {
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

        /**
         * Set or get popup width.
         * @param  {mixed}  width, null will unset
         */
        width: function(width) {
            if(undefined === width) {
                return this.options.width;
            }

            this.$element.css('width', (null === width ? '' : width));
        },

        /**
         * Set popup height.
         * @param  {mixed}  height, any falsy value will unset height
         */
        height: function(height) {
            if(undefined === height) {
                return this.options.height;
            }

            this.$element.css('height', (null === height ? '' : height));
        },

        /**
         * Set or get css class on popup
         * @param  {string}  cssClass, null unsets, undefined gets
         */
        cssClass: function(cssClass) {
            if(undefined === cssClass) {
                return this.options.cssClass;
            }

            if(null === cssClass) {
                this.$element.removeClass(this.options.cssClass);
            } else {
                this.$element.addClass(cssClass);
            }
            this.options.cssClass = cssClass;
        }
    };

    window.Mapbender = window.Mapbender || {};
    window.Mapbender.Popup = Popup;
})(jQuery);

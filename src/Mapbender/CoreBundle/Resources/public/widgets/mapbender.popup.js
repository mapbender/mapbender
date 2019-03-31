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
 * Events available on the element which you can get with .getElement:
 *   - open    - before opening the dialog
 *   - opened  - after the dialog has fully openend
 *   - focus   - after the dialog becomes an focus
 *   - close   - before closing the dialog
 *   - closed  - after the dialog has been fully closed
 *   - destroy - just before the popup is destroyed. Last dance, anyone?
 *
 * @TODOs:
 *   - CSS for following classes:
 *     - noTitle
 *     - noSubTitle
 *     - ajax content
 *     - ajaxWaiting content
 *     - ajaxFailed content
 */
(function($) {
    var counter = 0;
    var currentZindex = 10000;
    var currentModal_ = null;
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

        options.closeButton = !!(options.closeButton || options.closeOnPopupCloseClick);

        // Create DOM element
        this.$element = $(this.options.template)
            .attr('id', 'mbpopup-' + counter++);
        if (this.options.modal) {
            this.$modalWrap = $('<div class="popupContainer modal"><div class="overlay"></div></div>');
            if (this.options.closeOnOutsideClick) {
                this.$modalWrap.on('click', function(evt) {
                    if (!$(evt.target).closest(self.$element).length) {
                        self.close();
                    }
                });
            }
        }
        this.$element.toggleClass('noCloseButton', !this.options.closeButton);
        $('.popupHead', this.$element).toggleClass('hidden', !this.options.header);
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
        if (this.options.closeButton) {
            this.$element.on('click', '.popupClose', $.proxy(this.close, this));
        } else {
            $('.popupClose', this.$element).remove();
        }
        this.addButtons(this.options.buttons || []);

        var staticOptions = [
            'template', 'autoOpen', 'modal',
            'header', 'closeButton',
            'buttons',
            'content',
            'destroyOnClose', 'detachOnClose',
            'closeOnOutsideClick',
            'scrollable', 'resizable'
        ];
        // use the options mechanism to set up most of the things
        $.each(this.options, function(key, value) {
            // Skip options which already have been used or have to be used late
            if (key !== 'autoOpen' && -1 === staticOptions.indexOf(key)) {
                self.option(key, value);
            }
        });
        if (this.options.content) {
            this.setContent(this.options.content);
            delete(this.options.content);
        }

        // focused on popup click
        self.$element.on("click", $.proxy(self.focus, self));

        // Open if required
        if(this.options.autoOpen) {
            this.open();
        }
    };

    Popup.prototype = {
        // Reference to the created popup
        $element: null,

        // Containing element
        $container: $('body'),

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

            // Is popup draggable (showHeader must be true)
            draggable: false,
            // Resizable, you can pass true or an object of resizable options
            resizable: false,

            header: true,
            closeButton: true,

            autoOpen: true,
            closeOnESC: true,
            detachOnClose: true,
            closeOnOutsideClick: false,
            destroyOnClose: false,
            modal: true,

            scrollable: true,
            // Width, if not set, use custom CSS for cssClass
            width: null,
            // Height, if not set, use custom CSS for cssClass
            height: null,
            // CSS class(es) to give to popup
            cssClass: null,

            title: null,
            subtitle: null,
            // Content, can be simple string, DOM nodes, jQuery nodes or Ajax
            content: null,

            // Buttons, object with key which becomes identifier, label and
            // callback
            buttons: {
                'ok': {
                    label: 'Ok',
                    callback: function() {
                        this.close();
                    }
                }
            }
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
         * Open the popup, optionally giving new content.
         * This will insert the popup into the container.
         *
         * @param {*} [content]  New content, if any
         */
        open: function(content) {
            if (this.options.modal) {
                if (currentModal_ && this !== currentModal_) {
                    currentModal_.close();
                }
                currentModal_ = this;
            }
            var self = this;

            if (content) {
                this.setContent(content);
            }

            // why?
            this.$element.trigger('open');  // why?
            if(!this.options.detachOnClose || !$.contains(document, this.$element[0])) {
                if (this.$modalWrap) {
                    this.$modalWrap.prepend(this.$element);
                    this.$modalWrap.appendTo(this.$container);
                } else {
                    this.$element.appendTo(this.$container);
                }
            }
            // why?
            window.setTimeout(function() {
                self.focus();
                self.$element.trigger('openend');   // why?
            }, 100);
        },

        /**
         * Focus the popup.
         * This will show popup on top.
         *
         * @fires "focus"
         */
        focus: function (event) {
            if (this.$element) {
                this.$element.css("z-index",++currentZindex);
                if (!event) {
                    // Only trigger event this method was called programmatically.
                    this.$element.trigger('focus'); // why?
                }
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
            if (!this.options.destroyOnClose && this.$element) {
                this.$element.trigger('closed'); // why?
            }
        },

        /**
         * Destructor.
         */
        destroy: function() {
            if(this.$element){
                this.$element.trigger('destroy'); // why?
                this.$element.remove();
                this.$element = null;
            }
        },

        addButtons: function(buttons) {
            var self = this,
                buttonset = $('');

            $.each(buttons, function(key, conf) {
                var button = $('<a/>', {
                    href: '#' + self.$element.attr('id') + '/button/' + key,
                    html: conf.label
                });

                if(conf.cssClass) {
                    button.addClass(conf.cssClass);
                }

                if(conf.callback) {
                    button.on('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        conf.callback.call(self, event);
                        return false;
                    });
                }
                buttonset = buttonset.add(button);
            });
            $('.popupButtons', this.$element.get(0)).append(buttonset);
        },

        /**
         * Set or get title
         * @param  {string} title, null unsets, undefined gets
         * @return {string}
         */
        title: function(title) {
            var titleNode = $('.popupTitle', this.$element.get(0));

            if(undefined === title) {
                return this.options.title;
            }

            if(null === title) {
                titleNode.empty().addClass("hidden");
            } else {
                titleNode.html(title);
            }
            this.options.title = title;
        },

        /**
         * Set or get subtitle
         * @param  {string} subtitle, null unsets, undefined gets
         * @return {string}
         */
        subtitle: function(subtitle) {
            var subtitleNode = $('.popupSubTitle', this.$element.get(0));

            if(undefined === subtitle) {
                return this.options.subtitle;
            }

            if(null === subtitle) {
                subtitleNode.empty();
            } else {
                subtitleNode.text(subtitle);
            }
            this.options.subtitle = subtitle;
        },

        /**
         * Set or get closeOnESC
         * @param  {boolean} state, undefined gets
         * @return {boolean}
         */
        closeOnESC: function(state) {
            if(undefined === state) {
                return this.options.closeOnESC;
            }

            if(state) {
                var that = this;
                $(document).on("keyup", function(e){
                  if(e.keyCode == 27) that.close();
                });
            }

            this.options.closeOnESC = state;
        },

        /**
         * Set or get draggable
         * @param  {boolean} state, undefined gets
         * @return {boolean}
         */
        draggable: function(state) {
            var widget = this;
            var options = widget.options;
            var element = widget.$element;

            if(!state) {
                return options.draggable;
            }

            element.on('openend', function() {
                var $body = $("body");
                $(element).draggable({
                    handle:      $('.popupHead', element),
                    containment: $body
                });
            });

            options.draggable = state;
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
         * @param  {mixed}    content     Content
         * @param  {boolean}  emptyFirst  Empty content container before adding
         */
        addContent: function(content, emptyFirst) {
            var contentContainer = $('.popupContent', this.$element.get(0));

            if(emptyFirst) {
                contentContainer.empty();
            }

            var contentItem = $('<div class="contentItem"/>');

            var elementPrototype = typeof HTMLElement !== "undefined"
                                   ? HTMLElement : Element;

            if(typeof content === 'string') {
                // parse into HTM first
                contentItem.append($('<p>', {
                    'html': content,
                    'class': 'clear'
                }));
            } else if(content instanceof elementPrototype) {
                contentItem.append(content);
            } else if(content instanceof $) {
                contentItem.append(content);
            } else if(undefined !== content.readyState) {
                // Ajax can be finished or not
                if(4 === content.readyState) {
                    // If finished, insert result or failure notice
                    if(200 == content.status) {
                        contentItem.append(content.responseText);
                    } else {
                        contentItem
                            .addClass('ajax ajaxFailed')
                            .html('Ajax failed.');
                    }
                } else {
                    // If not finished, insert placeholder and wait until
                    // request has returned
                    contentItem
                        .addClass('ajax ajaxWaiting')
                        .html('Loading...');
                    content
                        .done(function(responseText, state, jqXHR) {
                            contentItem
                                .empty()
                                .append(responseText)
                                .removeClass('ajaxWaiting');

                        })
                        .fail(function(jqXHR, state, message) {
                            contentItem
                            .empty()
                            .append(message)
                            .removeClass('ajaxWaiting')
                            .addClass('ajaxFailed');
                        });
                }
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

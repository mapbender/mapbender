/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */

/**
 * FOM Popup
 * Deprecated. Does not work without Mapbender CSS and is used exclusively in Mapbender.
 *
 * Mapbender will receive its own popup widget implementation so the multitude of interdependent
 * markup-vs-css issues can be fixed in one place.
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
 *
 * @TODOs:
 *   - CSS for following classes:
 *     - ajax content
 */
(function($) {
    var counter = 0;
    var currentZindex = 10000;
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

        // Create DOM element
        this.$element = $(this.options.template)
            .attr('id', 'mbpopup-' + counter++);

        // use the options mechanism to set up most of the things
        $.each(this.options, function(key, value) {
            // Skip options which already have been used or have to be used late
            if(key == 'template' || key == 'autoOpen') {
                return;
            }

            self.option(key, value);
        });

        // focused on popup click
        self.$element.on("click", $.proxy(self.focus, self));

        this.$element.on('click', '.popupClose', function(evt) {
            evt.stopPropagation();
            self.close();
        });

        // Open if required
        if(this.options.autoOpen) {
            this.open();
        }
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
                '  <div class="popup fom-popup">',
                '    <div class="popupHead">',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right"><i class="fa fas fa-times fa-2x"></i></span>',
                '    </div>',
                '    <div class="popupScroll">',
                '      <div class="clear popupContent"></div>',
                '    </div>',
                '    <div class="popupButtons"></div>',
                '    <div class="clearContainer"></div>',
                '  </div>'].join("\n"),

            container: '.map-overlay .overlay-fill',
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
                case 'header':
                    if(undefined === value) {
                        return this.options[key];
                    }
                    var header = $('.popupHead', this.$element.get(0));

                    if(value) {
                        header.removeClass('hidden');
                    } else {
                        header.addClass('hidden');
                    }
                break;

                case 'modal':
                case 'closeButton':
                case 'destroyOnClose':
                case 'detachOnClose':
                case 'closeOnOutsideClick':
                    if (typeof value === 'undefined') {
                        return this.options[key];
                    } else {
                        this.options[key] = value;
                    }
                break;

                default:
                    var fct = this[key];
                    if(typeof fct == 'function') {
                        this[key](value);
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
         * @param  {mixed}  content  New content, if any
         */
        open: function(content) {
            var self = this;
            var selfElement = this.$element;

            if(content) {
                this.content(content);
            }

            this.$element.trigger('open');
            this.modalWrapper_ = this.options.modal && this.createModalWrapper_() || null;
            var container_ = this.getContainer_();
            if (!this.options.detachOnClose || !$.contains(container_, this.$element.get(0))) {
                this.$element.appendTo(this.modalWrapper_ || container_);
            }
            var draggableOptions = this.options.draggable && !this.options.modal && {
                handle: $('.popupHead', this.$element).get(0),
                containment: container_
            };
            if (this.modalWrapper_) {
                $(this.modalWrapper_).appendTo(document.body);
            }

            window.setTimeout(function() {
                self.focus();
                if (draggableOptions) {
                    selfElement.css('position', 'relative');
                    selfElement.draggable(draggableOptions);
                }
                selfElement.trigger('openend');
            }, 100);
        },

        /**
         * Focus the popup.
         * This will show popup on top.
         *
         * @fires "focus"
         */
        focus: function (event) {
          var self = this;
          var selfElement = this.$element;
          selfElement.css("z-index",++currentZindex);
          if(!event) {
            // Only trigger event this method was called programmatically.
            selfElement.trigger('focus');
          }
        },

        /**
         * Close the popup, removing it from the container.
         * If the token passed with the close event returns
         * true for the cancel property, closing the popup
         * will be aborted.
         */
        close: function() {
            var selfElement = this.$element;

            var token = { cancel: false };
            selfElement.trigger('close', token);
            if(token.cancel) {
              return;
            }

            selfElement.removeClass("show");
            if(this.options.detachOnClose || this.options.destroyOnClose) {
                selfElement.detach();
            }
            if(this.options.destroyOnClose) {
                this.destroy();
            }
            if (this.modalWrapper_) {
                $(this.modalWrapper_).remove();
                this.modalWrapper_ = null;
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

        /**
         * Set or get buttons
         * @param  {Object} buttons, null unsets, undefined gets
         * @return {[type]}
         */
        buttons: function(buttons) {
            if(undefined === buttons) {
                return this.options.buttons;
            }

            if(null === buttons) {
                $('.popupButtons', this.$element.get(0)).empty();
            } else {
                this.addButtons(buttons);
            }
            this.options.buttons = buttons;
        },

        addButtons: function(buttons) {
            var self = this,
                buttonset = []
            ;

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
                        conf.callback.call(self, event);
                    });
                }
                buttonset.push(button);
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
                subtitleNode.html(subtitle);
            }
            this.options.subtitle = subtitle;
        },
        /**
         * Set or get resizable status
         * @param {mixed} state, undefined gets, true or false sets state, an object of options for jQuery UI resizable
         *                can also be passed
         * @return {boolean}
         */
        resizable: function(state) {
            if(undefined === state) {
                return this.options.resizable;
            }

            if(state) {
                this.$element.resizable($.isPlainObject(state) ? state : null);
            } else {
                if (this.$element.data('uiResizable')) {
                    this.$element.resizable('destroy');
                }
            }
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
            if (typeof state !== 'undefined') {
                return this.options.draggable;
            } else {
                this.options.draggable = state;
            }
        },

        /**
         * Set or get contents
         *
         * Contents may be:
         *   - simple string
         *   - DOM Nodes
         *   - jQuery wrappend DOM nodes
         *   - Ajax promise
         *   - Array of all the above
         *
         * @param  {mixed} content
         */
        content: function(content) {
            if(undefined === content) {
                return this.contents;
            }

            if($.isArray(content)) {
                for(var i=0; i < content.length; i++) {
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
                this.contents = [];
                contentContainer.empty();
            }

            this.contents.push(content);

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
        },
        createModalWrapper_: function() {
            var self = this;
            var $modalWrap = $('<div class="popupContainer modal"><div class="overlay"></div></div>');
            $modalWrap.on('click', '.overlay', function() {
                if (self.options.closeOnOutsideClick) {
                    self.close();
                }
                return false;
            });
            return $modalWrap.get(0);
        },
        getContainer_: function() {
            if (typeof (this.options.container) === 'string') {
                return $(this.options.container).get(0) || document.body;
            } else {
                if (typeof (this.options.container.nodeType) !== 'undefined') {
                    return this.options.container;
                } else {
                    // Hope for jQuery object, fail if it isn't, fall back to body if
                    // it's empty
                    return this.options.container.get(0) || document.body;
                }
            }
        },
        __dummy__: null
    };

    window.FOM = window.FOM || {};
    window.FOM.Popup2 = Popup;
    if (!window.Mapbender || !window.Mapbender.Popup2) {
        window.Mapbender = window.Mapbender || {};
        window.Mapbender.Popup2 = FOM.Popup2;
    }
})(jQuery);

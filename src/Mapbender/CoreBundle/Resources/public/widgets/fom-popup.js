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
                '<div class="popupContainer fom-popup-container">',
                '  <div class="popup fom-popup">',
                '    <div class="popupHead">',
                '      <span class="popupTitle"></span>',
                '      <span class="popupSubTitle"></span>',
                '      <span class="popupClose right iconCancel iconBig"></span>',
                '    </div>',
                '    <div class="popupScroll">',
                '      <div class="clear popupContent"></div>',
                '    </div>',
                '    <div class="popupButtons"></div>',
                '    <div class="clearContainer"></div>',
                '  </div>',
                '  <div class="overlay"></div>',
                '</div>'].join("\n"),

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
            closeOnPopupCloseClick: true,
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
            var popup = $('.popup', this.$element.get(0));

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

                case 'closeButton':
                    if(undefined === value) {
                        return this.options[key];
                    }
                    if(value) {
                        popup.removeClass('noCloseButton');
                    } else {
                        popup.addClass('noCloseButton');
                    }
                break;

                // Some simple setter/getter options
                case 'destroyOnClose':
                    if(undefined === value) {
                        return this.options[key];
                    } else {
                        this.options[key] = value;
                    }
                break;

                case 'detachOnClose':
                    if(undefined === value) {
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

            selfElement.trigger('open');
            if(!this.options.detachOnClose || !$.contains(document, selfElement[0])) {
                selfElement.appendTo(this.$container);
            }
            window.setTimeout(function() {
                self.focus();
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
            selfElement.trigger('closed');
        },

        /**
         * Destructor.
         */
        destroy: function() {
            if(this.$element){
                this.$element.trigger('destroy');
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

        addButtons: function(buttons, offset) {
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
                        conf.callback.call(self, event);
                    });
                }

                buttonset = buttonset.add(button);
            });

            // @todo use offset if given
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
         * Set or get modal
         * @param  {boolean} state, undefined gets
         * @return {boolean}
         */
        modal: function(state) {
            if(undefined === state) {
                return this.options.modal;
            }

            if(state){
              this.$element.addClass("modal");
            }else{
              this.$element.removeClass("modal");
            }

            this.options.modal = state;
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
                $('.popup', this.$element).resizable($.isPlainObject(state) ? state : null);
            } else {
                var popup = $('.popup', this.$element);
                if(popup.data('uiResizable')) {
                    popup.resizable('destroy');
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
         * Set or get closeOnPopupCloseClick
         * @param  {boolean} state, undefined gets
         * @return {boolean}
         */
        closeOnPopupCloseClick: function(state) {
            if(undefined === state) {
                return this.options.closeOnPopupCloseClick;
            }

            if(state){
                $('.popupClose', this.$element.get(0)).on('click', $.proxy(this.close, this));
                $('.popupClose', this.$element.get(0)).removeClass('hidden');
            }else{
                $('.popupClose', this.$element.get(0)).off('click');
                $('.popupClose', this.$element.get(0)).addClass('hidden');
            }

            this.options.closeOnPopupCloseClick = state;
        },

        /**
         * Set or get closeOnOutsideClick
         * @param  {boolean} state, undefined gets
         * @return {boolean}
         */
        closeOnOutsideClick: function(state) {
            if(undefined === state) {
                return this.options.closeOnOutsideClick;
            }

            if(state){
                $('.overlay', this.$element.get(0)).on('click', $.proxy(this.close, this));
            }else{
                $('.overlay', this.$element.get(0)).off('click');
            }

            this.options.closeOnOutsideClick = state;
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
                var marginLeft = 100;
                var marginTop = 80;
                var popupContainer = $(">.popup", element);
                var document = $("body");
                element.draggable({
                    handle:      $('.popupHead', element),//, //,
                    containment: [
                        -marginLeft,
                        -marginTop,
                        document.width() - popupContainer.width() - marginLeft,
                        document.height() - popupContainer.height() - marginTop]
                });
            });

            options.draggable = state;
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
            var popup = $('.popup', this.$element.get(0));

            if(undefined === width) {
                return this.options.width;
            }

            popup.css('width', (null === width ? '' : width));
        },

        /**
         * Set popup height.
         * @param  {mixed}  height, any falsy value will unset height
         */
        height: function(height) {
            var popup = $('.popup', this.$element.get(0));

            if(undefined === height) {
                return this.options.height;
            }

            popup.css('height', (null === height ? '' : height));
        },

        /**
         * Set or get css class on popup
         * @param  {string}  cssClass, null unsets, undefined gets
         */
        cssClass: function(cssClass) {
            var popup = $('.popup', this.$element.get(0));

            if(undefined === cssClass) {
                return this.options.cssClass;
            }

            if(null === cssClass) {
                popup.removeClass(this.options.cssClass);
            } else {
                popup.addClass(cssClass);
            }
            this.options.cssClass = cssClass;
        }
    };

    window.FOM = window.FOM || {};
    window.FOM.Popup2 = Popup;
    if (!window.Mapbender || !window.Mapbender.Popup2) {
        window.Mapbender = window.Mapbender || {};
        window.Mapbender.Popup2 = FOM.Popup2;
    }
})(jQuery);

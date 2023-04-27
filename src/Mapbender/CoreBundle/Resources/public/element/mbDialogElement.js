;!(function($) {
    "use strict";
    /**
     * Utility base class for elements that are (or can be) shown
     * in popup dialogs.
     */
    $.widget("mapbender.mbDialogElement", {
        options: {
            autoOpen: false
        },
        /**
         * Checks if element should open a popup immediately on application
         * initialization.
         *
         * Returns true only if all of the following:
         * 1) Widget configuration option "autoOpen" is set to true
         * 2) Containing region is appropriate (see checkDialogMode)
         * 3) Responsive element controls allow the element to be shown
         *
         * @param {jQuery|HTMLElement} [element]
         * @param {Object} [options]
         * @returns boolean
         */
        checkAutoOpen: function(element, options) {
            var options_ = options || this.options;
            return options_.autoOpen
                && this.checkDialogMode(element)
                && this.checkResponsiveVisibility(element)
            ;
        },
        /**
         * Checks the markup region containing the element for reasonable
         * dialog mode behaviour.
         * I.e. returns true if element is placed in "content" region
         * in a fullscreen template; returns false if element is placed
         * in a sidepane or mobile pane.
         *
         * @param {jQuery|HTMLElement} [element]
         * @returns boolean
         */
        checkDialogMode: function(element) {
            return Mapbender.ElementUtil.checkDialogMode(element || this.element);
        },
        /**
         * @param {jQuery|HTMLElement} [element]
         * @returns boolean
         */
        checkResponsiveVisibility: function(element) {
            return Mapbender.ElementUtil.checkResponsiveVisibility(element || this.element);
        },
        notifyWidgetDeactivated: function() {
            $(this.element).trigger('mapbender.elementdeactivated', {
                widget: this,
                sender: this,
                active: false
            });
        },
        notifyWidgetActivated: function() {
            $(this.element).trigger('mapbender.elementactivated', {
                widget: this,
                sender: this,
                active: true
            });
        },
        __dummy__: null
    });
}(jQuery));

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
            var $element = element && $(element) || this.element;
            return !$element.closest('.sideContent, .mobilePane').length;
        },
        /**
         * @param {jQuery|HTMLElement} [element]
         * @returns boolean
         */
        checkResponsiveVisibility: function(element) {
            // Use (non-cascaded!) applied CSS visibility rule
            // Mapbender responsive controls use display: none
            var $element = element && $(element) || this.element;
            return $element.css('display') !== 'none';

        },
        __dummy__: null
    });
}(jQuery));

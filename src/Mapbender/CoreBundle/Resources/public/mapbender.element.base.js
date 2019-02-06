(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseElement", {

        /**
         * Destroy callback
         *
         * @private
         */
        destroy: function () {
            this.functionIsDeprecated();
        },

        /**
         * Private destroy
         *
         * @private
         */
        _destroy: function () {
            this.functionIsDeprecated();
        },

        /**
         * Notification that function is deprecated
         */
        functionIsDeprecated: function () {
            console.warn(new Error("Function marked as deprecated"));
        }
    });

})(jQuery);

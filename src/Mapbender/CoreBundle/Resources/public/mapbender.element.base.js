(function($) {

    $.widget("mapbender.mbBaseElement", {

        /**
         * On ready callback
         */
        ready: function() {
            this.functionIsDeprecated();
        },

        /**
         * Private on ready
         *
         * @private
         */
        _ready: function() {
            this.functionIsDeprecated();
        },

        /**
         * Destroy callback
         *
         * @private
         */
        destroy: function() {
            this.functionIsDeprecated();
        },

        /**
         * Private destroy
         *
         * @private
         */
        _destroy: function() {
            this.functionIsDeprecated();
        },

        /**
         * Notification that function is deprecated
         */
        functionIsDeprecated: function() {
            console.warn(new Error("Function marked as deprecated"));
        }
    });

})(jQuery);

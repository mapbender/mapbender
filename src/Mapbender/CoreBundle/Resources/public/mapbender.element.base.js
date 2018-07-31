(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseElement", {

        /**
         * On ready callback
         */
        ready: function () {
            this.functionIsDeprecated();

            var widget = this;

            _.each(widget.readyCallbacks, function (readyCallback) {
                if (typeof (readyCallback) === 'function') {
                    readyCallback();
                }
            });

            // Mark as ready
            widget.readyState = true;

            // Remove handlers
            widget.readyCallbacks.splice(0, widget.readyCallbacks.length);

        },

        /**
         * Private on ready
         *
         * @private
         */
        _ready: function (callback) {
            this.functionIsDeprecated();

            var widget = this;
            if (widget.readyState) {
                if (typeof (callback) === 'function') {
                    callback();
                }
            } else {
                widget.readyCallbacks.push(callback);
            }
        },

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

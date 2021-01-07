(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseElement", {
        /**
         * Called when a widget is becoming visible through user action.
         * This is (currently only) used by sidepane machinery.
         * This is never called before the widget has sent its 'ready' event.
         * This SHOULD NOT open a popup dialog (though opening a popup dialog MAY implicitly also
         * call this method, if it performs important state changes).
         * @see element-sidepane.js
         *
         * This method doesn't receive any arguments and doesn't need to return anything.
         */
        reveal: function() {
            // nothing to do in base implementation
        },
        /**
         * Called when a widget is getting hidden through user action.
         * This is (currently only) used by sidepane machinery.
         * NOTE: This method MAY be called even if the widget never sent a 'ready' event.
         * Overridden versions MAY close any popup dialogs owned by the widget, though elements in the
         * side pane should usually never reside inside a popup themselves.
         * @see element-sidepane.js
         *
         * This method doesn't receive any arguments and doesn't need to return anything.
         */
        hide: function() {
            // nothing to do in base implementation
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

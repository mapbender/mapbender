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


if (!Object.keys) {
    Object.keys = function(obj) {
        if (obj !== Object(obj)) {
            throw new TypeError('Object.keys called on a non-object');
        }

        var keys=[], property;
        for (property in obj) {
            if (Object.prototype.hasOwnProperty.call(obj,property)) {
                keys.push(property);
            }
        }

        return keys;
    };
}


if (!Object.entries) {
    Object.entries = function (obj) {
        var ownProps = Object.keys(obj),
            i = ownProps.length,
            resArray = new Array(i); // preallocate the Array

        while (i--) {
            resArray[i] = [ownProps[i], obj[ownProps[i]]];
        }

        return resArray;
    };
}
/**
 * Object.keys polyfill
 */
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

/**
 * Object.entries polyfill
 */
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

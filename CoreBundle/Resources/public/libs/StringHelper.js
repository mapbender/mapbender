/**
 * String Helper library
 *
 * Using example:
 *      StringHelper.parseHttpRequest( location.href.split('#')[1] );
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 27.01.2015 by WhereGroup GmbH & Co. KG
 */
window.StringHelper = new function() {

    var self = this;
    var reKeys = /[^\[\]]+/g;
    var reTiles = /[^=\?\&]+/g;

    /**
     * Get matched groups by RegExp
     *
     * @param reg
     * @param text
     * @return {Array}
     */
    self.matchGroups = function(reg, text) {
        var match;
        var result = [];
        while (match = reg.exec(text)) {
            result.push(match[0]);
        }
        return result;
    };

    /**
     * Cast string value to native type
     *
     * @param val
     * @return {*}
     */
    self.castValue = function(val) {
        var r;
        if(val == "false") {
            r = false;
        } else if(val == "true") {
            r = true;
        } else if(!isNaN(val)) {
            r = parseInt(val);
        } else {
            r = val;
        }
        return r;
    };

    /**
     * Parse and return HTTP request as an object
     *
     * @param url
     * @return {{}}
     */
    self.parseHttpRequest = function(uri) {
        var matches = uri ? self.matchGroups(reTiles, uri) : {};
        var len = matches.length;
        var result = {};
        var key, keyLength, subResult, keys, value, z, i;

        for (i = 0; i < len; i += 2) {
            keys = self.matchGroups(reKeys, matches[i]);
            value = self.castValue(matches[i + 1]);
            subResult = result;
            keyLength = keys.length;
            for (z = 0; z < keyLength; z++) {
                key = keys[z];
                if(z == keyLength - 1) {
                    subResult[key] = value;
                } else if(!subResult.hasOwnProperty(key)) {
                    subResult[key] = {};
                }
                subResult = subResult[key];
            }
        }
        return result;
    };
};
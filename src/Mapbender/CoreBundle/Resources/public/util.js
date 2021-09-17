window.Mapbender = Mapbender || {};

Mapbender.error = function(errorObject,delayTimeout) {
    var errorMessage = errorObject;
    if(typeof errorObject != "string"){
        errorMessage = JSON.stringify(errorObject);
    }
    $.notify(errorMessage,{autoHideDelay: delayTimeout?delayTimeout:5000}, 'error');
    console.error("Mapbender Error: ",errorObject);
};

Mapbender.info = function(infoObject,delayTimeout) {
    var message = infoObject;
    if(typeof infoObject != "string"){
        message = JSON.stringify(infoObject);
    }
    $.notify(message,{autoHideDelay: delayTimeout?delayTimeout:5000,className: 'info'});
    console.log("Mapbender Info: ",infoObject);
};
Mapbender.confirm = function(message){
    var res = confirm(message);
    return res;
};

Mapbender.checkTarget = function(widgetName, target, targetname) {
    if(target === null || typeof (target) === 'undefined'
            || new String(target).replace(/^\s+|\s+$/g, '') === ""
            || $('#' + target).length === 0) {
        Mapbender.error(widgetName + ': a target element ' + (targetname ? '"' + targetname + '"'
                : '') + ' is not defined.');
        return false;
    } else {
        return true;
    }
};

Mapbender.Util = Mapbender.Util || {};

Mapbender.Util.UUID = function(){
    var d = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c){
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == 'x' ? r : (r & 0x7 | 0x8)).toString(16);
    });
    return uuid;
};
/* deprecated */
Mapbender.urlParam = function(key){
    window.console && console.warn(
            'The function "Mapbender.urlParam" is deprecated, use instead it the "new Mapbender.Util.Url().getParameter(key)"');
    return new Mapbender.Util.Url(window.location.href).getParameter(key);
};

/* deprecated */
Mapbender.UUID = function(){
    window.console && console.warn(
            'The function "Mapbender.UUID" is deprecated, use instead it the "Mapbender.Util.UUID"');
    return Mapbender.Util.UUID();
}

/**
 * Creates an url object from a giving url string
 * @param {String} urlString
 */
Mapbender.Util.Url = function(urlString) {
    if(!urlString.replace(/^\s+|\s+$/g, ''))// trim
        return;
    var self = this;
    var tmp = document.createElement("a");
    tmp.href = urlString;
    this.protocol = tmp.protocol;
    this.username = decodeURIComponent(tmp.username || '') || null;
    this.password = decodeURIComponent(tmp.password || '') || null;
    this.host = tmp.host;
    this.hostname = tmp.hostname;
    this.port = tmp.port;
    // fix magically appearing explicit default port in some IE versions
    if (this.port) {
        // pattern: match until first single slash (ignore double slash between protocol and host),
        // check if the string preceding this first single slash is ":port"
        if (!(new RegExp('^([^/]+?//)?[^/]*?:' + this.port + '($|/)').test(urlString))) {
            this.port = '';
            // also fix .host, see IE notes at https://developer.mozilla.org/en-US/docs/Web/API/HTMLHyperlinkElementUtils/host
            this.host = this.host.replace(/:\d+$/, '');
        }
    }
    // No reliable .hostname support in IE, see e.g. https://stackoverflow.com/questions/10755943/ie-forgets-an-a-tags-hostname-after-changing-href
    if (this.host && !this.hostname) {
        // .hostname is same as .host minus port specification
        this.hostname = this.host.replace(/:\d+$/, '');
    }
    // https://stackoverflow.com/questions/956233/javascript-pathname-ie-quirk
    this.pathname = tmp.pathname.charAt(0) === '/' ? tmp.pathname : '/' + tmp.pathname;
    this.parameters = {};
    var rawParams = (tmp.search || '?').substr(1).split('&');
    for (var i = 0; i < rawParams.length; ++i) {
        var rawParam = rawParams[i];
        var eqAt = rawParam.indexOf('=');
        var paramName, paramValue;
        if (eqAt !== -1) {
            paramName = decodeURIComponent(rawParam.substr(0, eqAt));
            paramValue = decodeURIComponent(rawParam.substr(eqAt + 1));
        } else {
            paramName = rawParam;
            paramValue = '';
        }
        this.parameters[paramName] = paramValue;
    }
    this.hash = tmp.hash;
    /**
     * Checks if a url object is valid.
     * @returns {Boolean} true if url valid
     */
    this.isValid = function(){
        return  !(!self.hostname || !self.protocol);// TODO ?
    };
    /**
     * Reconstruct url
     * @param {boolean} [withoutUser] to omit credentials (default false)
     * @returns {String}
     */
    this.asString = function(withoutUser) {
        var parts = [this.protocol, '//'];
        if (!withoutUser && this.username) {
            parts.push(encodeURIComponent(this.username), ':', encodeURIComponent(this.password || ''), '@');
        }
        parts.push(this.hostname);
        if (this.port) {
            parts.push(':', this.port);
        }
        parts.push(this.pathname);
        var params = [];
        var paramKeys = Object.keys(this.parameters || {});
        for (var i = 0; i < paramKeys.length; ++i) {
            var key = paramKeys[i];
            var val = this.parameters[key];
            if (val) {
                params.push([encodeURIComponent(key), '=', encodeURIComponent(val)].join(''));
            } else {
                params.push(encodeURIComponent(key));
            }
        }
        if (params.length) {
            parts.push('?', params.join('&'));
        }
        parts.push(this.hash || '');
        return parts.join('');
    };
    /**
     * Gets a GET parameter value from a giving parameter name.
     * @param {String} name parameter name
     * @param {Boolean} [ignoreCase]
     * @returns {String|null}
     */
    this.getParameter = function(name, ignoreCase) {
        for(var key in self.parameters) {
            if(key === name || (ignoreCase && key.toLowerCase() === name.toLowerCase())) {
                return self.parameters[key];
            }
        }
        return null;
    };
};

/**
 * @param {String} x
 * @return {String}
 */
Mapbender.Util.escapeRegex = function(x) {
    // See https://stackoverflow.com/a/6969486
    return x.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
};

/**
 * Strips named url params from given url.
 * Only supports scalar ASCII param names.
 *
 * @param {String} url
 * @param {Array<String>} names
 * @param {boolean} [caseSensitive] default true
 * @return {String}
 */
Mapbender.Util.removeUrlParams = function(url, names, caseSensitive) {
    var ci_ = !caseSensitive && (typeof caseSensitive !== 'undefined');
    var qmAt = url.indexOf('?');
    if (qmAt === -1 || !names.length) {
        // nothing to do
        return url;
    }
    var hashAt = url.indexOf('#');
    if (hashAt !== -1) {
        return Mapbender.Util.removeUrlParams(url.substring(0, hashAt), names, caseSensitive) + url.substring(hashAt);
    }
    var base = url.substring(0, qmAt + 1);
    var queryPart = url.substring(qmAt + 1);
    var flags = (ci_ && 'gi') || 'g';
    for (var i = 0; i < names.length; ++i) {
        var name = names[i];
        if (decodeURIComponent(name) !== encodeURIComponent(name)) {
            console.warn("Fixme: Url parameter name contains url-encodable characters, results will be undefined", name, url);
        }
    }
    var patternParts = [
        '(&+|^)',
        '(', names.map(Mapbender.Util.escapeRegex).join('|'), ')',
        '([=][^&]*)?'
    ];
    var rx = new RegExp(patternParts.join(''), flags);
    var strippedQueryPart = queryPart.replace(rx, '').replace(/^&+/, '').replace(/&+$/, '');
    return [base, strippedQueryPart].join('');
};

/**
 * Appends params to query string of given URL.
 *
 * @param {String} url
 * @param {Object} params
 * @return {String}
 */
Mapbender.Util.addUrlParams = function(url, params) {
    var hashAt = url.indexOf('#');
    if (hashAt !== -1) {
        return Mapbender.Util.addUrlParams(url.substring(0, hashAt), params) + url.substring(hashAt);
    }
    var newParams = jQuery.param(params, false);
    var separator;
    if (!(/\?/).test(url)) {
        separator = '?';
    } else if ((/[?&]$/).test(url)) {
        separator = '';
    } else {
        separator = '&';
    }
    return [url, newParams].join(separator).replace(/&*$/, '');
};

/**
 * Appends params to query string of given URL.
 *
 * @param {String} url
 * @param {Object} params
 * @param {boolean} [caseSensitive] default true
 * @return {String}
 */
Mapbender.Util.replaceUrlParams = function(url, params, caseSensitive) {
    var hashAt = url.indexOf('#');
    if (hashAt !== -1) {
        return Mapbender.Util.replaceUrlParams(url.substring(0, hashAt), params, caseSensitive) + url.substring(hashAt);
    }
    var names = Object.keys(params);
    var stripped = Mapbender.Util.removeUrlParams(url, names, caseSensitive);
    var filteredParams = {};
    for (var i = 0; i < names.length; ++i) {
        var name = names[i];
        var value = params[name];
        // legacy fun time: allow pure removal by 'replacing' param with null.
        // This emulates behavior of OpenLayers.Util.getParameterString where null values are silently dropped
        if (value !== null) {
            filteredParams[name] = value;
        }
    }
    return Mapbender.Util.addUrlParams(stripped, filteredParams);
};

/**
 * @param {Object} a
 * @param {Object} b
 * @return {boolean}
 */
Mapbender.Util.extentsIntersect = function(a, b) {
    /**
     * Rectangle intersection logic lifted from OpenLayers 2
     * minus all its behavioural tweak options we do not need.
     * @see OpenLayers.Bounds.prototype.intersectsBounds
     */
    var inBottom = (
        ((a.bottom >= b.bottom) && (a.bottom <= b.top)) ||
        ((b.bottom >= a.bottom) && (b.bottom <= a.top))
    );
    var inTop = (
        ((a.top >= b.bottom) && (a.top <= b.top)) ||
        ((b.top > a.bottom) && (b.top < a.top))
    );
    var inLeft = (
        ((a.left >= b.left) && (a.left <= b.right)) ||
        ((b.left >= a.left) && (b.left <= a.right))
    );
    var inRight = (
        ((a.right >= b.left) && (a.right <= b.right)) ||
        ((b.right >= a.left) && (b.right <= a.right))
    );
    return ((inBottom || inTop) && (inLeft || inRight));
};

Mapbender.Util.isInScale = function(scale, min_scale, max_scale){
    return (min_scale ? min_scale <= scale : true) && (max_scale ? max_scale >= scale : true);
};

Mapbender.Util.isSameSchemeAndHost = function(urlA, urlB) {
    var a = document.createElement('a');
    var b = document.createElement('a');
    a.href = urlA;
    b.href = urlB;
    return a.host === b.host && a.protocol && b.protocol;
};

Mapbender.Util.addProxy = function(url) {
    return Mapbender.configuration.application.urls.proxy + '?url=' + encodeURIComponent(url);
};

Mapbender.Util.removeProxy = function(url) {
    var proxyBase = Mapbender.configuration.application.urls.proxy + '?url=';
    if (url.indexOf(proxyBase) === 0) {
        return decodeURIComponent(url.substring(proxyBase.length));
    }
    return url;
};

Mapbender.Util.removeSignature = function(url) {
    var pos = url.indexOf("_signature");
    if (pos !== -1) {
        var url_new = url.substring(0, pos);
        if (url_new.lastIndexOf('&') === url_new.length - 1) {
            url_new = url_new.substring(0, url_new.lastIndexOf('&'));
        }
        if (url_new.lastIndexOf('?') === url_new.length - 1) {
            url_new = url_new.substring(0, url_new.lastIndexOf('?'));
        }
        return url_new;
    }
    return url;
};


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

/**
 * ES5 Object.assign polyfill from MDN
 * Only required by IE <= 11, but IE needs to be >= 9 for this to work
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign#Polyfill
 * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/defineProperty#Browser_compatibility
 */
if (typeof Object.assign != 'function') {
  // Must be writable: true, enumerable: false, configurable: true
  Object.defineProperty(Object, "assign", {
    value: function assign(target, varArgs) { // .length of function is 2
      'use strict';
      if (target == null) { // TypeError if undefined or null
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var to = Object(target);

      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];

        if (nextSource != null) { // Skip over if undefined or null
          for (var nextKey in nextSource) {
            // Avoid bugs when hasOwnProperty is shadowed
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }
      return to;
    },
    writable: true,
    configurable: true
  });
}

if (typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g, '');
    }
}

// DataTables patch for prototype pollution and HTML escaping
//
// NOTE: This patch is only used because we cannot update DataTables to a safer version via composer.
// This workaround should be replaced as soon as possible by proper frontend package manager handling
// (e.g. npm/yarn) and an updated, secure DataTables version.
//
//
// ---
const ensureDataTablesPatched = () => {
  const dt = $.fn.dataTable;
  
  if (!dt.__pollutionFixApplied__) {
    const origSet = dt.util.set;
    dt.util.set = function safeSet(sourcePath) {
      const fn = origSet.call(this, sourcePath);
      return function (data, val, meta) {
        if (typeof sourcePath === 'string' &&
            /(?:__proto__|constructor)/.test(sourcePath)) {
          throw new Error('Blocked prototype-pollution attempt');
        }
        return fn.call(this, data, val, meta);
      };
    };
    dt.__pollutionFixApplied__ = true;
  }

  if (!dt.__escapeFixApplied__) {
    const encode = (s) => String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    dt.util.escapeHtml = function escapeSafe(input) {
      return Array.isArray(input)
        ? input.map(encode).join('')
        : encode(input);
    };
    dt.__escapeFixApplied__ = true;
  }
};

$(document).ready(() => {
  console.log("Execute DataTables patch");
  ensureDataTablesPatched();
});


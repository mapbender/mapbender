window.Mapbender = Mapbender || {};
window.Mapbender.GeolocationMock = (function() {
    function buildCoordsSequence() {
        var coordsSequence = [];
        // generate a sequence of points "moving" along a circle centered somewhere in Potsdam
        var center = [13.0581, 52.4];
        var radius = 0.0015;
        var angle = 0;
        while (angle < 2 * Math.PI) {
            // see https://developer.mozilla.org/en-US/docs/Web/API/GeolocationCoordinates
            // fluctuate fake confidence radius randomly between 20 and 500 meters
            var fakeAccuracy = 50. + (500. - 20.) * Math.pow(Math.random(), 2);
            var fakeCoord = {
                longitude: center[0] + radius * Math.cos(angle),
                latitude: center[1] + radius * Math.sin(angle),
                altitude: null,
                accuracy: fakeAccuracy,
                altitudeAccuracy: null,
                heading: 0.0,
                speed: null
            };
            coordsSequence.push(fakeCoord);
            angle += Math.PI / 10.25;
        }
        return coordsSequence;
    }
    var data_ = {
        coordsSequence: buildCoordsSequence(),
        coordsIndex: 0,
        getCoord: function() {
            return {
                coords: this.coordsSequence[this.coordsIndex],
                timestamp: (new Date()).getTime()
            };
        },
        advance: function() {
            ++this.coordsIndex;
            if (this.coordsIndex >= this.coordsSequence.length) {
                this.coordsIndex = 0;
            }
        }
    };
    return {
        watcherCount_: 0,
        watcherMap_ : {},
        cachedLast_: null,
        getCurrentPosition: function(success, error, options) {
            var maxAge, timeout;
            if (!options || (typeof (options.maximumAge) === 'undefined')) {
                maxAge = 0;
            } else {
                maxAge = options.maximumAge;
            }
            if (!options || (typeof (options.timeout) === 'undefined')) {
                timeout = Infinity;
            } else {
                timeout = options.timeout;
            }
            if (maxAge > 0 && this.cachedLast_ && maxAge > ((new Date()).getTime() - this.cachedLast_.timestamp)) {
                success(this.cachedLast_);
            } else {
                this._getCurrentDelayed(success, error, timeout);
            }
        },
        watchPosition: function(success, error, options) {
            ++this.watcherCount_;
            var watcherId = (++this.watcherCount_).toString();
            this.watcherMap_[watcherId] = {
                success: success,
                error: error,
                options: options
            };
            this.continueWatch_(watcherId);
            return watcherId;
        },
        clearWatch: function(watcherId) {
            delete this.watcherMap_[watcherId];
        },
        continueWatch_: function(watcherId) {
            var self = this;
            var entry = this.watcherMap_[watcherId];
            if (typeof entry !== 'undefined') {
                this.getCurrentPosition(function(success) {
                    (entry.success)(success);
                    self.continueWatch_(watcherId);
                }, function(error) {
                    (entry.error)(error);
                    self.continueWatch_(watcherId);
                }, entry.options);
            }
        },
        _getCurrentDelayed: function(success, error, timeout) {
            var responseDelay = Math.floor(5 + 1000 * Math.pow(Math.random(), 3));

            var timeoutCallback;
            if (responseDelay >= timeout) {
                timeoutCallback = function() {
                    error({
                        code: GeolocationPositionError.TIMEOUT,
                        message: 'Timeout'
                    });
                };
            } else {
                var self = this;
                timeoutCallback = function() {
                    var coord = data_.getCoord();
                    self.cachedLast_ = coord;
                    data_.advance();
                    success(coord);
                };
            }
            window.setTimeout(timeoutCallback, responseDelay);
        }
    }
}());


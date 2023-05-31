var Mapbender = Mapbender || {};

Mapbender.Dimension = function(options) {
    if (options.name === 'time') {
        return new Mapbender.DimensionTime(options);
    } else if(options.type === 'interval') {
        return new Mapbender.DimensionScalar(options);
    } else if(options.type === 'multiple') {
        return new Mapbender.DimensionScalar(options); // Add MultipleScalar ???
    } else {
        return null;
    }
};

Mapbender.DimensionScalar = function(options) {
    this.default = null;

    if(Object.prototype.toString.call(options.extent) !== '[object Array]') {
        throw 'DimensionScalar extent option has to be type [object Array]:' + Object.prototype.toString.call(options.extent) + 'given'
    }

    if (options.extent.length < 2) {
        throw 'DimensionScalar extent option needs atleast two entries'
    }

    this.options = options;
};

Mapbender.DimensionScalar.Types = {
    INTERVAL: 'interval',
    MULTIPLE: 'multiple'
};

Mapbender.DimensionScalar.prototype.getOptions = function() {
    return this.options;
};

Mapbender.DimensionScalar.prototype.getDefault = function() {
    return this.default;
};

Mapbender.DimensionScalar.prototype.setDefault = function(defaults) {
    return this.default = defaults;
};

Mapbender.DimensionScalar.prototype.getMin = function() {
    return this.options.extent[0];
};

Mapbender.DimensionScalar.prototype.getMax = function() {
    return this.options.extent[1];
};

Mapbender.DimensionScalar.prototype.getResolutionText = function() {
    return this.options.extent[2];
};

/**
 * @returns {int}
 */
Mapbender.DimensionScalar.prototype.getStep = function(value) {
    switch(this.options.type){
        case Mapbender.DimensionScalar.Types.INTERVAL:
            return Math.round(Math.abs(value - this.options.extent[0]) / this.options.extent[2]);
        case Mapbender.DimensionScalar.Types.MULTIPLE:
            return this.options.extent.indexOf(value);
    }
};

Mapbender.DimensionScalar.prototype.getStepsNum = function() {
    if (typeof this.stepsNum === 'undefined') {
        this.stepsNum = this.getStep(this.options.extent[1]);
    }
    return this.stepsNum;
};

Mapbender.DimensionScalar.prototype.valueFromStep = function(step) {
    return this.options.extent[step];
};

Mapbender.DimensionScalar.prototype.innerJoin = function() {
    console.warn('DimensionScalar.innerJoin() is not implemented yet!');
    return null;
};

Mapbender.DimensionScalar.prototype.getInRange = function(min, max, value) {
    var minStep = this.getStep(min);
    var maxStep = this.getStep(max);
    var valueStep = this.getStep(value);
    var step = Math.max(minStep, Math.min(valueStep, maxStep));
    return this.valueFromStep(step);
};

Mapbender.DimensionTime = function DimensionTime(options) {
    if (options.extent) {
        options.extent = options.extent.map(function(x) {
            return '' + x;
        });
    }
    Mapbender.DimensionScalar.call(this, options);
    try {
        this.template = new Mapbender.DimensionTime.DateTemplate(options.extent[0]);
    } catch (e) {
        this.template = new Mapbender.DimensionTime.DateTemplate(options.extent[1]);
    }
    this.start = new Date(options.extent[0]);
    this.end = new Date(options.extent[1]);
    var invalidDate = new Date('invalid');
    if (this.start.toString() === invalidDate.toString()) {
        throw new Error("Invalid start date input " + options.extent[0]);
    }
    if (this.end.toString() === invalidDate.toString()) {
        throw new Error("Invalid end date input" + options.extent[1]);
    }
    this.step = new PeriodISO8601(options.extent[2]);
    if (this.start > this.end) {
        var swapTmp = this.end;
        this.end = this.start;
        this.start = swapTmp;
    }
    this.setDefault(this.getInRange(this.valueFromStep(0), this.valueFromStep(this.getStepsNum()), this.options['default'] || this.getMax()));
};

Mapbender.DimensionTime.prototype = Object.create(Mapbender.DimensionScalar.prototype);
Mapbender.DimensionTime.prototype.constructor = Mapbender.DimensionTime;

Mapbender.DimensionTime.prototype.getStep = function(value) {
    var valueDate = new Date([value].join('')); // Force string input to Date
    switch (this.step.getType()) {
        case 'year':
        case 'month':
            var years = valueDate.getUTCFullYear() - this.start.getUTCFullYear();
            var months = valueDate.getUTCMonth() - this.start.getUTCMonth();
            return Math.floor((12 * years + months) / (12 * this.step.years + this.step.months));
        case 'date':
            var step = -1;
            var startFormatted = this.template.formatDate(this.start);
            do {
                ++step;
                valueDate.setFullYear(valueDate.getFullYear() - this.step.years);
                valueDate.setMonth(valueDate.getMonth() - this.step.months);
                valueDate.setDate(valueDate.getDate() - this.step.days);
            } while (this.template.formatDate(valueDate) >= startFormatted);
            return step;
        case 'msec':
            return Math.floor((valueDate - this.start) / this.step.asMsec());
        default:
            throw new Error("Unsupported DimensionTime type " + this.step.getType());
    }
};

Mapbender.DimensionTime.prototype.valueFromStep = function(step) {
    var dateOut = new Date(this.start.toISOString());
    switch (this.step.getType()) {
        case 'year':
        case 'month':
        case 'date':
            dateOut.setUTCFullYear(dateOut.getUTCFullYear() + step * this.step.years);
            dateOut.setUTCMonth(dateOut.getUTCMonth() + step * this.step.months);
            dateOut.setUTCDate(dateOut.getUTCDate() + step * this.step.days);
            break;
        case 'msec':
            dateOut.setTime(dateOut.getTime() + step * this.step.asMsec());
            break;
        default:
            throw new Error("Unsupported step type " + this.step.getType());
    }
    return this.template.formatDate(dateOut);
};

/**
 * @param {Mapbender.DimensionTime} another
 * @return {null}
 */
Mapbender.DimensionTime.prototype.innerJoin = function innerJoin(another) {
    if (!this.step.equals(another.step)) {
        return null;
    }
    var options = $.extend(true, {}, this.options);
    var startDimension, endDimension;
    if (this.start.getTime() >= another.start.getTime()) {
        startDimension = this;
    } else {
        startDimension = another;
    }
    if(this.end.getTime() >= another.end.getTime()) {
        endDimension = another;
    } else {
        endDimension = this;
    }
    var startStep = endDimension.getStep(this.template.formatDate(startDimension.start));
    if (endDimension.valueFromStep(startStep) !== this.template.formatDate(startDimension.start)) {
        console.warn("Dimension join failure", this, another);
        return null;
    }

    options.extent = [
        this.template.formatDate(startDimension.start),
        this.template.formatDate(endDimension.end),
        this.step.toString()
    ];
    options.current = this.options.current === another.options.current ? this.options.current : null;
    options.multipleValues = this.options.multipleValues && another.options.multipleValues;
    options.nearestValue = this.options.nearestValue && another.options.nearestValue;
    return Mapbender.Dimension(options);
};

Mapbender.DimensionTime.DateTemplate = function(value) {
    // For a nice summary of possible format variants, see
    // https://mapserver.org/ogc/wms_time.html#time-patterns
    var dateTimeStr = '' + value;
    if(dateTimeStr.indexOf('-') === 0) {
        var date = new Date(value);
        console.warn("Ambiguous vcard date format, truncating ambiguously", dateTimeStr);
        dateTimeStr = date.toISOString().replace(/([-T:]00)*\.000(Z?)$/, '');
    }
    var dateTimeParts = dateTimeStr.split(/[T ]/, 2);

    var dateString = dateTimeParts[0];
    var timeString = dateTimeParts[1];
    if (dateString.indexOf(':') !== -1 || (!timeString && dateString.length <= 3)) {
        // Time only, no date
        dateString = '';
        timeString = dateTimeParts[0];
    }
    if (dateString.length) {
        // Could be "T" or a single space
        this.timePrefix = dateTimeStr.charAt((dateString || '').length);
    } else {
        // Pure time (no date)
        // Prefix is either "T" or empty (NOT a space)
        this.timePrefix = dateTimeStr.charAt(0) === 'T' && 'T' || '';
    }
    switch (true) {
        default:
            throw new Error("Invalid date template input " + value);
        case '' === dateString:
            this.ymd  = [false, false, false];
            this.dateSeparator = '';
            break;
        case /^\d{4}$/.test(dateString):
            // Year only, separator irrelevant
            this.ymd = [true, false, false];
            this.dateSeparator = '';
            break;
        case /^\d{4}-\d{2}$/.test(dateString):
            // YYYY-MM with dash separator
            this.ymd = [true, true, false];
            this.dateSeparator = '-';
            break;
        case /^\d{6}$/.test(dateString):
            // Gapless YYYYMM
            this.ymd = [true, true, false];
            this.dateSeparator = '';
            break;
        case /^\d{4}-\d{2}-\d{2}$/.test(dateString):
            // YYYY-MM-DD with dash separator
            this.ymd = [true, true, true];
            this.dateSeparator = '-';
            break;
        case /^\d{8}$/.test(dateString):
            // Gapless YYYYMMDD
            this.ymd = [true, true, true];
            this.dateSeparator = '';
            break;
    }

    this.hmsm = (timeString || '').replace(/Z$/, '').split(':').map(function(part) {
        return part && !isNaN(parseInt(part)) || false;
    });
    if ((timeString || '').indexOf('.') !== -1) {
        this.hmsm[2] = true;
        this.hmsm[3] = true;
    }
    var truths = this.ymd.concat(this.hmsm).filter(function(x) {
        return !!x;
    });
    if (!truths.length) {
        throw new Error("Invalid date template input " + value);
    }
    if (timeString && /Z$/.test(value)) {
        this.timeSuffix = 'Z';
    }
};

Object.assign(Mapbender.DimensionTime.DateTemplate.prototype, {
    formatDate: function (date) {
        var value = date.toISOString().replace(/Z$/, '');
        if (!this.hmsm[0]) {
            // strip time portion entirely
            value = value.replace(/T.*$/, '');
        } else if (!this.hmsm[1]) {
            // strip minutes, seconds and milliseconds
            value = value.replace(/(T\d\d)(.*)$/, '$1');
        } else if (!this.hmsm[2]) {
            // strip seconds and milliseconds
            value = value.replace(/(T\d\d:\d\d)(.*)$/, '$1');
        } else if (!this.hmsm[3]) {
            // strip milliseconds
            value = value.replace(/(T\d\d:\d\d:\d\d)(.*)$/, '$1');
        }
        if (this.hmsm[0] && this.timeSuffix) {
            value = [value, this.timeSuffix].join('');
        }
        if (!this.ymd[0]) {
            // strip date portion entirely
            value = value.replace(/^[^T]*/, '');
        } else if (!this.ymd[1]) {
            // strip month and day, keep time portion
            value = value.replace(/^(\d\d\d\d)([^T]*)(.*)$/, '$1$3');
        } else if (!this.ymd[2]) {
            // strip day, keep time portion
            value = value.replace(/^(\d\d\d\d-\d\d)([^T]*)(.*)$/, '$1$3');
        }
        // Replace "T" with time prefix
        value = value.replace(/T/, this.timePrefix);
        // Replace "-" with date separator ("-" or empty string)
        value = value.replace(/-/g, this.dateSeparator);
        return value;
    }
});

PeriodISO8601 = function(datetimeStr) {
    var pattern = /^(?:P)(?:(\d+)(?:Y))?(?:(\d+)(?:M))?(?:(\d+)(?:D))?(?:T(?:(\d+)(?:H))?(?:(\d+)(?:M))?(?:(\d+)(?:S))?)?$/;
    if (!datetimeStr.match(pattern)) {
        throw new Error("Invalid duration input " + datetimeStr);
    }
    var parts = datetimeStr.split(pattern).map(function(part) {
        return parseInt(part) || 0;
    });
    this.years = parts[1];
    this.months = parts[2];
    this.days = parts[3];
    this.hours = parts[4];
    this.mins = parts[5];
    this.secs = parts[6];
};

Object.assign(PeriodISO8601.prototype, {
    getType: function() {
        if (!this.years && !this.months) {
            return 'msec';
        } else {
            if (!this.days && !this.hours && !this.mins && !this.secs) {
                if (this.years && !this.months) {
                    return 'year';
                } else {
                    return 'month';
                }
            } else {
                return 'date';
            }
        }
    },
    toString: function() {
        var time = this.hours > 0 ? this.hours + 'H' : '';
        time += this.mins > 0 ? this.mins + 'M' : '';
        time += this.secs > 0 ? this.secs + 'S' : '';
        time = time.length > 0 ? 'T' + time : '';
        var date = this.years > 0 ? this.years + 'Y' : '';
        date += this.months > 0 ? this.months + 'M' : '';
        date += this.days > 0 ? this.days + 'D' : '';
        return (date.length + time.length) > 0 ? 'P' + (date + time) : '';
    },
    equals: function(period) {
        return this.years === period.years && this.months === period.months && this.days === period.days && this.hours === period.hours && this.mins === period.mins && this.secs === period.secs;
    },
    asMsec: function() {
        return 1000 * (
            (this.secs || 0)
            + (this.mins || 0) * 60
            + (this.hours || 0) * 3600
            + (this.days || 0) * 86400
        );
    }
});

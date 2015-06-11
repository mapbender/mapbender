var Mapbender = Mapbender || {};
//Mapbender.IDimension = Interface({
//    'public getOptions': function(){},
//    'public getDefault': function(){},
//    'public setDefault': function(val){},
//    'public getStepsNum': function(){},
//    'public partFromValue': function(val){},
//    'public stepFromPart': function(part){},
//    'public stepFromValue': function(val){},
//    'public valueFromPart': function(part){},
//    'public valueFromStart': function(){},
//    'public valueFromEnd': function(){},
//    'public innerJoin': function(another){},
//    'public getInRange': function(min, max, value){}
//});
Mapbender.Dimension = function (options) {
    if (options.type === 'interval' && options.name === 'time') {
        return new Mapbender.DimensionTime(options);
    } else if (options.type === 'interval') {
        return new Mapbender.DimensionScalar(options);
    } else if (options.type === 'multiple') {
        return new Mapbender.DimensionScalar(options); // Add MultipleScalar ???
    } else {
        return null;
    }
};
Mapbender.DimensionScalar = Class({//{implements: Mapbender.IDimension}, {
    'public object options': {},
    'public default_': null,
    'public number stepsNum': -1,
    __construct: function (options, initDefault) {
            this.options = options;
        if (initDefault) {
            this.setValue(this.getInRange(this.valueFromPart(0), this.valueFromPart(1),
                    this.options['default'] === null ? options.extent[0] : options['default']));
        }
    },
    'public getOptions': function () {
        return this.options;
    },
    'public getDefault': function () {
        return this.default_;
    },
    'public setDefault': function (val) {
        this.default_ = val;
    },
    'public getStepsNum': function () {
        if (this.stepsNum !== -1) {
            return this.stepsNum;
        } else if (this.options.type === 'interval') {
            return Math.round(Math.abs(this.options.extent[1] - this.options.extent[0]) / this.options.extent[2]);
        } else if (this.options.type === 'multiple') {
            return this.options.extent.length;
        }
    },
    'public partFromValue': function (val) {
        if (this.options.type === 'interval') {
            return Math.abs(val - this.options.extent[0]) / Math.abs(this.options.extent[1] - this.options.extent[0]);
        } else if (this.options.type === 'multiple') {
            for (var i = 0; i < this.options.extent.length; i++) {
                if (val === this.options.extent[i]) {
                    return i / (this.getStepsNum() + 1);
                }
            }
            return 0;
        }
    },
    'public stepFromPart': function (part) {
        return Math.round(part * (this.getStepsNum()));
    },
    'public stepFromValue': function (val) {
        return this.stepFromPart(this.partFromValue(val));
    },
    'public valueFromPart': function (part) {
        var step = this.stepFromPart(part);
        return this.options.extent[step];
    },
    'public valueFromStart': function () {
        return this.options.extent[0];
    },
    'public valueFromEnd': function () {
        return this.options.extent[this.options.extent.length - 1];
    },
    'public innerJoin': function (another) {
        if (this.asc !== another.asc) {
            return null;
        }
        //TODO
    },
    'public getInRange': function (min, max, value) {
        var partMin = this.partFromValue(min);
        var partMax = this.partFromValue(max);
        if (partMin < 0 || partMax > 1) {
            return null;
        }
        var partValue = this.partFromValue(value);
        return this.valueFromPart(partValue <= partMin ? partMin : partValue >= partMax ? partMax : partValue);
    }
});

Mapbender.DimensionFormat = function (value, numDig) {
    var d = numDig - ('' + value).length;
    while (d > 0) {
        value = '0' + value;
        d--;
    }
    return value;
};
Mapbender.DimensionTime = Class({'extends': Mapbender.DimensionScalar},{//,implements: Mapbender.IDimension}, {
    'private object start': null,
    'private object end': null,
    'private object step': null,
    'private boolean asc': true,
    __construct: function (options) {
        options.extent[0] = '' + options.extent[0];
        options.extent[1] = '' + options.extent[1];
        this['super']('__construct', options, false);
        this.start = new TimeISO8601(options.extent[0]);
        this.end = new TimeISO8601(options.extent[1]);
        this.step = new PeriodISO8601(options.extent[2]);
        this.asc = this.end.getTime().getTime() > this.start.getTime().getTime();
        this.setDefault(this.getInRange(this.valueFromPart(0), this.valueFromPart(1),
                this.options['default'] === null ? options.extent[0] : options['default']));
    },
    'public getStepsNum': function () {
        if (this.stepsNum !== -1) {
            return this.stepsNum;
        } else {
            if (this.step.getType() === 'year') {
                this.stepsNum = Math.floor(Math.abs(this.end.getYear() - this.start.getYear()) / this.step.getYears());
            } else if (this.step.getType() === 'msec') {
                var stepTst = this.step.asMsec();
                this.stepsNum = Math.floor(Math.abs(this.end.getTime().getTime() - this.start.getTime().getTime()) / stepTst);
            } else if (this.step.getType() === 'month') {
                var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
                var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
                var stepMonth = (this.step.getYears() * 12 + this.step.getMonths());
                this.stepsNum = Math.floor(Math.abs(endMonth - startMonth) / stepMonth);
            } else if (this.step.getType() === 'date') {
                /* TODO optimize? */
                var stepTime = new TimeISO8601(this.start.getTime().toJSON());
                this.stepsNum = 0;
                var endtime = this.end.getTime().getTime();
                if (this.asc) {
                    while (stepTime.time.getTime() <= endtime) {
                        this.stepsNum++;
                        stepTime.add(this.step);
                    }
                } else {
                    while (stepTime.time.getTime() >= endtime) {
                        this.stepsNum++;
                        stepTime.substract(this.step);
                    }
                }
            }
            return this.stepsNum;
        }
    },
    'public partFromValue': function (isoDate) {
        var givenTime = new TimeISO8601(isoDate);
        if (this.step.getType() === 'year') {
            var part = (givenTime.getYear() - this.start.getYear()) / (this.end.getYear() - this.start.getYear());
            return part;
        } else if (this.step.getType() === 'msec') {
            var part = (givenTime.time.getTime() - this.start.getTime().getTime()) / (this.end.getTime().getTime() - this.start.getTime().getTime());
            return part;
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
            var timeMonth = (givenTime.getYear() * 12 + givenTime.getMonth());
            var part = (timeMonth - startMonth) / (endMonth - startMonth);
            return part;
        } else if (this.step.getType() === 'date') {
            /* TODO optimize? */
            var stepTime = new TimeISO8601(this.start.getTime().toJSON());
            var stepsNum = 0;
            var endtime = givenTime.time.getTime();
            if (this.asc) {
                while (stepTime.time.getTime() <= endtime) {
                    stepsNum++;
                    stepTime.add(this.step);
                }
            } else {
                while (stepTime.time.getTime() >= endtime) {
                    stepsNum++;
                    stepTime.substract(this.step);
                }
            }
            return stepsNum / this.stepsNum;
        }
    },
    stepFromPart: function (part) {
//        if (this.step.getType() === 'msec') {
//            return Math.round(part * (this.getStepsNum()));
//        } else if (this.step.getType() === 'month') {
//            return Math.round(part * (this.getStepsNum()));
//        } else if (this.step.getType() === 'date') {
            return Math.round(part * (this.getStepsNum()));/* ??? */
//        }
    },
    stepFromValue: function (value) {
//        if (this.step.getType() === 'msec') {
//            return this.stepFromPart(this.partFromValue(value));
//        } else if (this.step.getType() === 'month') {
//            return this.stepFromPart(this.partFromValue(value));
//        } else if (this.step.getType() === 'date') {
            return this.stepFromPart(this.partFromValue(value));/* ??? */
//        }
    },
    valueFromPart: function (part) {
        var step = this.stepFromPart(part);
        var time;
        if (this.step.getType() === 'year') {
            var years;
            if (this.asc) {
                years = this.start.getYear() + step * this.step.getYears();
            } else {
                years = this.start.getYear() - step * this.step.getYears();
            }
            time = new TimeISO8601(Mapbender.DimensionFormat('' + years, 4));
            return time.toString();
        } else if (this.step.getType() === 'msec') {
            var stepTst = this.step.asMsec();//msecs
            if (this.asc) {
                time = new TimeISO8601(new Date(this.start.getTime().getTime() + step * stepTst));
            } else {
                time = new TimeISO8601(new Date(this.start.getTime().getTime() - step * stepTst));
            }
            return time.toString();
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var stepMonth = (this.step.getYears() * 12 + this.step.getMonths());
            var months;
            if (this.asc) {
                months = startMonth + step * stepMonth;
            } else {
                months = startMonth - step * stepMonth;
            }
            var years = Math.floor(months / 12);
            time = new TimeISO8601(Mapbender.DimensionFormat(years, 4) + "-" + Mapbender.DimensionFormat(months - years * 12 + 1, 2));
            return time.toString();
        } else if (this.step.getType() === 'date') {
            /* TODO optimize? */
            var tempStep = 0;
            var stepTime = new TimeISO8601(this.start.getTime().toJSON());
            if (this.asc) {
                while (tempStep !== step) {
                    stepTime.add(this.step);
                    tempStep++;
                }
            } else {
                while (tempStep !== step) {
                    stepTime.substract(this.step);
                    tempStep++;
                }
            }
            return stepTime.toString();
        }
    },
    valueFromStart: function () {
        return this.start.getTime().toJSON();
    },
    valueFromEnd: function () {
        return this.end.getTime().toJSON();
    },
    intervalAsMonth: function (absolut) {
        if (this.step.getType() === 'month') {
            var startMonth = this.start.getYear() * 12 + this.start.getMonth();
            var endMonth = this.end.getYear() * 12 + this.end.getMonth();
            return absolut ? Math.abs(endMonth - startMonth) : endMonth - startMonth;
        }
    },
    innerJoin: function (another) {
        if (this.asc !== another.asc || !this.step.equals(another.step)) {
            return null;
        }
        var start, end;
        var options = $.extend(true, {}, this.options);
        function joinOptions(opts, one, two, startStr, endStr) {
            opts.extent = [startStr, endStr, one.step.toString()];
            opts.origextent = opts.extent;
            opts.current = one.options.current === two.options.current ? one.options.current : null;
            opts.multipleValues = one.options.multipleValues === two.options.multipleValues ? one.options.multipleValues : false;
            opts.nearestValue = one.options.nearestValue === two.options.nearestValue ? one.options.nearestValue : false;
            opts.unitSymbol = one.options.unitSymbol === two.options.unitSymbol ? one.options.unitSymbol : null;
            opts.units = one.options.units === two.options.units ? one.options.units : null;
            return opts;
        }
        if (this.step.getType() === 'year') {
            var testMin = Math.abs(this.start.getYear() - another.start.getYear()) / this.step.getYears();
            var testMax = Math.abs(this.end.getYear() - another.end.getYear()) / this.step.getYears();
            if (testMin !== parseInt(testMin) || testMax !== parseInt(testMax)) {
                return null;
            }
            if (this.asc) {
                start = this.start.getYear() >= another.start.getYear() ? this.start : another.start;
                end = this.end.getYear() <= another.end.getYear() ? this.end : another.end;
            } else {
                start = this.start.getYear() <= another.start.getYear() ? this.start : another.start;
                end = this.end.getYear() >= another.end.getYear() ? this.end : another.end;
            }
            options = joinOptions(options, this, another, start.toString(), end.toString());
            var joined = Mapbender.Dimension(options);
            return joined;
        } else if (this.step.getType() === 'msec') {
            var testMin = Math.abs(this.start.getTime().getTime() - another.start.getTime().getTime()) / this.step.asMsec();
            var testMax = Math.abs(this.end.getTime().getTime() - another.end.getTime().getTime()) / this.step.asMsec();
            if (testMin !== parseInt(testMin) || testMax !== parseInt(testMax)) {
                return null;
            }
            if (this.asc) {
                start = this.start.getTime().getTime() >= another.start.getTime().getTime() ? this.start : another.start;
                end = this.end.getTime().getTime() <= another.end.getTime().getTime() ? this.end : another.end;
            } else {
                start = this.start.getTime().getTime() <= another.start.getTime().getTime() ? this.start : another.start;
                end = this.end.getTime().getTime() >= another.end.getTime().getTime() ? this.end : another.end;
            }
            options = joinOptions(options, this, another, start.toString(), end.toString());
            var joined = Mapbender.Dimension(options);
            return joined;
        } else if (this.step.getType() === 'month') {
            var thisStartMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var anotherStartMonth = (another.start.getYear() * 12 + another.start.getMonth());
            var thisEndMonth = (this.end.getYear() * 12 + this.end.getMonth());
            var anotherEndMonth = (another.end.getYear() * 12 + another.end.getMonth());
            var stepMonth = (this.step.getYears() * 12 + this.step.getMonths());

            var testMin = Math.abs(thisStartMonth - anotherStartMonth) / stepMonth;
            var testMax = Math.abs(thisEndMonth - anotherEndMonth) / stepMonth;
            if (testMin !== parseInt(testMin) || testMax !== parseInt(testMax)) {
                return null;
            }

            if (this.asc) {
                start = this.start.getTime().getTime() >= another.start.getTime().getTime() ? this.start : another.start;
                end = this.end.getTime().getTime() <= another.end.getTime().getTime() ? this.end : another.end;
            } else {
                start = this.start.getTime().getTime() <= another.start.getTime().getTime() ? this.start : another.start;
                end = this.end.getTime().getTime() >= another.end.getTime().getTime() ? this.end : another.end;
            }
            options = joinOptions(options, this, another, start.toString(), end.toString());
            var joined = Mapbender.Dimension(options);
            return joined;
        } else if (this.step.getType() === 'date') {
            var joinStart, joinEnd;
            if (this.asc) {
                if (this.start.getTime().getTime() >= another.start.getTime().getTime()) {
                    joinStart = this.start;
                    start = another.start;
                } else {
                    joinStart = another.start;
                    start = this.start;
                }
                if (this.end.getTime().getTime() >= another.end.getTime().getTime()) {
                    joinEnd = another.end;
                    end = this.end;
                } else {
                    joinEnd = this.end;
                    end = another.end;
                }
            } else {
                if (this.start.getTime().getTime() >= another.start.getTime().getTime()) {
                    joinStart = another.start;
                    start = this.start;
                } else {
                    joinStart = this.start;
                    start = another.start;
                }
                if (this.end.getTime().getTime() >= another.end.getTime().getTime()) {
                    joinEnd = this.end;
                    end = another.end;
                } else {
                    joinEnd = another.end;
                    end = this.end;
                }
            }
//            var endtime = end.time.getTime();
            var joinStartTime = joinStart.time.getTime();
            var joinEndTime = joinEnd.time.getTime();
            var stepTime = start;
            var startOk = false;
            var endOk = false;
            var stepSt;
            var stepsNum = 0;
            while (true) {
                stepSt = stepTime.time.getTime();
                if (stepSt === joinStartTime) {
                    startOk = true;
                }
                if (stepSt === joinEndTime) {
                    endOk = true;
                }
                if (this.asc) {
                    if (stepSt >= joinEndTime || (startOk === true && endOk === true)) {
                        break;
                    }
                    stepTime.add(this.step);
                } else {
                    if (stepSt <= joinEndTime || (startOk === true && endOk === true)) {
                        break;
                    }
                    stepTime.substract(this.step);
                }
                stepsNum++;
            }
            if (startOk !== true || endOk !== true || stepsNum === 0) {
                return null;
            }
            options = joinOptions(options, this, another, start.toString(), end.toString());
            var joined = Mapbender.Dimension(options);
            return joined;
        }
    }
});

TimeStr = Class({
    'public string years': null,
    'public string months': null,
    'public string days': null,
    'public string hours': null,
    'public string mins': null,
    'public string secs': null,
    'public string msecs': null,
    'public boolean vC': false,
    'public boolean utc': false,
    __construct: function (datetimeStr) {
        var datetimeStr_ = '' + datetimeStr;
        this.years = null;
        this.months = null;
        this.days = null;
        this.hours = null;
        this.mins = null;
        this.secs = null;
        this.msecs = null;
        var dtStr;
        if (datetimeStr_.indexOf('-') === 0) {
            this.vC = true;
            dtStr = datetimeStr_.substr(1);
        } else {
            dtStr = datetimeStr_;
        }
        this.utc = datetimeStr_.indexOf('Z') !== -1;
        var help = dtStr.split('T');
        var ymd = [];
        var hmsm = [];
        if (help.length === 2) {
            ymd = help[0].split('-');
            hmsm = help[1].split(':');
        } else if (help[0].indexOf('-') !== -1) {
            ymd = help[0].split('-');
        } else if (help[0].indexOf(':') !== -1) {
            hmsm = help[0].split(':');
        } else if (help.length === 1) {
            ymd = help;
        }
        try {
            this.years = ymd[0];
        } catch (e) {
        }
        try {
            this.months = ymd[1];
        } catch (e) {
        }
        try {
            this.days = ymd[2];
        } catch (e) {
        }

        try {
            this.hours = hmsm[0];
        } catch (e) {
        }
        try {
            this.mins = hmsm[1];
        } catch (e) {
        }
        try {
            var hmsmHelp = hmsm[2].split('.');
            this.secs = hmsmHelp[0];
            this.msecs = hmsmHelp[1];
        } catch (e) {
        }
        try {
            this.msecs = this.msecs.replace('Z', '');
        } catch (e) {
        }
    },
    toISOString: function () {
        var res = (this.vC ? "-" : "") + this.years;
        res += "-" + (this.months ? this.months : "01");
        res += "-" + (this.days ? this.days : "01") + "T";
        res += (this.hours ? this.hours : "00");
        res += ":" + (this.mins ? this.mins : "00");
        res += ":" + (this.secs ? this.secs : "00");
        res += "." + (this.msecs ? this.msecs : "000") + (this.utc ? "Z" : "");
        return res;
    }
});

TimeISO8601 = Class({
    'public boolean utc': false,
    'public object time': null,
    'public object timeStr': null,
    __construct: function (date) {
        function convertDateFromISO(s) {
            s = s.split(/\D/);
            return new Date(Date.UTC(s[0], --s[1] || '', s[2] || '', s[3] || '', s[4] || '', s[5] || '', s[6] || ''))
        }
        if (typeof date === "object") {
            this.time = date;
            this.timeStr = new TimeStr(this.time.toJSON());
            this.utc = this.timeStr.isUtc();
        } else if (date === 'current') {
            this.time = new Date();
            this.timeStr = new TimeStr(this.time.toJSON());
            this.utc = this.timeStr.isUtc();
        } else if (!isNaN(new Date(date).getFullYear())) {
            this.timeStr = new TimeStr(date);
            this.utc = this.timeStr.isUtc();
            this.time = new Date(date);
            var a = 0;
        } else if (typeof date === 'string') {
            this.timeStr = new TimeStr(new TimeStr(date).toISOString());
            this.utc = this.timeStr.isUtc();
            if (date.indexOf('-') === 0) { // vC.
                this.timeStr = new TimeStr(new TimeStr(date).toISOString());
                this.utc = this.timeStr.isUtc();
                this.time = new Date(date);
                var d = convertDateFromISO(this.timeStr.toISOString());
                this.time = new Date(-d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds(), d.getMilliseconds());
            } else {
                this.time = convertDateFromISO(this.timeStr.toISOString());
            }
        } else {
            this.time = null;
        }
    },
    getYear: function () {
        return this.time.getFullYear();
    },
    getMonth: function () {
        return this.time.getMonth();
    },
    getDate: function () {
        return this.time.getDate();
    },
    getHours: function () {
        return this.time.getHours();
    },
    getMinutes: function () {
        return this.time.getMinutes();
    },
    getSeconds: function () {
        return this.time.getSeconds();
    },
    getMilliseconds: function () {
        return this.time.getMilliseconds();
    },
    isValid: function () {
        return !isNaN(this.time.getFullYear());
    },
    add: function (period) {
        if (period.msecs) {
            this.time.setMilliseconds(this.time.getMilliseconds() + period.msecs);
        }
        if (period.secs) {
            this.time.setSeconds(this.time.getSeconds() + period.secs);
        }
        if (period.mins) {
            this.time.setMinutes(this.time.getMinutes() + period.mins);
        }
        if (period.h) {
            this.time.setHours(this.time.getHours() + period.h);
        }
        if (period.d) {
            this.time.setDate(this.time.getDate() + period.d);
        }
        if (period.m) {
            this.time.setMonth(this.time.getMonth() + period.m);
        }
        if (period.y) {
            this.time.setFullYear(this.time.getFullYear() + period.y);
        }
    },
    substract: function (period) {
        if (period.msecs) {
            this.time.setMilliseconds(this.time.getMilliseconds() - period.msecs);
        }
        if (period.secs) {
            this.time.setSeconds(this.time.getSeconds() - period.secs);
        }
        if (period.mins) {
            this.time.setMinutes(this.time.getMinutes() - period.mins);
        }
        if (period.h) {
            this.time.setHours(this.time.getHours() - period.h);
        }
        if (period.d) {
            this.time.setDate(this.time.getDate() - period.d);
        }
        if (period.m) {
            this.time.setMonth(this.time.getMonth() - period.m);
        }
        if (period.y) {
            this.time.setFullYear(this.time.getFullYear() - period.y);
        }
    },
    toString: function () {
        var value = this.timeStr.isVc() ? '-' : '';
        value += this.timeStr.getYears() ? Mapbender.DimensionFormat(this.getYear(), 4) : '';
        value += this.timeStr.getMonths() ? ('-' + Mapbender.DimensionFormat(this.getMonth() + 1, 2)) : '';
        value += this.timeStr.getDays() ? ('-' + Mapbender.DimensionFormat(this.getDate(), 2)) : '';
        if (this.timeStr.getHours()) {
            value += this.timeStr.getHours() ? ('T' + Mapbender.DimensionFormat(this.getHours(), 2)) : '';
            value += this.timeStr.getMins() ? (':' + Mapbender.DimensionFormat(this.getMinutes(), 2)) : '';
            value += this.timeStr.getSecs() ? (':' + Mapbender.DimensionFormat(this.getSeconds(), 2)) : '';
            value += this.timeStr.getMsecs() ? ('.' + Mapbender.DimensionFormat(this.getMilliseconds(), 3)) : '';
        }
        value += this.utc ? 'Z' : '';
        return value;
    }
});


PeriodISO8601 = Class({
    'public number years': null,
    'public number months': null,
    'public number days': null,
    'public number hours': null,
    'public number mins': null,
    'public number secs': null,
    __construct: function (datetimeStr) {
        this.years = null;
        this.months = null;
        this.days = null;
        this.hours = null;
        this.mins = null;
        this.secs = null;
        if (datetimeStr.indexOf('P') === 0) {
            var str = datetimeStr.substr(1);
            var tmp, y = 0, m = 0, d = 0, h = 0, mins = 0, secs = 0;

            if (str.indexOf('T') !== -1) {
                tmp = str.split('T');
                str = tmp[1];
                if (str.indexOf('H') !== -1) {
                    tmp = str.split('H');
                    this.hours = parseInt(tmp[0]);
                    str = tmp[1];
                }
                if (str.indexOf('M') !== -1) {
                    tmp = str.split('M');
                    this.mins = parseInt(tmp[0]);
                    str = tmp[1];
                }
                if (str.indexOf('S') !== -1) {
                    tmp = str.split('S');
                    this.secs = parseInt(tmp[0]);
                    str = tmp[1];
                }
                str = tmp[0];
            }

            if (str.indexOf('Y') !== -1) {
                tmp = str.split('Y');
                this.years = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('M') !== -1) {
                tmp = str.split('M');
                this.months = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('D') !== -1) {
                tmp = str.split('D');
                this.days = parseInt(tmp[0]);
                str = tmp[1];
            }
        }
    },
    'public getType': function () {
        if (this.years === null && this.months === null) {
            return 'msec';
        } else if (this.years !== null && this.months === null && this.days === null && this.hours === null && this.mins === null && this.secs === null) {
            return 'year';
        } else if (this.days === null && this.hours === null && this.mins === null && this.secs === null) {
            return 'month';
        } else {
            return 'date';
        }
    },
    'public added': function (period) {
        var newPeriod = new PeriodISO8601(this.toString());
        if (period.secs) {
            newPeriod.secs = (newPeriod.secs ? newPeriod.secs : 0) + period.secs;
            var temp = newPeriod.secs / 60;
            if (temp >= 1) {
                newPeriod.mins = (newPeriod.mins ? newPeriod.mins : 0) + Math.floor(temp);
                newPeriod.secs = newPeriod.secs - Math.floor(temp) * 60;
            }
        }
        if (period.mins) {
            newPeriod.mins = (newPeriod.mins ? newPeriod.mins : 0) + period.mins;
        }
        if (newPeriod.mins) {
            var temp = newPeriod.mins / 60;
            if (temp >= 1) {
                newPeriod.hours = (newPeriod.hours ? newPeriod.hours : 0) + Math.floor(temp);
                newPeriod.mins = newPeriod.mins - Math.floor(temp) * 60;
            }
        }
        if (period.hours) {
            newPeriod.hours = (newPeriod.hours ? newPeriod.hours : 0) + period.hours;
        }
        if (newPeriod.hours) {
            var temp = newPeriod.hours / 24;
            if (temp >= 1) {
                newPeriod.days = (newPeriod.days ? newPeriod.days : 0) + Math.floor(temp);
                newPeriod.hours = newPeriod.hours - Math.floor(temp) * 24;
            }
        }
        if (period.days) {
            newPeriod.days = (newPeriod.days ? newPeriod.days : 0) + period.days;
        }


        if (period.months) {
            newPeriod.months = (newPeriod.months ? newPeriod.months : 0) + period.months;
        }
        if (newPeriod.months) {
            var temp = newPeriod.months / 12;
            if (temp >= 1) {
                newPeriod.years = (newPeriod.years ? newPeriod.years : 0) + Math.floor(temp);
                newPeriod.months = newPeriod.months - Math.floor(temp) * 12;
            }
        }
        if (period.years) {
            newPeriod.years = (newPeriod.years ? newPeriod.years : 0) + period.years;
        }
        return newPeriod;
    },
    'public toString': function () {
        var time = this.hours > 0 ? this.hours + 'H' : '';
        time += this.mins > 0 ? this.mins + 'M' : '';
        time += this.secs > 0 ? this.secs + 'S' : '';
        time = time.length > 0 ? 'T' + time : '';
        var date = this.years > 0 ? this.years + 'Y' : '';
        date += this.months > 0 ? this.months + 'M' : '';
        date += this.days > 0 ? this.days + 'D' : '';
        return (date.length + time.length) > 0 ? 'P' + (date + time) : '';
    },
    'public equals': function (period) {
        if (this.years !== period.years || this.months !== period.months || this.days !== period.days || this.hours !== period.hours || this.mins !== period.mins || this.secs !== period.secs) {
            return false;
        } else {
            return true;
        }
    },
    'public asMsec': function () {
        if (this.getType() === 'msec') {
            var stepTst = this.secs ? this.secs : 0;// secs
            stepTst += this.mins ? this.mins * 60 : 0;// secs
            stepTst += this.hours ? this.hours * 3600 : 0;// secs
            stepTst += this.days ? this.days * 86400 : 0;// secs
            return stepTst = stepTst * 1000;//msecs
        } else {
            return null;
        }
    },
    'public asMonth': function (timeStart, timeEnd) {
        if (this.getType() === 'month') {
            return this.years * 12 + this.months;
        } else {
            return null;
        }
    }
});

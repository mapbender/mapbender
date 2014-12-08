var Mapbender = Mapbender || {};
Mapbender.IDimension = Interface({
    getOptions: 'function',
    getValue: 'function',
    setValue: 'function',
    getStepsNum: 'function',
    partFromValue: 'function',
    stepFromPart: 'function',
    stepFromValue: 'function',
    valueFromPart: 'function',
    valueFromStart: 'function',
    valueFromEnd: 'function',
    isValid: 'function'
});
Mapbender.Dimension = function(options) {
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
Mapbender.DimensionScalar = Class({implements: Mapbender.IDimension}, {
    options: {},
    value: null,
    stepsNum: -1,
    __construct: function(options) {
        this.options = options;
        this.value = options['default'] === null ? options.extent[0] : options['default'];
    },
    getOptions: function() {
        return this.options;
    },
    getValue: function() {
        return this.value;
    },
    setValue: function(val) {
        this.value = val;
    },
    getStepsNum: function() {
        if (this.stepsNum !== -1) {
            return this.stepsNum;
        } else if (this.options.type === 'interval') {
            return Math.round(Math.abs(this.options.extent[1] - this.options.extent[0]) / this.options.extent[2]);
        } else if (this.options.type === 'multiple') {
            return this.options.extent.length;
        }
    },
    partFromValue: function(val) {
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
    stepFromPart: function(part) {
        return Math.round(part * (this.getStepsNum()));
    },
    stepFromValue: function(val) {
        return this.stepFromPart(this.partFromValue(val));
    },
    valueFromPart: function(part) {
        var step = this.stepFromPart(part);
        this.value = this.options.extent[step];
        return this.value;
    },
    valueFromStart: function() {
        return this.options.extent[0];
    },
    valueFromEnd: function() {
        return this.options.extent[this.options.extent.length - 1];
    },
    isValid: function(subDim) {
        return true;
    }
});

Mapbender.DimensionFormat = function(value, numDig) {
    var d = numDig - ('' + value).length;
    while (d > 0) {
        value = '0' + value;
        d--;
    }
    return value;
};
Mapbender.DimensionTime = Class({implements: Mapbender.IDimension, 'extends': Mapbender.DimensionScalar}, {
    start: null,
    end: null,
    step: null,
    asc: true,
    __construct: function(options) {
        this['super']('__construct', options);
        this.start = new TimeISO8601(options.extent[0]);
        this.end = new TimeISO8601(options.extent[1]);
        this.step = new PeriodISO8601(options.extent[2]);
        this.asc = this.end.time.getTime() > this.start.time.getTime();
    },
    getStepsNum: function() {
        if (this.stepsNum !== -1) {
            return this.stepsNum;
        } else {
            if (this.step.getType() === 'msec') {
                var stepTst = this.step.secs ? this.step.secs : 0;// secs
                stepTst += this.step.mins ? this.step.mins * 60 : 0;// secs
                stepTst += this.step.hours ? this.step.hours * 3600 : 0;// secs
                stepTst += this.step.days ? this.step.days * 86400 : 0;// secs
                stepTst = stepTst * 1000;//msecs
                this.stepsNum = Math.floor(Math.abs(this.end.time.getTime() - this.start.time.getTime()) / stepTst);
            } else if (this.step.getType() === 'month') {
                var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
                var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
                var stepMonth = (this.step.years * 12 + this.step.months);
                this.stepsNum = Math.floor(Math.abs(endMonth - startMonth) / stepMonth);
            } else if (this.step.getType() === 'date') {
                /* TODO optimize? */
                var stepTime = new TimeISO8601(this.start.time.toJSON());
                this.stepsNum = 0;
                var endtime = this.end.time.getTime();
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
    partFromValue: function(isoDate) {
        var givenTime = new TimeISO8601(isoDate);
        if (this.step.getType() === 'msec') {
            var part = (givenTime.time.getTime() - this.start.time.getTime()) / (this.end.time.getTime() - this.start.time.getTime());
            return part;
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
            var timeMonth = (givenTime.getYear() * 12 + givenTime.getMonth());
            var part = (timeMonth - startMonth) / (endMonth - startMonth);
            return part;
        } else if (this.step.getType() === 'date') {
            /* TODO optimize? */
            var stepTime = new TimeISO8601(this.start.time.toJSON());
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
    stepFromPart: function(part) {
        if (this.step.getType() === 'msec') {
            return Math.round(part * (this.getStepsNum()));
        } else if (this.step.getType() === 'month') {
            return Math.round(part * (this.getStepsNum()));
        } else if (this.step.getType() === 'date') {
            return Math.round(part * (this.getStepsNum()));/* ??? */
        }
    },
    stepFromValue: function(value) {
        if (this.step.getType() === 'msec') {
            return this.stepFromPart(this.partFromValue(value));
        } else if (this.step.getType() === 'month') {
            return this.stepFromPart(this.partFromValue(value));
        } else if (this.step.getType() === 'date') {
            return this.stepFromPart(this.partFromValue(value));/* ??? */
        }
    },
    valueFromPart: function(part) {
        var step = this.stepFromPart(part);
        var time;
        if (this.step.getType() === 'msec') {
            var stepTst = this.step.secs ? this.step.secs : 0;// secs
            stepTst += this.step.mins ? this.step.mins * 60 : 0;// secs
            stepTst += this.step.hours ? this.step.hours * 3600 : 0;// secs
            stepTst += this.step.days ? this.step.days * 86400 : 0;// secs
            stepTst = stepTst * 1000;//msecs
            if (this.asc) {
                time = new TimeISO8601(new Date(this.start.time.getTime() + step * stepTst));
            } else {
                time = new TimeISO8601(new Date(this.start.time.getTime() - step * stepTst));
            }
            return time.toString();
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var stepMonth = (this.step.years * 12 + this.step.months);
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
            var stepTime = new TimeISO8601(this.start.time.toJSON());
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
    valueFromStart: function() {
        return this.start.time.toJSON();
    },
    valueFromEnd: function() {
        return this.end.time.toJSON();
    },
    isValid: function(subDim) {
        /* TODO  */
        var inRange;
        if (this.asc) {
            inRange = subDim.start.time.getTime() < subDim.end.time.getTime()
                    && this.start.time.getTime() <= subDim.start.time.getTime()
                    && this.end.time.getTime() >= subDim.start.time.getTime()
                    && this.start.time.getTime() <= subDim.end.time.getTime()
                    && this.end.time.getTime() >= subDim.end.time.getTime();

//            dimNew.start.time.getTime() < dimNew.start.time.getTime()
//            this.asc = this.end.time.getTime() > this.start.time.getTime();
        } else {
            inRange = subDim.start.time.getTime() > subDim.end.time.getTime()
                    && this.start.time.getTime() >= subDim.start.time.getTime()
                    && this.end.time.getTime() <= subDim.start.time.getTime()
                    && this.start.time.getTime() >= subDim.end.time.getTime()
                    && this.end.time.getTime() <= subDim.end.time.getTime();
        }
        return true;
    }
});

TimeStr = Class({
    years: null,
    months: null,
    days: null,
    hours: null,
    mins: null,
    secs: null,
    msecs: null,
    vC: false,
    utc: false,
    __construct: function(datetimeStr) {
        this.years = null;
        this.months = null;
        this.days = null;
        this.hours = null;
        this.mins = null;
        this.secs = null;
        this.msecs = null;
        var dtStr;
        if (datetimeStr.indexOf('-') === 0) {
            this.vC = true;
            dtStr = datetimeStr.substr(1);
        } else {
            dtStr = datetimeStr;
        }
        this.utc = datetimeStr.indexOf('Z') !== -1;
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
    toISOString: function() {
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
    utc: false,
    time: null,
    timeStr: null,
    __construct: function(date) {
        function convertDateFromISO(s) {
            s = s.split(/\D/);
            return new Date(Date.UTC(s[0], --s[1] || '', s[2] || '', s[3] || '', s[4] || '', s[5] || '', s[6] || ''))
        }
        if (typeof date === "object") {
            this.time = date;
            this.timeStr = new TimeStr(this.time.toJSON());
            this.utc = this.timeStr.utc;
        } else if (date === 'current') {
            this.time = new Date();
            this.timeStr = new TimeStr(this.time.toJSON());
            this.utc = this.timeStr.utc;
        } else if (!isNaN(new Date(date).getFullYear())) {
            this.timeStr = new TimeStr(date);
            this.utc = this.timeStr.utc;
            this.time = new Date(date);
        } else if (typeof date === 'string') {
            this.timeStr = new TimeStr(new TimeStr(date).toISOString());
            this.utc = this.timeStr.utc;
            if (date.indexOf('-') === 0) { // vC.
                this.timeStr = new TimeStr(new TimeStr(date).toISOString());
                this.utc = this.timeStr.utc;
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
    getYear: function() {
        return this.time.getFullYear();
    },
    getMonth: function() {
        return this.time.getMonth();
    },
    getDate: function() {
        return this.time.getDate();
    },
    getHours: function() {
        return this.time.getHours();
    },
    getMinutes: function() {
        return this.time.getMinutes();
    },
    getSeconds: function() {
        return this.time.getSeconds();
    },
    getMilliseconds: function() {
        return this.time.getMilliseconds();
    },
    isValid: function() {
        return !isNaN(this.time.getFullYear());
    },
    add: function(period) {
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
    substract: function(period) {
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
    toString: function() {
        var value = this.timeStr.vC ? '-' : '';
        value += this.timeStr.years ? Mapbender.DimensionFormat(this.getYear(), 4) : '';
        value += this.timeStr.months ? ('-' + Mapbender.DimensionFormat(this.getMonth() + 1, 2)) : '';
        value += this.timeStr.days ? ('-' + Mapbender.DimensionFormat(this.getDate(), 2)) : '';
        if (this.timeStr.hours) {
            value += this.timeStr.hours ? ('T' + Mapbender.DimensionFormat(this.getHours(), 2)) : '';
            value += this.timeStr.mins ? (':' + Mapbender.DimensionFormat(this.getMinutes(), 2)) : '';
            value += this.timeStr.secs ? (':' + Mapbender.DimensionFormat(this.getSeconds(), 2)) : '';
            value += this.timeStr.msecs ? ('.' + Mapbender.DimensionFormat(this.getMilliseconds(), 3)) : '';
        }
        value += this.utc ? 'Z' : '';
        return value;
    }
});


PeriodISO8601 = Class({
    years: null,
    months: null,
    days: null,
    hours: null,
    mins: null,
    secs: null,
    __construct: function(datetimeStr) {
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
    getType: function() {
        if (this.years === null && this.months === null) {
            return 'msec';
        } else if (this.days === null && this.hours === null && this.mins === null && this.secs === null) {
            return 'month';
        } else {
            return 'date';
        }
    },
    added: function(period) {
        var newPeriod = new PeriodISO8601(this.toString());
        if(period.secs){
            newPeriod.secs = (newPeriod.secs ? newPeriod.secs : 0) + period.secs;
            var temp = newPeriod.secs / 60;
            if(temp >= 1){
                newPeriod.mins = (newPeriod.mins ? newPeriod.mins : 0) + Math.floor(temp);
                newPeriod.secs = newPeriod.secs - Math.floor(temp) * 60;
            }
        }
        if(period.mins){
            newPeriod.mins = (newPeriod.mins ? newPeriod.mins : 0) + period.mins;
        }
        if(newPeriod.mins){
            var temp = newPeriod.mins / 60;
            if(temp >= 1){
                newPeriod.hours = (newPeriod.hours ? newPeriod.hours : 0) + Math.floor(temp);
                newPeriod.mins = newPeriod.mins - Math.floor(temp) * 60;
            }
        }
        if(period.hours){
            newPeriod.hours = (newPeriod.hours ? newPeriod.hours : 0) + period.hours;
        }
        if(newPeriod.hours){
            var temp = newPeriod.hours / 24;
            if(temp >= 1){
                newPeriod.days = (newPeriod.days ? newPeriod.days : 0) + Math.floor(temp);
                newPeriod.hours = newPeriod.hours - Math.floor(temp) * 24;
            }
        }
        if(period.days){
            newPeriod.days = (newPeriod.days ? newPeriod.days : 0) + period.days;
        }
        
        
        if(period.months){
            newPeriod.months = (newPeriod.months ? newPeriod.months : 0) + period.months;
        }
        if(newPeriod.months){
            var temp = newPeriod.months / 12;
            if(temp >= 1){
                newPeriod.years = (newPeriod.years ? newPeriod.years : 0) + Math.floor(temp);
                newPeriod.months = newPeriod.months - Math.floor(temp) * 12;
            }
        }
        if(period.years){
            newPeriod.years = (newPeriod.years ? newPeriod.years : 0) + period.years;
        }
        return newPeriod;
    },
    toString: function() {
        var time = this.hours > 0 ? this.hours + 'H' : '';
        time += this.mins > 0 ? this.mins + 'M' : '';
        time += this.secs > 0 ? this.secs + 'S' : '';
        time = time.length > 0 ? 'T' + time : '';
        var date = this.years > 0 ? this.years + 'Y' : '';
        date += this.months > 0 ? this.months + 'M' : '';
        date += this.days > 0 ? this.days + 'D' : '';
        return (date.length + time.lengt) > 0 ? 'P' + (date + time) : '';
    }
});

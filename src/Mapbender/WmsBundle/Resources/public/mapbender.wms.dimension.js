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
                var stepTst = this.step.sec ? this.step.sec : 0;// sec
                stepTst += this.step.min ? this.step.min * 60 : 0;// sec
                stepTst += this.step.hours ? this.step.hours * 3600 : 0;// sec
                stepTst += this.step.date ? this.step.date * 86400 : 0;// sec
                stepTst = stepTst * 1000;//msec
                this.date === null && this.hours === null && this.min === null && this.sec === null
                this.stepsNum = Math.floor(Math.abs(this.end.time.getTime() - this.start.time.getTime()) / stepTst);
            } else if (this.step.getType() === 'month') {
                var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
                var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
                var stepMonth = (this.step.year * 12 + this.step.month);
                this.stepsNum = Math.floor(Math.abs(endMonth - startMonth) / stepMonth);
            } else if (this.step.getType() === 'date') {
                // TODO 
//                var current = new TimeISO8601(this.start.time.toJSON());
//                this.stepsNum = 0;
//                if (this.asc) {
//                    while (current.time.getTime() <= this.end.time.getTime()) {
//                        this.stepsNum++;
//                        current.add(this.step);
//                    }
//                } else {
//                    while (current.time.getTime() >= this.end.time.getTime()) {
//                        this.stepsNum++;
//                        current.substract(this.step);
//                    }
//                }
            }
            return this.stepsNum;
        }
    },
    partFromValue: function(isoDate) {
        var current = new TimeISO8601(isoDate);
        if (this.step.getType() === 'msec') {
            var part = (current.time.getTime() - this.start.time.getTime()) / (this.end.time.getTime() - this.start.time.getTime());
            return part;
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var endMonth = (this.end.getYear() * 12 + this.end.getMonth());
            var timeMonth = (current.getYear() * 12 + current.getMonth());
            var part = (timeMonth - startMonth) / (endMonth - startMonth);
            return part;
        } else if (this.step.getType() === 'date') {
            // TODO 
//            var time = new TimeISO8601(this.start.time.toJSON());
//            var stepsNum = 0;
//            if (this.asc) {
//                while (time.time.getTime() <= current.time.getTime()) {
//                    stepsNum++;
//                    time.add(this.step);
//                }
//            } else {
//                while (time.time.getTime() >= current.time.getTime()) {
//                    stepsNum++;
//                    time.substract(this.step);
//                }
//            }
//            return stepsNum / this.stepsNum;
        }
    },
    stepFromPart: function(part) {
        if (this.step.getType() === 'msec') {
            return Math.round(part * (this.getStepsNum()));
        } else if (this.step.getType() === 'month') {
            return Math.round(part * (this.getStepsNum()));
        } else if (this.step.getType() === 'date') {

        }
    },
    stepFromValue: function(value) {
        if (this.step.getType() === 'msec') {
            return this.stepFromPart(this.partFromValue(value));
        } else if (this.step.getType() === 'month') {
            return this.stepFromPart(this.partFromValue(value));
        } else if (this.step.getType() === 'date') {

        }
    },
//    partFromStep: function(step){
//        return step / this.getStepsNum();
//    },
    valueFromPart: function(part) {
//        if(part > 1){
//            part = this.partFromStep(part);
//        }
        var step = this.stepFromPart(part);
        var time;
        if (this.step.getType() === 'msec') {
            var stepTst = this.step.sec ? this.step.sec : 0;// sec
            stepTst += this.step.min ? this.step.min * 60 : 0;// sec
            stepTst += this.step.hours ? this.step.hours * 3600 : 0;// sec
            stepTst += this.step.date ? this.step.date * 86400 : 0;// sec
            stepTst = stepTst * 1000;//msec
            if (this.asc) {
                time = new TimeISO8601(new Date(this.start.time.getTime() + step * stepTst));
            } else {
                time = new TimeISO8601(new Date(this.start.time.getTime() - step * stepTst));
            }
            return time.toString();
        } else if (this.step.getType() === 'month') {
            var startMonth = (this.start.getYear() * 12 + this.start.getMonth());
            var stepMonth = (this.step.year * 12 + this.step.month);
            var month;
            if (this.asc) {
                month = startMonth + step * stepMonth;
            } else {
                month = startMonth - step * stepMonth;
            }
            var year = Math.floor(month / 12);
            time = new TimeISO8601(Mapbender.DimensionFormat(year, 4) + "-" + Mapbender.DimensionFormat(month - year * 12 + 1, 2));
            return time.toString();
        } else if (this.step.getType() === 'date') {

        }
    },
    valueFromStart: function() {
        return this.start.time.toJSON();
    },
    valueFromEnd: function() {
        return this.end.time.toJSON();
    },
    isValid: function(subDim) {
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
    year: null,
    month: null,
    date: null,
    hours: null,
    min: null,
    sec: null,
    msec: null,
    vC: false,
    utc: false,
    __construct: function(datetimeStr) {
        this.year = null;
        this.month = null;
        this.date = null;
        this.hours = null;
        this.min = null;
        this.sec = null;
        this.msec = null;
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
            this.year = ymd[0];
        } catch (e) {
        }
        try {
            this.month = ymd[1];
        } catch (e) {
        }
        try {
            this.date = ymd[2];
        } catch (e) {
        }

        try {
            this.hours = hmsm[0];
        } catch (e) {
        }
        try {
            this.min = hmsm[1];
        } catch (e) {
        }
        try {
            var hmsmHelp = hmsm[2].split('.');
            this.sec = hmsmHelp[0];
            this.msec = hmsmHelp[1];
        } catch (e) {
        }
        try {
            this.msec = this.msec.replace('Z', '');
        } catch (e) {
        }
    },
    toISOString: function() {
        //2014-11-10T15:53:15.477Z
        var res = (this.vC ? "-" : "") + this.year;
        res += "-" + (this.month ? this.month : "01");
        res += "-" + (this.date ? this.date : "01") + "T";
        res += (this.hours ? this.hours : "00");
        res += ":" + (this.min ? this.min : "00");
        res += ":" + (this.sec ? this.sec : "00");
        res += "." + (this.msec ? this.msec : "000") + (this.utc ? "Z" : "");
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
            if(date.indexOf('-') === 0){ // vC.
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
        if (period.msec) {
            this.time.setMilliseconds(this.time.getMilliseconds() + period.msec);
        }
        if (period.sec) {
            this.time.setSeconds(this.time.getSeconds() + period.sec);
        }
        if (period.min) {
            this.time.setMinutes(this.time.getMinutes() + period.min);
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
        if (period.msec) {
            this.time.setMilliseconds(this.time.getMilliseconds() - period.msec);
        }
        if (period.sec) {
            this.time.setSeconds(this.time.getSeconds() - period.sec);
        }
        if (period.min) {
            this.time.setMinutes(this.time.getMinutes() - period.min);
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
        value += this.timeStr.year ? Mapbender.DimensionFormat(this.getYear(), 4) : '';
        value += this.timeStr.month ? ('-' + Mapbender.DimensionFormat(this.getMonth() + 1, 2)) : '';
        value += this.timeStr.date ? ('-' + Mapbender.DimensionFormat(this.getDate(), 2)) : '';
        if (this.timeStr.hours) {
            value += this.timeStr.hours ? ('T' + Mapbender.DimensionFormat(this.getHours(), 2)) : '';
            value += this.timeStr.min ? (':' + Mapbender.DimensionFormat(this.getMinutes(), 2)) : '';
            value += this.timeStr.sec ? (':' + Mapbender.DimensionFormat(this.getSeconds(), 2)) : '';
            value += this.timeStr.msec ? ('.' + Mapbender.DimensionFormat(this.getMilliseconds(), 3)) : '';
        }
        value += this.utc ? 'Z' : '';
        return value;
    }
});


PeriodISO8601 = Class({
    year: null,
    month: null,
    date: null,
    hours: null,
    min: null,
    sec: null,
//    msec: 0,
    __construct: function(datetimeStr) {
        this.year = null;
        this.month = null;
        this.date = null;
        this.hours = null;
        this.min = null;
        this.sec = null;
        if (datetimeStr.indexOf('P') === 0) {
            var str = datetimeStr.substr(1);
            var tmp, y = 0, m = 0, d = 0, h = 0, min = 0, sec = 0;

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
                    this.min = parseInt(tmp[0]);
                    str = tmp[1];
                }
                if (str.indexOf('S') !== -1) {
                    tmp = str.split('S');
                    this.sec = parseInt(tmp[0]);
                    str = tmp[1];
                }
                str = tmp[0];
            }

            if (str.indexOf('Y') !== -1) {
                tmp = str.split('Y');
                this.year = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('M') !== -1) {
                tmp = str.split('M');
                this.month = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('D') !== -1) {
                tmp = str.split('D');
                this.date = parseInt(tmp[0]);
                str = tmp[1];
            }
//            var strNew = this.toStr(y,4) + '-' + this.toStr(m === 0 ? 1 : m, 2);
//            strNew += '-' + this.toStr(d === 0 ? 1 : d, 2) + 'T' + this.toStr(h,2);
//            strNew += ':' + this.toStr(min,2) + ':' + this.toStr(sec,2) +  (hasZ ? 'Z' : '');
//            this.time = new Date(y, m, d, h, min, sec, 0);
//            this.time = new Date(strNew);
        }
    },
    getType: function() {
        if (this.year === null && this.month === null) {
            return 'msec';
        } else if (this.date === null && this.hours === null && this.min === null && this.sec === null) {
            return 'month';
        } else {
            return 'date';
        }
    },
    x2: function() {
        this.sec = this.sec * 2;
        this.min = this.min + Math.floor(this.sec / 60);
    }
//    ,
//    toString: function() {
//        var time = this._hh > 0 ? this._hh + 'H' : '';
//        time += this._min > 0 ? this._min + 'M' : '';
//        time += this._sec > 0 ? this._sec + 'S' : '';
//        time = time.length > 0 ? 'T' + time : '';
//        var date = this._yy > 0 ? this._yy + 'Y' : '';
//        date += this._mm > 0 ? this._mm + 'M' : '';
//        date += this._dd > 0 ? this._dd + 'D' : '';
//        return (date.length + time.lengt) > 0 ? 'P' + (date + time) : '';
//    }
});

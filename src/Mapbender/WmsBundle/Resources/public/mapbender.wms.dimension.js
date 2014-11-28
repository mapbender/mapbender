var Mapbender = Mapbender || {};
Mapbender.IDimension = Interface({
    getOptions: 'function',
    getValue: 'function',
    setValue: 'function',
    getStepsNum: 'function',
    partFromValue: 'function',
    stepFromPart: 'function',
    stepFromValue: 'function',
    valueFromPart: 'function'
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
    __options: {},
    __value: null,
    __stepsNum: -1,
    __construct: function(options) {
        this.__options = options;
        this.__value = options['default'] === null ? options.extent[0] : options['default'];
    },
    getOptions: function() {
        return this.__options;
    },
    getValue: function() {
        return this.__value;
    },
    setValue: function(value) {
        this.__value = value;
    },
    getStepsNum: function() {
        if (this.__stepsNum !== -1) {
            return this.__stepsNum;
        } else if (this.__options.type === 'interval') {
            return Math.round(Math.abs(this.__options.extent[1] - this.__options.extent[0]) / this.__options.extent[2]);
        } else if (this.__options.type === 'multiple') {
            return this.__options.extent.length;
        }
    },
    partFromValue: function(value) {
        if (this.__options.type === 'interval') {
            return Math.abs(value - this.__options.extent[0]) / Math.abs(this.__options.extent[1] - this.__options.extent[0]);
        } else if (this.__options.type === 'multiple') {
            for (var i = 0; i < this.__options.extent.length; i++) {
                if (value === this.__options.extent[i]) {
                    return i / (this.getStepsNum() + 1);
                }
            }
            return 0;
        }
    },
    stepFromPart: function(part) {
        return Math.round(part * (this.getStepsNum()));
    },
    stepFromValue: function(value) {
        return this.stepFromPart(this.partFromValue(value));
    },
    valueFromPart: function(part) {
        var step = this.stepFromPart(part);
        this.value = this.__options.extent[step];
        return this.value;
    }
});

Mapbender.DimensionTime = Class({implements: Mapbender.IDimension, 'extends': Mapbender.DimensionScalar}, {
    __start: null,
    __startTst: null,
    __end: null,
    __endTst: null,
    __step: null,
    __stepTst: null,
    __asc: true,
    __construct: function(options) {
        this['super']('__construct', options);
        var hasZ = options.extent[0].indexOf('Z') !== -1;
        this.__start = new TimeISO8601(options.extent[0]);
        this.__startTst = this.__start.time.getTime();
        this.__end = new TimeISO8601(options.extent[1]);
        this.__endTst = this.__end.time.getTime();
        this.__step = new PeriodISO8601(options.extent[2], hasZ);
        var t1970 = new TimeISO8601("1970-01-01T00:00:00" + (hasZ ? 'Z' : ''));
        t1970.add(this.__step);
        this.__stepTst = t1970.time.getTime();
        this.__asc = this.__endTst > this.__startTst;
    },
    getStepsNum: function() {
        if (this.__stepsNum !== -1) {
            return this.__stepsNum;
        } else {
            this.__stepsNum = Math.abs(this.__endTst - this.__startTst) / this.__stepTst;
            return this.__stepsNum;
        }
    },
    partFromValue: function(value) {
        var time = new TimeISO8601(value);
        var part = (time.time.getTime() - this.__startTst) / (this.__endTst - this.__startTst);
        return part;
    },
    stepFromPart: function(part) {
        return Math.round(part * (this.getStepsNum()));
    },
    stepFromValue: function(value) {
        return this.stepFromPart(this.partFromValue(value));
    },
    valueFromPart: function(part) {
        var step;
        if(part <= 1)
            step = part;
        else
            step = this.stepFromPart(part);
        var time;
        if(this.__asc){
            time = new Date(this.__startTst + step * this.__stepTst);
        } else {
            time = new Date(this.__startTst - step * this.__stepTst);
        }
        return time.toISOString();
        
//        var steptime = new TimeISO8601(this.__start.time.toISOString());
//        var num = 0;
//        while (steptime.time.getTime() <= this.__endTst) {
//            if (step === num) {
//                return steptime;
//            }
//            if (this.__asc) {
//                steptime.add(this.__step.time);
//            } else {
//                steptime.substract(this.__step.time);
//            }
//            num++;
//        }
//        return this.__start.time;
    }
});

TimeISO8601 = Class({
    time: null,
    __construct: function(datetimeStr) {
        if (datetimeStr === 'current') {
            this.time = new Date();
        } else if (!isNaN(new Date(datetimeStr).getFullYear())) {
            this.time = new Date(datetimeStr);
        } else if (datetimeStr.indexOf('-') === 0) { // vC.
            var d = new Date(datetimeStr.substr(1));
            return new Date(-d.getFullYear(), d.getMonth(), d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds(), d.getMilliseconds());
        } else {
            this.time = null;
        }
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
    }
});


PeriodISO8601 = Class({
    y: 0,
    m: 0,
    d: 0,
    h: 0,
    min: 0,
    sec: 0,
    msec: 0,
    __construct: function(datetimeStr, hasZ) {
        if (datetimeStr.indexOf('P') === 0) {
            var str = datetimeStr.substr(1);
            var tmp, y = 0, m = 0, d = 0, h = 0, min = 0, sec = 0;

            if (str.indexOf('T') !== -1) {
                tmp = str.split('T');
                str = tmp[1];
                if (str.indexOf('H') !== -1) {
                    tmp = str.split('H');
                    this.h = parseInt(tmp[0]);
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
                this.y = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('M') !== -1) {
                tmp = str.split('M');
                this.m = parseInt(tmp[0]);
                str = tmp[1];
            }
            if (str.indexOf('D') !== -1) {
                tmp = str.split('D');
                this.d = parseInt(tmp[0]);
                str = tmp[1];
            }
//            var strNew = this.toStr(y,4) + '-' + this.toStr(m === 0 ? 1 : m, 2);
//            strNew += '-' + this.toStr(d === 0 ? 1 : d, 2) + 'T' + this.toStr(h,2);
//            strNew += ':' + this.toStr(min,2) + ':' + this.toStr(sec,2) +  (hasZ ? 'Z' : '');
//            this.time = new Date(y, m, d, h, min, sec, 0);
//            this.time = new Date(strNew);
        }
    },
    toStr: function(value, numDig) {
        var d = numDig - ('' + value).length;
        while (d > 0) {
            value = '0' + value;
            d--;
        }
        return value;
    }
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
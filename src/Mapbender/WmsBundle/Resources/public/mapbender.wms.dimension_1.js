//
// Create proper-derivable "class".
//
// Version: 1.2
//
var newClass =  newClass || function newClass(parent, prop) {
  // Dynamically create class constructor.
  var clazz = function() {
    // Stupid JS need exactly one "operator new" calling for parent
    // constructor just after class definition.
    if (clazz.preparing) return delete(clazz.preparing);
    // Call custom constructor.
    if (clazz.constr) {
      this.constructor = clazz; // we need it!
      clazz.constr.apply(this, arguments);
    }
  };
  clazz.prototype = {}; // no prototype by default
  if (parent) {
    parent.preparing = true;
    clazz.prototype = new parent;
    clazz.prototype.constructor = parent;
    clazz.constr = parent; // BY DEFAULT - parent constructor
  }
  if (prop) {
    var cname = "constructor";
    for (var k in prop) {
      if (k != cname) clazz.prototype[k] = prop[k];
    }
    if (prop[cname] && prop[cname] != Object)
      clazz.constr = prop[cname];
  }
  return clazz;
};


/**
 * 
 * Mapbender metadata connector to call metadata
 */
var Mapbender = Mapbender || {};
Mapbender.Dimension = Mapbender.Dimension || newClass(null, {
    options: {},
    value: null,
    constructor: function(options){
        this.options = options;
    },
    getValue: function(){
        return this.value;
    },
    getDimension: function(options){
        if(options.type === 'interval'){
            return new Mapbender.Dimension.Interval(options);
        }else if(options.type === 'multiple'){
            return new Mapbender.Dimension.Multiple(options); // Add MultipleScalar ???
        }else{
            return null;
        }
    }
});
Mapbender.Dimension.IntervalScalar = newClass(Mapbender.Dimension, {
    getStartStep: function(){
        this.value = this.options.default === null ? this.options.extent[0] : this.options.default;
        return this.value;
    },
    getSteps: function(){
        return Math.round(Mapth.abs(this.options.extent[1] - this.options.extent[0]) / this.options.extent[2] + 1);
    },
    
    calculateValue: function(tail){
        
//        var val = this.options.extent[0] + (this.options.extent[this.options.extent.length - 1] - this.options.extent[0]) * tail;
//        for (var i = 1; i < this.options.extent.length; i++) {
//            if (val >= this.options.extent[i - 1] && val <= this.options.extent[i]) {
//                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
//                return this.value;
//            } else if (val <= this.options.extent[i - 1] && val >= this.options.extent[i]) {
//                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
//                return this.value;
//            }
//        }
    }
});
Mapbender.Dimension.IntervalTime = newClass(Mapbender.Dimension, {
    getStartStep: function(){
        return this.options.default === null ? 0 : $.inArray(this.options.default, this.options.extent);
    },
    getSteps: function(){
        return this.options.extent.lenght();
    },
    
    calculateValue: function(tail){
        var val = this.options.extent[0] + (this.options.extent[this.options.extent.length - 1] - this.options.extent[0]) * tail;
        for (var i = 1; i < this.options.extent.length; i++) {
            if (val >= this.options.extent[i - 1] && val <= this.options.extent[i]) {
                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
                return this.value;
            } else if (val <= this.options.extent[i - 1] && val >= this.options.extent[i]) {
                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
                return this.value;
            }
        }
    }
});
Mapbender.Dimension.Multiple = newClass(Mapbender.Dimension, {
    constructor: function(options){
        this.constructor.prototype.constructor.call(this,options);
        this.calculateValue(this.getSteps() / this.getStartStep());
    },
    getStartStep: function(){
        return this.options.default === null ? 0 : $.inArray(this.options.default, this.options.extent);
    },
    getSteps: function(){
        return this.options.extent.length;
    },
    
    calculateValue: function(tail){
        var pos = Math.round(tail * (this.getSteps() - 1));
        this.value = this.options.extent[pos];
        return this.value;
    }
});

Mapbender.Dimension.MultipleScalar = newClass(Mapbender.Dimension.Multiple, {
    calculateValue: function(tail){
        var val = this.options.extent[0] + (this.options.extent[this.options.extent.length - 1] - this.options.extent[0]) * tail;
        for (var i = 1; i < this.options.extent.length; i++) {
            if (val >= this.options.extent[i - 1] && val <= this.options.extent[i]) {
                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
                return this.value;
            } else if (val <= this.options.extent[i - 1] && val >= this.options.extent[i]) {
                this.value = val >= (this.options.extent[i - 1] + this.options.extent[i]) / 2.0 ? this.options.extent[i] : this.options.extent[i - 1];
                return this.value;
            }
        }
    }
});
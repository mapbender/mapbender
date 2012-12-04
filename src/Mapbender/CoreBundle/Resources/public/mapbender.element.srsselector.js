(function($) {

$.widget("mapbender.mbSrsSelector", {
    options: {
//        crsList:[{name: "EPSG:25832", title: "ETRS89 / UTM Zone 32N"}]
    },

//    elementUrl: null,
    
    op_sel: null,
    
    mapWidget: null,
//    
//    origExtents: {},

    _create: function() {
        var self = this;
        var me = $(this.element);
//        this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
        this.mapWidget = $('#' + self.options.targets.map);
        var mbMap = this.mapWidget.data('mbMap');
        var options = "";
        options += '<option value="' + mbMap.options.srs + '">' + mbMap.options.srs + '</option>';
        var otherSrs = mbMap.options.otherSrs.split(",");
        for(var i = 0; i < otherSrs.length; i++){
            options += '<option value="' + otherSrs[i] + '">' + otherSrs[i] + '</option>';
        }
        $("#"+me.attr('id')).html(options);
        this.op_sel = "#"+me.attr('id')+" option";
        $(self.element).val(mbMap.map.olMap.getProjection());
        $(self.element).change($.proxy(self._switchCrs, self));
        
//        $(document).one('mapbender.setupfinished', $.proxy(this._mapbenderSetupFinished, this));
    },
    
//    _mapbenderSetupFinished: function() {
//      this._init(); 
//    },
//    
//    _init: function(){
//        var self = this;
//        var me = $(this.element);
//        this.op_sel = "#"+me.attr('id')+" option";
//        var mbMap = $('#' + self.options.targets).data('mbMap');
//        $(self.element).val(mbMap.map.olMap.getProjection());
//        $(self.element).change($.proxy(self._switchCrs, self));
//    },
    
    showHidde: function() {
        var self = this;
        var div_id = '#'+$(self.element).attr('id')+'-div';

        if($(self.element).css('display') && $(self.element).css('display') == 'none'){
            $(self.element).css('display', 'inline');
            $(div_id).css('display', 'inline');
        } else {
            $(self.element).css('display', 'none');
            $(div_id).css('display', 'none');
        }
    },
    
    _switchCrs: function(evt) {
        var dest = new OpenLayers.Projection(this.getSelectedCrs());
        if(dest.projCode === 'EPSG:4326') {
            dest.proj.units = 'degrees';
        }

        this.mapWidget.mbMap("setMapProjection", dest);
        $('.mb-element-coordsdisplay').mbCoordinatesDisplay("reset");
        return true;
    },
    
    selectCrs: function(crs) {
        if(this.isCrsSupported(crs)){
            $(this.op_sel + '[value="'+crs+'"]').attr('selected',true);
            this._switchCrs();
            return true;
        } return false;
    },
    
    getSelectedCrs: function() {
        return $(this.element).val();
    },
    
    isCrsSupported: function(crs) {
        if(typeof($(this.op_sel + '[value="'+crs+'"]').val()) !== 'undefined'){
            return true;
        }
        return false;
    },
    
    isCrsEnabled: function(crs) {
        if(!this.isCrsSupported(crs))
            return false;
        if($(this.op_sel + '[value="'+crs+'"]').attr("disabled")){
            return false;
        }
        return true;
    },

    disableCrs: function(crs){
        if($.type(crs) === "string"){
            if(this.isCrsSupported(crs)){
                $(this.op_sel + '[value="'+crs+'"]').attr("disabled", "disabled");
                return true;
            } else {
                return false;
            }
        } else if($.type(crs) === "object"){
            var res = false;
            for(idx in crs){
                var crsName;
                if(typeof(idx) === 'number'){
                    crsName = crs[idx];
                } else {
                    crsName = idx;
                }
                if(this.isCrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').attr("disabled", "disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },

    enableCrs: function(crs){
        if($.type(crs) === "string"){
            if(this.isCrsSupported(crs)){
                $(this.op_sel + '[value="'+crs+'"]').removeAttr("disabled");
                return true;
            } else {
                return false;
            }
        } else if($.type(crs) === "object"){
            var res = false;
            for(idx in crs){
                var crsName;
                if(typeof(idx) === 'number'){
                    crsName = crs[idx];
                } else {
                    crsName = idx;
                }
                if(this.isCrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').removeAttr("disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },
    
    enableOnlyCrs: function(crs){
        this.disableAllCrs();
        if($.type(crs) === "string"){
            if(this.isCrsSupported(crs)){
                $(this.op_sel + '[value="'+crs+'"]').removeAttr("disabled");
                return true;
            } else {
                return false;
            }
        } else if($.type(crs) === "object"){
            var res = false;
            for(idx in crs){
                var crsName;
                if(typeof(idx) === 'number'){
                    crsName = crs[idx];
                } else {
                    crsName = idx;
                }
                if(this.isCrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').removeAttr("disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },
    
    getFullCrsObj: function(crs){
        var result = [];
        if($.type(crs) === "string"){
            if(this.isCrsSupported(crs)){
                return [{name: crs, title: $(this.op_sel + '[value="'+crs+'"]').text()}];
            }
        } else if($.type(crs) === "object"){
            $.each($(this.op_sel), function(idx_, option){
                for(idx in crs){
                    var crsName;
                    if(typeof(idx) === 'number'){
                        crsName = crs[idx];
                    } else {
                        crsName = idx;
                    }
                    if($(option).val()==crsName){
                        result.push({name: $(option).val(), title: $(option).text()});
                    }
                }
//                
//                
//                for(var j = 0; j < crsesArr.length; j++){
//                    if(option.val() == crsesArr[j]){
//                        result.push(crsesArr[j]);
//                    }
//                }
            });
//            var result = {};
//            for(idx in crs){
//                var crsName;
//                if(typeof(idx) === 'number'){
//                    crsName = crs[idx];
//                } else {
//                    crsName = idx;
//                }
//                if(this.isCrsSupported(crsName)){
//                    result[crsName] = $(this.op_sel + '[value="'+crsName+'"]').text();
//                }
//            }
            return result;
        }
        return [];
    },

    enableAllCrs: function(){
        $.each($(this.op_sel), function(idx, val){
            $(this).removeAttr("disabled");
        });
        return true;
    },
    
    disableAllCrs: function(){
        $.each($(this.op_sel), function(idx, val){
            $(this).attr("disabled","disabled");
        });
        return true;
    },
    
    getInnerJoinCrs: function(crsesArr){
        var result = new Array();
//        for(var j = 0; j < crsesArr.length; j++){
//            if(this.isCrsSupported(crsesArr[j])){
//                result.push(crsesArr[j]);
//            }
//        }
        $.each($(this.op_sel), function(idx, option){
            for(var j = 0; j < crsesArr.length; j++){
                if(option.val() == crsesArr[j]){
                    result.push(crsesArr[j]);
                }
            }
        });
        return result;
    },
    
    getInnerJoinArrays: function(arr1, arr2){
        var result = [];
        for(var i = 0; i < arr1.lenght; i++){
            for(var j = 0; j < arr2.lenght; j++){
                if(arr1[i] == arr2[j]){
                    result.push(arr1[i]);
                }
            }
        }
        return result;
    },
    _destroy: $.noop
});

})(jQuery);


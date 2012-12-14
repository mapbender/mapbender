(function($) {

$.widget("mapbender.mbSrsSelector", {
    options: {
//        crsList:[{name: "EPSG:25832", title: "ETRS89 / UTM Zone 32N"}]
        targets: {map: "map", coordsdisplay: "coordinates" }
    },

    op_sel: null,

    mapWidget: null,

    _create: function() {
        this.mapWidget = $('#' + this.options.targets.map);
        this.mapWidget.one('mbmapready', $.proxy(this._setup, this));
    },

    _setup: function(){
        console.log("XXX");
        var self = this;
        var me = $(this.element);
        var mbMap = this.mapWidget.data('mbMap');
        var options = "";
        var allSrs = mbMap.getAllSrs();
        for(srs in allSrs){
            options += '<option value="' + srs + '">' + allSrs[srs] + '</option>';
        }
        $("#"+me.attr('id')).html(options);
        this.op_sel = "#"+me.attr('id')+" option";
        $(self.element).val(mbMap.map.olMap.getProjection());
        $(self.element).change($.proxy(self._switchSrs, self));
    },

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

    _switchSrs: function(evt) {
        var old = this.mapWidget.data('mbMap').map.olMap.getProjectionObject();
        var dest = new OpenLayers.Projection(this.getSelectedSrs());
//        if(old.projCode == dest.projCode){
//            return true;
//        }
        window.console && console.log("switch STS from:"+old.projCode
            +" into:"+dest.projCode, dest.readyToUse);

        if(dest.projCode === 'EPSG:4326') {
            dest.proj.units = 'degrees';
        }
        this.mapWidget.mbMap("setMapProjection", dest);
//        $('#' + this.options.targets.coordsdisplay).mbCoordinatesDisplay("reset");
        $('.mb-element-coordsdisplay').mbCoordinatesDisplay("reset");
        return true;
    },

    selectSrs: function(crs) {
        if(this.isSrsSupported(crs)){
            $(this.op_sel + '[value="'+crs+'"]').attr('selected',true);
            this._switchSrs();
            return true;
        } return false;
    },

    getSelectedSrs: function() {
        return $(this.element).val();
    },

    isSrsSupported: function(crs) {
        if(typeof($(this.op_sel + '[value="'+crs+'"]').val()) !== 'undefined'){
            return true;
        }
        return false;
    },

    isSrsEnabled: function(crs) {
        if(!this.isSrsSupported(crs))
            return false;
        if($(this.op_sel + '[value="'+crs+'"]').attr("disabled")){
            return false;
        }
        return true;
    },

    disableSrs: function(crs){
        if($.type(crs) === "string"){
            if(this.isSrsSupported(crs)){
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
                if(this.isSrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').attr("disabled", "disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },

    enableSrs: function(crs){
        if($.type(crs) === "string"){
            if(this.isSrsSupported(crs)){
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
                if(this.isSrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').removeAttr("disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },

    enableOnlySrs: function(crs){
        this.disableAllSrs();
        if($.type(crs) === "string"){
            if(this.isSrsSupported(crs)){
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
                if(this.isSrsSupported(crsName)){
                    $(this.op_sel + '[value="'+crsName+'"]').removeAttr("disabled");
                    res = true;
                }
            }
            return res;
        }
        return false;
    },

    getFullSrsObj: function(crs){
        var result = [];
        if($.type(crs) === "string"){
            if(this.isSrsSupported(crs)){
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
//                if(this.isSrsSupported(crsName)){
//                    result[crsName] = $(this.op_sel + '[value="'+crsName+'"]').text();
//                }
//            }
            return result;
        }
        return [];
    },

    enableAllSrs: function(){
        $.each($(this.op_sel), function(idx, val){
            $(this).removeAttr("disabled");
        });
        return true;
    },

    disableAllSrs: function(){
        $.each($(this.op_sel), function(idx, val){
            $(this).attr("disabled","disabled");
        });
        return true;
    },

    getInnerJoinSrs: function(crsesArr){
        var result = new Array();
//        for(var j = 0; j < crsesArr.length; j++){
//            if(this.isSrsSupported(crsesArr[j])){
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


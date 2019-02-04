(function($){

    $.widget("mapbender.mbSrsSelector", {
        options: {
            target: null
        },
        op_sel: null,
        model: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbSrsSelector", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            var self = this;
            this.targetMbMap_ = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;

            var options = "";
            var allSrs = this.targetMbMap_.getAllSrs();

            var mapProjectionCode = this.targetMbMap_.model.getCurrentProjectionCode();

            for(var i = 0; i < allSrs.length; i++){
                options += '<option value="' + allSrs[i].name + '">' + allSrs[i].title + '</option>';
            }
            $("#" + $(this.element).attr('id') + " select").html(options);
            this.op_sel = "#" + $(this.element).attr('id') + " select option";
            $(this.op_sel + '[value="' + mapProjectionCode + '"]').prop("selected", true);
            initDropdown.call(this.element.get(0));
            $("#" + $(this.element).attr('id') + " select").on('change', $.proxy(self._switchSrs, self));
            $(document).on('mbmapsrschanged', $.proxy(self._onSrsChanged, self));
            $(document).on('mbmapsrsadded', $.proxy(self._onSrsAdded, self));

            this._trigger('ready');
        },
        showHidde: function(){
            var self = this;
            var div_id = '#' + $(self.element).attr('id');

            if($(self.element).css('display') && $(self.element).css('display') == 'none'){
                $(self.element).css('display', 'inline');
                $(div_id).css('display', 'inline');
            }else{
                $(self.element).css('display', 'none');
                $(div_id).css('display', 'none');
            }
        },
        _switchSrs: function(event){
            this.targetMbMap_.changeProjection(this.getSelectedSrs());
        },
        _onSrsChanged: function(event, srsObj){
            this.selectSrs(srsObj.projection.projCode);
        },
        _onSrsAdded: function(event, srsObj){
            $("#" + $(this.element).attr('id') + " select").append($('<option></option>').val(srsObj.name).html(srsObj.title));
            if(initDropdown){
                initDropdown.call(this.element.get(0));
            }
        },
        selectSrs: function(crs){
            if(this.isSrsSupported(crs)){
                $(this.op_sel + '[value="' + crs + '"]').attr('selected', true);
                return true;
            }
            return false;
        },
        getSelectedSrs: function(){
            return $("#" + $(this.element).attr('id') + " select").val();
        },
        isSrsSupported: function(crs){
            if(typeof($(this.op_sel + '[value="' + crs + '"]').val()) !== 'undefined'){
                return true;
            }
            return false;
        },
        isSrsEnabled: function(crs){
            if(!this.isSrsSupported(crs))
                return false;
            if($(this.op_sel + '[value="' + crs + '"]').attr("disabled")){
                return false;
            }
            return true;
        },
        disableSrs: function(crs){
            if($.type(crs) === "string"){
                if(this.isSrsSupported(crs)){
                    $(this.op_sel + '[value="' + crs + '"]').attr("disabled", "disabled");
                    return true;
                }else{
                    return false;
                }
            }else if($.type(crs) === "object"){
                var res = false;
                for(var idx in crs){
                    var crsName;
                    if(typeof(idx) === 'number'){
                        crsName = crs[idx];
                    }else{
                        crsName = idx;
                    }
                    if(this.isSrsSupported(crsName)){
                        $(this.op_sel + '[value="' + crsName + '"]').attr("disabled", "disabled");
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
                    $(this.op_sel + '[value="' + crs + '"]').removeAttr("disabled");
                    return true;
                }else{
                    return false;
                }
            }else if($.type(crs) === "object"){
                var res = false;
                for(var idx in crs){
                    var crsName;
                    if(typeof(idx) === 'number'){
                        crsName = crs[idx];
                    }else{
                        crsName = idx;
                    }
                    if(this.isSrsSupported(crsName)){
                        $(this.op_sel + '[value="' + crsName + '"]').removeAttr("disabled");
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
                    $(this.op_sel + '[value="' + crs + '"]').removeAttr("disabled");
                    return true;
                }else{
                    return false;
                }
            }else if($.type(crs) === "object"){
                var res = false;
                for(var idx in crs){
                    var crsName;
                    if(typeof(idx) === 'number'){
                        crsName = crs[idx];
                    }else{
                        crsName = idx;
                    }
                    if(this.isSrsSupported(crsName)){
                        $(this.op_sel + '[value="' + crsName + '"]').removeAttr("disabled");
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
                    return [{
                            name: crs,
                            title: $(this.op_sel + '[value="' + crs + '"]').text()
                        }];
                }
            }else if($.type(crs) === "object"){
                $.each($(this.op_sel), function(idx_, option){
                    for(var idx in crs){
                        var crsName;
                        if(typeof(idx) === 'number'){
                            crsName = crs[idx];
                        }else{
                            crsName = idx;
                        }
                        if($(option).val() == crsName){
                            result.push({
                                name: $(option).val(),
                                title: $(option).text()
                            });
                        }
                    }
                });
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
                $(this).attr("disabled", "disabled");
            });
            return true;
        },
        getInnerJoinSrs: function(crsesArr){
            var result = new Array();
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


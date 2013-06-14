(function($) {
    

    $.widget("mapbender.mbWmcHandler", {
        options: {},

        elementUrl: null,
        //        dlg: null,

        _create: function() {
            if(!Mapbender.checkTarget("mbWmcHandler", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the wmc handler
         */
        _setup: function() {
            var self = this;
            $(this.element).tabs();
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(this.options.useEditor === true){
                $(this.element).find('#wmc-new').bind("click", $.proxy(self._loadForm, self));
//                    });
//                });
                $(this.element).find('#tab-wmc-load').bind("click",function(e){
                    self._loadList();
                });
            }
        },
        
        _initAjaxForm: function(){
            var self = this;
            $(self.element).find('form').ajaxForm({
                url: self.elementUrl + 'save',
                type: 'POST',
                beforeSerialize: $.proxy(self._beforeSave, self),
                contentType: 'json',
                context: self,
                success: self._createWmcSuccess,
                error: self._createWmcError
            });
        },
        
        _loadForm: function(id) {
            var self = this;
            if(id instanceof jQuery.Event || id === null) {
                id = "";
            }
            $(self.element).find("#container-wmc-form").load(self.elementUrl+"get",{wmcid: id},function(){
                self._initAjaxForm();
            });
            
            if(id !== ""){
                this.removeWmcFromMap();
                this.loadFromId(id);
            }
        },
        
        _loadList: function() {
            if(this.options.useEditor === true){
                var self = this;
                $(this.element).find("#container-wmc-load").load(this.elementUrl + "list",function(){
                    $(self.element).find("#container-wmc-load .iconEdit").bind("click",function(){
                        self._loadForm($(this).attr("data-id"));
                        $(self.element).find('#tab-wmc-edit').click();
                        return true;
                    });
                    $(self.element).find("#container-wmc-load .iconRemove").bind("click",function(e){
                        var wmcid = $(this).attr("data-id");
                        if(Mapbender.confirm("Remove WMC ID:" + wmcid) === true){
                            var url = self.elementUrl + 'remove';
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: {
                                    wmcid: wmcid
                                },
                                dataType: 'json',
                                success: function(data){
                                    if(data.error)
                                        Mapbender.error(data.error);
                                    else {
                                        Mapbender.info(data.success);
                                        self._loadList();
                                    }
                                },
                                error:  function(data){
                                    alert("error")
                                }
                            });
                        }
                        return false;
                    });

                });
            }
        },
        
        open: function() {
            if(!this.options.useEditor){
                Mapbender.error("A WMC Editor is not available. To use a WMC Editor configure your WMC Handler.")
                return;
            }
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function(){
                    self.close();
                    $("body").mbPopup("close");                    
                });
                $("body").mbPopup('showCustom', {
                    title: me.attr("title"), 
                    showHeader: true, 
                    content: this.element, 
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: false,
                    overflow:true
                });
                $('#popupContent').css({
                    height: "500px"
                });
                $('#popup').css({
                    width: "400px"
                });
                me.show();
                self._loadList();
            }
        },
        
        close: function() {
            this.element.hide().appendTo($('body'));
            $("body").mbPopup("close");
        },

        _beforeSave: function(e) {
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            $(this.element).find('form#save-wmc').find('input#wmc_state_json').val(JSON.stringify(state));
        },

        _createWmcSuccess: function(response) {
            response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
            Mapbender.info(response.success);
            this._loadForm("");
        },
        _createWmcError: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            Mapbender.error(response.error);
        },
        
        loadFromId: function(wmcid) {
            $.ajax({
                url: this.elementUrl + 'load',
                type: 'POST',
                data: {
                    wmcid: wmcid
                },
                dataType: 'json',
                contetnType: 'json',
                context: this,
                success: this._loadFromIdSuccess,
                error: this._loadFromIdError
            });
            return false;
        },
        
        _loadWmc: function(wmcid, state){
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if(widget.length == 1) {
                widget = widget[0];
            } else {
                widget = widget[1];
            }
            var model = target[widget]("getModel");
            var wmcProj = model.getProj(state.bbox.srs),
            mapProj = model.map.olMap.getProjectionObject();
            if(wmcProj === null){
                Mapbender.error('SRS "' + state.bbox.srs + '" is not supported by this application.');
            } else if(wmcProj.projCode === mapProj.projCode){
                var boundsAr = [state.bbox.minx, state.bbox.miny, state.bbox.maxx, state.bbox.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                this._addWmcToMap(wmcid, state);
            } else {
                model.changeProjection({
                    projection: wmcProj
                });
                var boundsAr = [state.bbox.minx, state.bbox.miny, state.bbox.maxx, state.bbox.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                this._addWmcToMap(wmcid, state);
            }
        },
        
        _loadFromIdSuccess: function(response, textStatus, jqXHR){
            if(response.data){
                //                var wmcstate = $.parseJSON(response.data);
                for(wmcid in response.data){
                    var state = $.parseJSON(response.data[wmcid]);
                    this._loadWmc(wmcid, state);
                }
            } else if(response.error){
                Mapbender.error(response.error);
            }
        },
        
        _loadFromIdError: function(response){
            Mapbender.error(response.error);
        },
        
        removeWmcFromMap: function() {
            if(this.sources_wmc !== null){
                var target = $('#' + this.options.target);
                var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
                if(widget.length == 1) {
                    widget = widget[0];
                } else {
                    widget = widget[1];
                }
                var model = target[widget]("getModel");
                for(wmcid in this.sources_wmc){
                    for(var i = 0; i < this.sources_wmc[wmcid].sources.length; i++){
                        var source = this.sources_wmc[wmcid].sources[i];
//                        if(!source.configuration.isBaseSource){
                            var toremove = model.createToChangeObj(source);
                            model.removeSource(toremove);
//                        }
                    }
                }
            }
        },
        
        _addWmcToMap: function(wmcid, sources){
            this.removeWmcFromMap();
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if(widget.length == 1) {
                widget = widget[0];
            } else {
                widget = widget[1];
            }
            this.sources_wmc = {};
            this.sources_wmc[wmcid] = sources;
            for(var i = 0; i < this.sources_wmc[wmcid].sources.length; i++){
                var source = this.sources_wmc[wmcid].sources[i];
//                if(!source.configuration.isBaseSource)
                    target[widget]("addSource", source);
            }
        },
        
        _destroy: $.noop
    });

})(jQuery);
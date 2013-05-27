(function($) {
    
    //    $.widget("mapbender.mbWmcHandler",$.ui.dialog, {
    $.widget("mapbender.mbWmcHandler", {
        options: {},

        elementUrl: null,
        current_wmcid: null,
        sources_wmc: null,

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
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + $(this.element).attr('id') + '/';
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
                        if(!source.configuration.isBaseSource){
                            var toremove = model.createToChangeObj(source);
                            model.removeSource(toremove);
                        }
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
                if(!source.configuration.isBaseSource)
                    target[widget]("addSource", source);
            }
        },
        
        loadFromId: function(wmcid) {
            $.ajax({
                url: this.elementUrl + 'loadfromid',
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
        
        _loadFromIdSuccess: function(response, textStatus, jqXHR){
            if(response.data){
                //                var wmcstate = $.parseJSON(response.data);
                for(wmcid in response.data){
                    var state = $.parseJSON(response.data[wmcid]);
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
                }
            } else if(response.error){
                Mapbender.error(response.error);
            }
        },
        
        _loadFromIdError: function(response){
            Mapbender.error(response.error);
        },

        _destroy: $.noop,

        open: function() {
            this._super('open');
            this._reloadIndex();

        },

        _reloadIndex: function(){
            var self = this;
            //            self.dlg.dialog('open');
            $(this.element).find("#wmceditor-load").load(this.elementUrl + "index",function(){
                $(self.element).find("#wmceditor-load .edit").bind("click",function(){
                    var $anchor = $(this);
                    var id = $.trim($anchor.parent().siblings(".id").text());
                    $(self.element).find("#wmceditor-save")
                    .load(self.elementUrl+"get",{
                        'wmcid':id
                    },function(){
                        // since there is no way to force a fileinpout to display
                        // a preset, it can't be a required field
                        $("#form_screenshot")
                        .removeAttr("required");
        
                        var id = $.trim($anchor.parent().siblings(".id").text());
                        $(self.element).find("form")
                        .append('<input name="tkid" type="hidden" value="'+ id +'" />');
                        $(self.element).find("form").ajaxForm({
                            url: self.elementUrl + 'update',
                            type: 'POST',
                            beforeSerialize: $.proxy(self._beforeSave, self),
                            context: self,
                            success: self._onSaveSuccess
        
                        });
                        $(self.element).tabs('select',0);
                    });
                    return false;
                });
                $(self.element).find("#wmceditor-load .delete").bind("click",function(){
                    var url = self.elementUrl + 'delete?wmcid=' + $(this).attr("data-id");
                    $.post(url, function(){
                        $.proxy(self._reloadIndex,self)();
                    });
                    return false;
                });
        
            });
        },

        _beforeSave: function(form, options) {
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            form.find('input#wmc_state_json').val(JSON.stringify(state));
        //            var map = $('#' + this.options.target);
        //        
        //            var projection = map.data('mbMap').map.olMap.getProjection();
        //            form.find('input#form_srs').val(projection);
        //        
        //            var extent = map.data('mbMap').map.olMap.getExtent().toBBOX();
        //            form.find('input#form_extent').val(extent);
        //                
        //                
        //            var mapcontext = $.extend({},map.data('mbMap').map.olMap);
        //            mapcontext.title = $("input#form_title").val();
        //            mapcontext.metadata = {};
        //            mapcontext.metadata.keywords = $("input#form_tags").val();
        //            mapcontext.metadata['abstract'] = $("textarea#form_description").val();
        //            var wmctext = new OpenLayers.Format.WMC().write(mapcontext);
        //            form.find('input#form_wmc').val(wmctext);
        //                
        //        
        //            var mqLayers = map.data('mbMap').map.layers();
        //            var layers = [];
        //            $.each(mqLayers, function(idx, mqLayer) {
        //                if(mqLayer.olLayer.isBaseLayer) {
        //                    return;
        //                }
        //        
        //                layers.push({
        //                    visible: mqLayer.visible(),
        //                    opacity: mqLayer.opacity(),
        //                    options: mqLayer.options
        //                });
        //            });
        //            form.find('input#form_services').val(JSON.stringify(layers));
        },

        _save: function(event) {
            //            this.dlg.find('input,textarea').each(function() {
            //                window.console && console.log(arguments);
            //            });
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            $(event.target).find('input#wmc_state_json').val(JSON.stringify(state));
            var values = $(event.target).serialize();
            $.ajax({
                url: this.elementUrl + 'save',
                type: 'POST',
                data: values,
                dataType: 'json',
                context: this,
                success: this._createWmcSuccess,
                error: this._createWmcError
            });
            return false;
        },

        _reset: function(){
        //            this._reloadIndex();
        //            this.dlg.dialog('close');
        },
        _createWmcSuccess: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            alert('Themenkarte gespeichert mit der id=' + response);
        },
        _createWmcError: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            alert('ERROR: Themenkarte gespeichert mit der id=' + response);
        }
    });

})(jQuery);
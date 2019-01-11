(function($){
    $.widget("mapbender.mbSuggestMap", {
        options: {},
        elementUrl: null,
        _create: function(){
            this.a = this.alert;
            this.element.hide().appendTo($('body'));
            if(!Mapbender.checkTarget("mbSuggestMap", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(){
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if(typeof this.options.load !== 'undefined'
                    && typeof this.options.load.stateid !== 'undefined'){
                this._getState(this.options.load.stateid, "state");
            }
            this.element.find('ul li').bind("click", $.proxy(self._suggestMap, self));
        },
        _getState: function(id){
            $.ajax({
                url: this.elementUrl + 'load',
                type: 'POST',
                data: {
                    _id: id
                },
                dataType: 'json',
                contetnType: 'json',
                context: this,
                success: this._getStateSuccess,
                error: this._getStateError
            });
            return false;
        },
        _getStateSuccess: function(response, textStatus, jqXHR){
            if(response.data){
                for(stateid in response.data){
                    var state;
                    if(response.data[stateid])
                        state = response.data[stateid]
                    else
                        state = $.parseJSON(response.data[stateid]);
                    if(!state.window)
                        state = $.parseJSON(state);
                    this._addToMap(stateid, state);
                }
            }else if(response.error){
                Mapbender.error(response.error);
            }
        },
        _getStateError: function(response){
            Mapbender.error(response);
        },
        _addToMap: function(wmcid, state){
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if(widget.length == 1){
                widget = widget[0];
            }else{
                widget = widget[1];
            }
            var model = target[widget]("getModel");
            var wmcProj = model.getProj(state.extent.srs),
                    mapProj = model.map.olMap.getProjectionObject();
            if(wmcProj === null){
                Mapbender.error(Mapbender.trans("mb.wmc.element.suggestmap.error_srs", {"srs": state.extent.srs}));
            }else if(wmcProj.projCode === mapProj.projCode){
                var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                target[widget]("removeSources", {});
                this._addStateToMap(wmcid, state);
            }else{
                model.changeProjection({
                    projection: wmcProj
                });
                var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                target[widget]("removeSources", {});
                this._addStateToMap(wmcid, state);
            }
        },
        _addStateToMap: function(wmcid, sources){
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if(widget.length == 1){
                widget = widget[0];
            }else{
                widget = widget[1];
            }
            this.sources_wmc = {};
            this.sources_wmc[wmcid] = sources;
            for(var i = 0; i < this.sources_wmc[wmcid].sources.length; i++){
                var source = this.sources_wmc[wmcid].sources[i];
                if(!source.configuration.isBaseSource || (source.configuration.isBaseSource && !this.options.keepBaseSources)){
                    target[widget]("addSource", source);
                }
            }
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: false,
                    cssClass: 'mb-element-suggestmap-popup',
                    content: [$.ajax({
                            url: self.elementUrl + 'content',
                            complete: function(data){
                                $('ul li', self.popup.$element).bind("click", $.proxy(self._suggestMap, self));
                            }})],
                    destroyOnClose: true,
                    width: 350,
                    buttons: {
                        'ok': {
                            label: Mapbender.trans("mb.wmc.element.suggestmap.popup.btn.ok"),
                            cssClass: 'button right',
                            callback: function(){
                                self.close();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
            }else{
                this.popup.open($.ajax({url: self.elementUrl + 'content'}));
            }
        },
        _suggestState: function(callback){
            var self = this;
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var state = map.getMapState();
            var stateSer = JSON.stringify(state);
            $.ajax({
                url: self.elementUrl + 'state',
                type: 'POST',
                data: {
                    state: stateSer
                },
                dataType: 'json',
                contetnType: 'json',
                context: self,
                success: function(response, textStatus, jqXHR){
                    if(response.id){
                        var help = document.location.href.split("?");
                        var url = help[0];
                        url = url.replace(/#/gi, '') + "?stateid=" + response.id;
                        callback(url);
                    }else if(response.error){
                        Mapbender.error(Mapbender.trans(response.error));
                    }
                },
                error: self._suggestStateError
            });
            return false;
        },
        _suggestMap: function(e){
            var self = this;
            var type = $(e.delegateTarget).attr('id');
            if(type === 'suggestmap-email'){
                this._suggestState($.proxy(self._suggestEmail, self));
            }else if(type === 'suggestmap-facebook'){
                this._suggestState($.proxy(self._suggestFacebook, self));
            }else if(type === 'suggestmap-twitter'){
                this._suggestState($.proxy(self._suggestTwitter, self));
            }else if(type === 'suggestmap-googleplus'){
                this._suggestState($.proxy(self._suggestGooglePlus, self));
            }
        },
        _suggestEmail: function(url){
            if(url){
                Mapbender.SMC.callEmail(this.element.attr("title"), url);
            }
        },
        _suggestTwitter: function(url){
            if(url){
                Mapbender.SMC.callTwitter(this.element.attr("title"), url);
            }
        },
        _suggestFacebook: function(url){
            if(url){
                Mapbender.SMC.callFacebook(this.element.attr("title"), url);
            }
        },
        _suggestGooglePlus: function(url){
            if(url){
                Mapbender.SMC.callGooglePlus(this.element.attr("title"), url);
            }
        },
        _suggestStateError: function(response){
            Mapbender.error(Mapbender.trans(response));
        },
        _destroy: $.noop
    });

})(jQuery);

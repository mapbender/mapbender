(function($){
    $.widget("mapbender.mbSuggestMap", {
        options: {},
        elementUrl: null,
        mbMap: null,
        _create: function(){
            this.element.hide().appendTo($('body'));
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbSuggestMap", self.options.target);
            });
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if(typeof this.options.load !== 'undefined'
                    && typeof this.options.load.stateid !== 'undefined'){
                this._getState(this.options.load.stateid, "state");
            }
            this.element.find('ul li').bind("click", $.proxy(this._suggestMap, this));
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
            if (response.data){
                for (var stateid in response.data){
                    var state = response.data[stateid];
                    if (!state.window) {
                        state = $.parseJSON(state);
                    }
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
            var mapProj = this.mbMap.map.olMap.getProjectionObject();
            this.mbMap.removeSources({});
            if (state.extent.srs !== mapProj.projCode) {
                try {
                    this.mbMap.changeProjection(state.extent.srs);
                } catch (e) {
                    Mapbender.error(Mapbender.trans(Mapbender.trans("mb.wmc.element.wmchandler.error_srs", {"srs": state.extent.srs})));
                    console.error("Projection change failed", e);
                    return;
                }
            }
            var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
            this.mbMap.zoomToExtent(OpenLayers.Bounds.fromArray(boundsAr));
            this._addStateToMap(wmcid, state);
        },
        _addStateToMap: function(wmcid, state){
            for(var i = 0; i < state.sources.length; i++){
                var source = state.sources[i];
                if(!source.configuration.isBaseSource || (source.configuration.isBaseSource && !this.options.keepBaseSources)){
                    this.mbMap.addSource(source);
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
            var state = this.mbMap.getMapState();
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

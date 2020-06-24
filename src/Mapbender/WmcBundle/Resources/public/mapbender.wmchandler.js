var Mapbender = Mapbender || {};
Mapbender.WmcHandler = function(mapWidget, options){
    if(!options)
        options = {};
    this.mapWidget = mapWidget;
    this.options = $.extend({}, {keepSources: 'no', keepExtent: false}, options);

    this.loadFromId = function(url, id){
        $.ajax({
            url: url,
            type: 'POST',
            data: {_id: id},
            dataType: 'json',
            contetnType: 'json',
            context: this,
            success: this._loadFromIdSuccess,
            error: this._loadError
        });
        return false;
    };
    this._loadFromIdSuccess = function(response, textStatus, jqXHR){
        if(response.data){
            for (var stateid in response.data){
                var state = $.parseJSON(response.data[stateid]);
                if(!state.window)
                    state = $.parseJSON(state);
                this.addToMap(stateid, state);
            }
        }else if(response.error){
            Mapbender.error(response.error);
        }
    };
    this._loadError = function(error){
        Mapbender.error(error);
    };
    this.addToMap = function(wmcid, state){
        var model = this.mapWidget.getModel();
        var mapProj = model.map.olMap.getProjectionObject();
        if (this.options.keepSources !== 'allsources') {
            for (var i = 0; i < model.sourceTree.length; i++) {
                var source = model.sourceTree[i];
                if (!source.configuration.isBaseSource || this.options.keepSources !== 'basesources') {
                    model.removeSource(source);
                }
            }
        }
        if (state.extent.srs !== mapProj.projCode) {
            try {
                this.mapWidget.changeProjection(state.extent.srs);
            } catch (e) {
                Mapbender.error(Mapbender.trans(Mapbender.trans("mb.wmc.element.wmchandler.error_srs", {"srs": state.extent.srs})));
                console.error("Projection change failed", e);
                return;
            }
        }
        if(!this.options.keepExtent){
            var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
            this.mapWidget.getModel().setExtent(boundsAr);
        }
        this._addWmcToMap(state.sources);
    };
    
    this._addWmcToMap = function(sources){
        for(var i = 0; i < sources.length; i++){
            var source = sources[i];
            if(!source.configuration.isBaseSource || (source.configuration.isBaseSource && this.options.keepSources !== 'basesources')){
                this.mapWidget.addSourceFromConfig(source, true);
            }
        }
    };
};

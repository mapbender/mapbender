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
    this.loadFromUrl = function(url, wmcurl){
        $.ajax({
            url: url,
            type: 'POST',
            data: {_url: wmcurl},
            dataType: 'json',
            contetnType: 'json',
            context: this,
            success: this._loadFromUrlSuccess,
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
    this._loadFromUrlSuccess = function(response, textStatus, jqXHR){
        if(response.success){
            for (var stateid in response.success){
                this.addToMap(stateid, response.success[stateid]);
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
        var mapProj = model.map.olMap.getProjectionObject(),
            toKeepSources = {};
        var source, i;
        if (this.options.keepSources === 'basesources'){
            for (i = 0; i < model.sourceTree.length; i++){
                source = model.sourceTree[i];
                if(source.configuration.isBaseSource)
                    toKeepSources[source.id] = {sourceId: source.id};
            }
        } else if (this.options.keepSources === 'allsources'){
            for (i = 0; i < model.sourceTree.length; i++){
                source = model.sourceTree[i];
                toKeepSources[source.id] = {sourceId: source.id};
            }
        }
        this.mapWidget.removeSources(toKeepSources);
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
            this.mapWidget.zoomToExtent(OpenLayers.Bounds.fromArray(boundsAr), true);
        }
        this._addWmcToMap(state.sources);
    };
    
    this.removeFromMap = function(){
        this.mapWidget.fireModelEvent({
            name: 'contextremovestart',
            value: null
        });
        var model = this.mapWidget.getModel();
        if (this.options.keepSources !== 'allsources') {
            var toKeepSources = {};
            if (this.options.keepSources === 'basesources') {
                for(var i = 0; i < model.sourceTree.length; i++){
                    var source = model.sourceTree[i];
                    if(source.configuration.isBaseSource)
                        toKeepSources[source.id] = {sourceId: source.id};
                }
            }
            this.mapWidget.removeSources(toKeepSources);
        }

        this.mapWidget.fireModelEvent({
            name: 'contextremoveend',
            value: null
        });
    };

    this._addWmcToMap = function(sources){
        this.mapWidget.fireModelEvent({
            name: 'contextaddstart',
            value: null
        });
        for(var i = 0; i < sources.length; i++){
            var source = sources[i];
            if(!source.configuration.isBaseSource || (source.configuration.isBaseSource && this.options.keepSources !== 'basesources')){
                source.configuration.status = source.configuration.status ? source.configuration.status : 'ok';
                this.mapWidget.addSource(source, true);
            }
        }
        this.mapWidget.fireModelEvent({
            name: 'contextaddend',
            value: null
        });
    };
};
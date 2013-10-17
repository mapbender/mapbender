(function($){
    $.widget("mapbender.mbLayertree", {
        options: {
            title: 'Layertree',
            autoOpen: false,
            target: null
        },
        model: null,
        dlg: null,
        template: null,
        layerconf: null,
        popup: null,
        created: false,
        consts: {
            source: "source",
            root: "root",
            group: "group",
            simple: "simple"
        },
        _create: function(){
            if(!Mapbender.checkTarget("mbLayertree", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            this.options.layerMenu= false;
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.template = $('li', this.element).remove();
            this.model = $("#" + self.options.target).data("mapbenderMbMap").getModel();
            if(this.options.type === 'element'){
                this._createTree();
            } else if(this.options.type === 'dialog' && new Boolean(self.options.autoOpen).valueOf() === true){
                this.open();
            }
            this._trigger('ready');
            this._ready();
        },
        _createTree: function(){
            var self = this;
            var sources = this.model.getSources();
            if(this.created)
                this._unSortable();
            for(var i = (sources.length - 1); i > -1; i--){
                if(!sources[i].configuration.isBaseSource || (sources[i].configuration.isBaseSource && this.options.showBaseSource)){
                    if(this.options.displaytype === "tree"){
                        var li_s = this._createSourceTree(sources[i], sources[i], this.model.getScale());
                        $("ul.layers:first", this.element).append(li_s);
                    }else if(this.options.displaytype === "list"){
                        var li_s = self._createSourceList(sources[i], sources[i], this.model.getScale());
                        $("ul.layers:first", this.element).append($(li_s));
                    }
                }
            }
            
            this._reset();
            
            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            
            this.created = true;
        },
        _reset: function(){
            this._resetEvents();
            this._resetSortable();
            this._resetCheckboxes();
            this._setSourcesCount();
        },
        _createEvents: function(){
            var self = this;
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('click',  '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.on('click',  '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.on('click',  '.iconRemove', $.proxy(self._removeSource, self));
            this.element.on('click',  '.layer-menu-btn', $.proxy(self._toggleMenu, self));
        },
        _removeEvents: function(){
            var self = this;
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.off('click',  '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.off('click',  '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.off('click',  '.iconRemove', $.proxy(self._removeSource, self));
            this.element.off('click',  '.layer-menu-btn', $.proxy(self._toggleMenu, self));
    
        },
        _resetEvents: function(){
            this._removeEvents();
            this._createEvents();
        },
                
        _resetCheckboxes: function(){
            var self = this;
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            if(initCheckbox){
                $('.checkbox', self.element).each(function() {
                    initCheckbox.call(this);
                });
            }
        },
        _resetSortable: function(){
//            this._unSortable();
            this._createSortable();
        },
        _unSortable: function(){
//            $("ul.layers", this.element).each(function(){
//                var that = this;
//                if($(that).sortable())
//                    $(that).sortable('destroy');
//            });
        },
        _createSortable: function(){
            var self = this;
            $("ul.layers", this.element).each(function(){
                var that = this;
                $(that).sortable({
                    axis: 'y',
                    items: "> li:not(.notreorder)",
                    distance: 6,
                    cursor: "move",
                    update: function(event, ui){
                        var elm = $(ui.item), beforeObj = null, afterObj = null, tomoveObj = null;
                        if(elm.prev().length !== 0){
                            var beforeEl = $(elm.prev()[0]);
                            var bid = $(beforeEl).attr('data-sourceid');
                            if(!bid)
                                bid = $(beforeEl).parents('li[data-sourceid]:first').attr('data-sourceid');
                            beforeObj = { sourceIdx: {id: bid}, layerIdx: {id: $(beforeEl).attr("data-id")}};
                        }
                        if(elm.next().length !== 0){
                            var afterEl = $(elm.next()[0]);
                            var aid = $(afterEl).attr('data-sourceid');
                            if(!aid)
                                aid = $(afterEl).parents('li[data-sourceid]:first').attr('data-sourceid');
                            afterObj = { sourceIdx: {id: aid}, layerIdx: {id: $(afterEl).attr("data-id")}};
                        }
                        var id = $(elm).attr('data-sourceid');
                        if(!id)
                            id = $(elm).parents('li[data-sourceid]:first').attr('data-sourceid');
                        tomoveObj = { sourceIdx: {id: id}};
                        if($(ui.item).attr("data-type") !== self.consts.root){
                            tomoveObj['layerIdx'] = {id: $(ui.item).attr("data-id")};
                        }
                        /* "before" at layerTree is "after" at model.sourceTree */
                        self.model.changeSource({ change: { move: { tomove: tomoveObj, before: afterObj, after: beforeObj }}});
                    }
                });
            });
        },
        _createSourceTree: function(source, sourceEl, scale, type, isroot){
            var self = this;
            if(sourceEl.type){ // source
                var li = "";
                sourceEl.layers = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li = this._createSourceTree(source, sourceEl.configuration.children[i], scale, sourceEl.type, true);
                }
            }else{
                var config = this._getNodeProporties(sourceEl);
                var li = this.template.clone();
                li.removeClass('hide-elm');
                li.attr('data-id', sourceEl.options.id);
                li.attr('data-sourceid', source.id)
                var nodeType = this._getNodeType(sourceEl, isroot);
                li.attr('data-type', nodeType);
                if(nodeType === this.consts.root || nodeType === this.consts.group){
                    if(nodeType === this.consts.root){
                        li.addClass("serviceContainer");
                    }else if(nodeType === this.consts.group){
                        li.addClass("groupContainer");
                    }
                    li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    /** @TODO add check config.toggleable if config.toggleable !== true -> '.iconFolder' has no folder and no event, sublayers are unvisible */
                    if(config.toggle === true)
                        li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    else
                        li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");// todo
                }
                li.addClass(config.reorder);
                li.find('.layer-state').attr('title', config.visibility.tooltip);
                li.find('input.layer-selected').attr('checked', config.selected ? true : false);
                if(!config.selectable)
                    li.find('input.layer-selected').attr('disabled', 'disabled');
                li.find('input.layer-info').attr('checked', config.info ? 'checked' : null);
                if(!config.infoable)
                    li.find('input.layer-info').attr('disabled', 'disabled');
                li.find('.layer-title').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                if(config.toggleable)
                    li.find('.layer-title').addClass('toggleable');
                if(!this.options.layerMenu){
                    li.find('.layer-menu-btn').remove();
                }else{
                    var menu = li.find('.layer-menu:first');
                    if(!sourceEl.options.legend){
                        menu.find('.layer-legend').addClass('btn-disabled');
                    }else{
                        menu.find('.layer-legend').bind("click", function(e){
                            e.stopPropagation();
                            self._showLegend(sourceEl);
                        });
                    }
                    menu.find('.layer-kmlexport').bind("click", function(e){
                        e.stopPropagation();
                        self._exportKml(sourceEl);
                    });
                    if(sourceEl.options.maxScale !== null){
                        menu.find('.layer-zoom').addClass('btn-disabled');
                    }else{
                        menu.find('.layer-zoom').bind("click", function(e){
                            e.stopPropagation();
                            self._zoomToLayer(sourceEl);
                        });
                    }
                    menu.find('.layer-metadata').bind("click", function(e){
                        e.stopPropagation();
                        self._showMetadata(sourceEl);
                    });

                }
                if(!this.options.layerRemove)
                    li.find('.iconRemove').remove();
                if(sourceEl.children){
                    li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                    if(config.toggle){
                        li.find('ul:first').addClass("closed");
                    }
                    for(var j = sourceEl.children.length; j > 0; j--){
                        li.find('ul:first').append(this._createSourceTree(source, sourceEl.children[j - 1], scale, type, false));
                    }
                }else{
                    li.find('ul:first').remove();
                }
            }
            return li;
        },
        _createTreeNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found){
            if(sourceEl.type){ // source
                var li = "";
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li = this._createTreeNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent, sourceEl.type, true, false);
                }
            }else{
                if(layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found){
                    found = true;
                    var config = this._getNodeProporties(sourceEl);
                    var li = this.template.clone();
                    li.removeClass('hide-elm');
                    li.attr('data-id', sourceEl.options.id);
                    isroot ? li.attr('data-sourceid', source.id) : li.removeAttr('data-sourceid');
                    var nodeType = this._getNodeType(sourceEl, isroot);
                    li.attr('data-type', nodeType);

                    if(nodeType == this.consts.root){
                        li.addClass("serviceContainer showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    }else if(nodeType == this.consts.group){
                        li.addClass("groupContainer showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    }
                    li.addClass(config.reorder);
                    li.find('.layer-state').attr('title', config.visibility.tooltip);
                    li.find('input.layer-selected').attr('checked', config.selected ? 'checked' : null);
                    if(!config.selectable)
                        li.find('input.layer-selected').attr('disabled', 'disabled');
                    li.find('input.layer-info').attr('checked', config.info ? 'checked' : null);
                    if(!config.infoable)
                        li.find('input.layer-info').attr('disabled', 'disabled');
                    li.find('.layer-title').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                    if(config.toggleable)
                        li.find('.layer-title').addClass('toggleable');
                    if(!this.options.layerMenu){
                        li.find('.layer-menu-btn').remove();
                    }else{
                        var menu = li.find('.layer-menu:first');
                        if(!sourceEl.options.legend){
                            menu.find('.layer-legend').addClass('btn-disabled');
                        }else{
                            menu.find('.layer-legend').bind("click", function(e){
                                e.stopPropagation();
                                self._showLegend(sourceEl);
                            });
                        }
                        menu.find('.layer-kmlexport').bind("click", function(e){
                            e.stopPropagation();
                            self._exportKml(sourceEl);
                        });
                        if(sourceEl.options.maxScale !== null){
                            menu.find('.layer-zoom').addClass('btn-disabled');
                        }else{
                            menu.find('.layer-zoom').bind("click", function(e){
                                e.stopPropagation();
                                self._zoomToLayer(sourceEl);
                            });
                        }
                        menu.find('.layer-metadata').bind("click", function(e){
                            e.stopPropagation();
                            self._showMetadata(sourceEl);
                        });
                    }
                    if(!this.options.layerRemove)
                        li.find('.iconRemove').remove();
                    if(sourceEl.children){
                        li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                        if(config.toggle){
                            li.find('ul:first').addClass("closed");
                        }
                        for(var j = 0; j < sourceEl.children.length; j++){
                            li.find('ul:first').append(this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found));
                        }
                    }else{
                        li.find('ul').remove();
                    }
                    found = false;
                    return li;
                }
                if(sourceEl.children){
                    parent = parent.find('li[data-id="' + sourceEl.options.id + '"]:first');
                    for(var j = 0; j < sourceEl.children.length; j++){
                        var li = this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        if(li !== null){
                            if(sourceEl.children.length === 1){
                                parent.add(li);
                            }else if(j === 0){
                                parent.find('li[data-id="' + sourceEl.children[j + 1].options.id + '"]:first').after(li);
                            }else{
                                parent.find('li[data-id="' + sourceEl.children[j - 1].options.id + '"]:first').before(li);
                            }
                        }
                    }
                }
            }
            return null;
        },
        _onSourceAdded: function(event, options){
            if(!this.created || !options.added) return;
            var added = options.added;
            //window.console && console.log("layertree _onSourceAdded");
            var before = added.after, after = added.before;
            if(added.source.configuration.baseSource && !this.options.showBaseSource){
                return;
            }
            if(this.options.displaytype === "tree"){
                var hasChildren = false;
                for(layerid in added.children){
                    this._createTreeNode(added.source, added.source, this.model.getScale(), added.children[layerid], $(this.element).find('ul.layers:first'));
                }
                if(!hasChildren){
                    var li_s = this._createSourceTree(added.source, added.source, this.model.getScale());
                    var first_li = $(this.element).find('ul.layers:first li:first');
                    if(first_li && first_li.length !== 0){
                        first_li.before(li_s);
                    }else{
                        $(this.element).find('ul.layers:first').append($(li_s));
                    }
                }
            }else if(this.options.displaytype === "list"){
                var hasChildren = false;
                for(layerid in added.children){
                    hasChildren = true;
                    if($(!this.element).find('ul.layers:first li[data-id="' + added.layerId + '"]')){
                        this._createListNode(added.source, added.source, this.model.getScale(), added.children[layerid], $(this.element).find('ul.layers:first'));
                    }
                }
                if(!hasChildren){
                    $("ul.layers").each(function(){
                        var that = this;
                        $(that).sortable("destroy");
                    });
                    var li_s = this._createSourceList(added.source, added.source, this.model.getScale());
                    if(before && before.layerId){
                        $(this.element).find('ul.layers:first li[data-id="' + before.layerId + '"]').after(li_s);
                    }else if(after && after.layerId){
                        $(this.element).find('ul.layers:first li[data-id="' + after.layerId + '"]').before(li_s);
                    }else if(!this.options.showBaseSource && after.source.configuration.isBaseSource){
                        $(this.element).find('ul.layers:first').append(li_s);
                    }else if(!after.source.configuration.isBaseSource){
                        $(this.element).find('ul.layers:first').append(li_s);
                    }
                }
            }
            this._reset();
        },
        _onSourceChanged: function(event, options){
            if(options.changed && options.changed.options){
                this._optionsChanged(options.changed);
            }else if(options.changed && options.changed.layerRemoved){
                this._removeLayer(options.changed);
            }
        },
        _optionsChanged: function(changed){
            var source = this.model.getSource(changed.options.sourceIdx);
            if(changed.options.children){
                for(var layerId in changed.options.children){
                    var changedEl = changed.options.children[layerId];
                    var lif = $(this.element).find('li[data-id="' + layerId + '"][data-sourceid="' + source.id + '"]:first');
                    /* check selected */
                    var chk_selected = lif.find('input[name="selected"]:first');
                    if(changedEl.options){
                        if(changedEl.options.treeOptions.selected !== chk_selected.is(':checked'))
                            chk_selected.get(0).checked = changedEl.options.treeOptions.selected;
                        if(chk_selected.is(':checked'))
                            chk_selected.parents('.checkWrapper:first').addClass("iconCheckboxActive");
                        else
                            chk_selected.parents('.checkWrapper:first').removeClass("iconCheckboxActive");
                        if(changedEl.options.treeOptions.info !== lif.find('input[name="info"]:first').is(':checked'))
                            lif.find('input[name="info"]:first').get(0).checked = changedEl.options.treeOptions.info;
                    }
                    /* check visibility */
                    if(changedEl.state.visibility){
                        lif.removeClass("invisible").find('span.layer-state:first').attr("title", "");
                    }else{
                        if(chk_selected.is(':checked'))
                            lif.addClass("invisible").find('span.layer-state:first').attr("title", changedEl.state.outOfScale ? "outOfScale" : "parent invisible");
                        else
                            lif.addClass("invisible").find('span.layer-state:first').attr("title", "");
                    }
                }
            }
        },
        _removeLayer: function(changed){
            var self = this;
            if(changed && changed.sourceIdx && changed.layerRemoved){
                var source = this.model.getSource(changed.sourceIdx);
                    // TODO check source id ?
                $('ul.layers:first li[data-id="' + changed.layerRemoved.layer.options.id + '"]', self.element).remove();
            }
        },
        _onSourceRemoved: function(event, removed){
            if(removed && removed.source && removed.source.id){
                $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element).remove();
                this._setSourcesCount();
            }
        },
        _onSourceLoadStart: function(event, option){ // sets "loading" for layers
            //window.console && console.log("layertree _onSourceLoadStart");
            if(!option.source)
                return;
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="' + source.id + '"]');
                if(source_li.find('input.layer-selected:first').is(':checked')
                        && !source_li.hasClass('invisible')){
                    source_li.addClass('loading');
                    source_li.find('li').each(function(idx, el){
                        var li_el = $(el);
                        if(li_el.find('input.layer-selected:first').is(':checked')
                                && !li_el.hasClass('invisible')){
                            li_el.addClass('loading');
                        }
                    });
                }
            }else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="' + source.id + '"]').each(function(idx, elm){
                    if($(elm).find('input[name="selected"]:first').is(':checked')
                            && !$(elm).hasClass('invisible')){
                        $(elm).removeClass('error').addClass("loading");
                    }
                });
            }
        },
        _onSourceLoadEnd: function(event, option){ // removes "loading" from layers
            if(!option.source)
                return;
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="' + source.id + '"]');
                source_li.removeClass('loading').removeClass('error').find('span.layer-state:first').attr("title", "");
                source_li.find('li').each(function(idx, el){
                    $(el).removeClass('loading').removeClass('error').find('span.layer-state:first').attr("title", "");
                });
            }else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="' + source.id + '"]').removeClass('loading');
            }
        },
        _onSourceLoadError: function(event, option){ // sets "error" for layers
            if(!option.source)
                return;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="' + option.source.id + '"]');
                source_li.removeClass('loading').removeClass('invisible').addClass('error').find('span.layer-state:first').attr("title", option.error.details);
                source_li.find('li').each(function(idx, el){
                    $(el).removeClass('loading').removeClass('invisible').addClass('error').find('span.layer-state:first').attr("title", option.error.details);
                });
            }else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="' + option.source.id + '"]').each(function(idx, elm){
                    if($(elm).find('input[name="selected"]:first').is(':checked')){
                        $(elm).removeClass('invisible').removeClass('loading').addClass('error');
                        $(elm).find('span.state').attr({
                            title: option.error.details
                        });
                    }
                });
            }
        },
        _subStringText: function(text){
            if(text.length <= this.options.titlemaxlength){
                return text;
            }else{
                for(var i = this.options.titlemaxlength; i > 0; i--){
                    if(text[i] === " "){
                        text = text.substring(0, i) + "...";
                        break;
                    }
                }
                if(text.length < 2 || text.length > this.options.titlemaxlength + 3)
                    return text.substring(0, this.options.titlemaxlength) + "...";
                else
                    return text;
            }
        },
        _getNodeType: function(node, isroot){
            if(node.children && isroot){
                return this.consts.root;
            }else if(node.children){
                return this.consts.group;
            }else{
                return this.consts.simple;
            }
        },
        _getNodeProporties: function(nodeConfig){
            var conf = {
                selected: nodeConfig.options.treeOptions.selected,
                selectable: nodeConfig.options.treeOptions.allow.selected,
                info: nodeConfig.options.treeOptions.info,
                infoable: nodeConfig.options.treeOptions.allow.info,
                reorderable: nodeConfig.options.treeOptions.allow.reorder
            };
            if(nodeConfig.children){
                conf["toggle"] = nodeConfig.options.treeOptions.toggle;
                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle;
            }else{
                conf["toggle"] = null;
                conf["toggleable"] = null;
            }

            if(nodeConfig.state.outOfScale){
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: "outOfScale"
                };
            }else if(nodeConfig.state.outOfBounds){
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: "outOfBounds"
                };
            }else if(!nodeConfig.state.visibility){
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: "parent invisible"
                };
            }else{
                conf["visibility"] = {
                    state: "",
                    tooltip: ""
                };
            }
            return conf;
        },
        _toggleContent: function(e){
            var me = $(e.target);
            if(me.hasClass("iconFolderActive")){
                me.removeClass("iconFolderActive");
                me.parents('li:first').removeClass("showLeaves");
            }else{
                me.addClass("iconFolderActive");
                me.parents('li:first').addClass("showLeaves");
            }
            return false;
        },
        _toggleSelected: function(e){
            var li = $(e.target).parents('li:first');
            var tochange = {sourceIdx: {id: li.attr('data-sourceid')}, options: {}};
            if(li.attr('data-type') === this.consts.root){
                tochange.options = {configuration: {options: {visibility: $(e.target).is(':checked')}}};
            }else{
                tochange.options = {children: {}};
                tochange.options.children[li.attr('data-id')] = {options: {treeOptions: {selected: $(e.target).is(':checked')}}};
            }
            tochange.options['type'] = 'selected';
            this.model.changeSource({ change: tochange});
        },
        _toggleInfo: function(e){
            var li = $(e.target).parents('li:first');
            var tochange = {sourceIdx: {id: li.attr('data-sourceid')}, options: {children: {}, type: 'info'}};
            tochange.options.children[li.attr('data-id')] = {options: {treeOptions: {info: $(e.target).is(':checked')}}};
            this.model.changeSource({ change: tochange});
        },
        _toggleMenu: function(e){},
        _removeSource: function(e){
            var layer_type = $(e.target).parents("li:first").attr("data-type");
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            if(sourceId && layer_type && this.consts.root === layer_type){ // remove source
                this.model.removeSource({remove:{ sourceIdx: {id: sourceId}}});
            } else if(sourceId && layer_type && this.consts.group === layer_type){// remove group layer
                var layer_id = $(e.target).parents("li:first").attr("data-id");
                this.model.changeSource({ change: { layerRemove: { sourceIdx: {id: sourceId}, layer: {options: {id: layer_id}}}}});
            } else if(sourceId && layer_type && this.consts.simple === layer_type){// remove group layer
                var layer_id = $(e.target).parents("li:first").attr("data-id");
                this.model.changeSource({ change: { layerRemove: { sourceIdx: {id: sourceId}, layer: {options: {id: layer_id}}}}});
            }
            this._setSourcesCount();
        },
        _showLegend: function(elm){
        },
        _exportKml: function(elm){
        },
        _zoomToLayer: function(elm){
        },
        _showMetadata: function(elm){
        },
        _setSourcesCount: function(){
            var countObj = {};
            $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm){
                countObj[$(elm).attr('data-sourceid')] = true;
            });
            var num = 0;
            for(s in countObj)
                num++;
            $(this.element).find('#counter').text(num);
        },
        _removeAllSources: function(e){
            var self = this;
            if(confirm("Really all sources delete?")){
                $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm){
                    var sourceId = $(elm).attr('data-sourceid');
                    self.model.removeSource({remove:{ sourceIdx: {id: sourceId}}});
                });
            }
            this._setSourcesCount();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        /**
         * Opens a dialog with a layertree (if options.type == 'dialog')
         */
        open: function(callback){
            this.callback = callback ? callback : null;
            if(this.options.type === 'dialog'){
                var self = this;
                if(!this.popup || !this.popup.$element){
                    this._createTree();
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('title'),
                        modal: false,
                        draggable: true,
                        closeButton: false,
                        closeOnPopupCloseClick: false,
                        closeOnESC: false,
                        content: [self.element.show()],
                        destroyOnClose: true,
                        width: 350,
                        buttons: {
                            'ok': {
                                label: 'Close',
                                cssClass: 'button right',
                                callback: function(){
                                    self.close();
                                }
                            }
                        }
                    });
                    this._reset();
                } else {
                    this._reset();
                    this.popup.open();
                }
            }
        },
        /**
         * closes a dialog with a layertree (if options.type == 'dialog')
         */
        close: function(){
            if(this.options.type === 'dialog'){
                if(this.popup){
                    $("ul.layers:first", this.element).empty();
                    $(this.element).hide().appendTo("body");
                    this._unSortable();
                    this.created = false;
                    if(this.popup.$element){
                        this.popup.destroy();
                    }
                    this.popup = null;
                }
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);
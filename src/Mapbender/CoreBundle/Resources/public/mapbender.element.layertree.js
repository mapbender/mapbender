(function($) {

    $.widget("mapbender.mbLayertree", {
        options: {
            title: 'Layertree',
            autoOpen: false,
            target: null
        },
        model: null,
        dlg: null,
        template: null,
        //        elementUrl: null,
        layerconf : null,
        consts: {
            source: "source", 
            root: "root", 
            group: "group", 
            simple: "simple"
        },

        _create: function() {
            if(!Mapbender.checkTarget("mbLayertree", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
    
        _setup: function(){
            var self = this;
            if(self.options.type === 'dialog' && new Boolean(self.options.autoOpen).valueOf() === true){
                self.open();
            }
            var me = this.element;
            this.template = $(me).find('li').remove();
            this.model = $("#" + self.options.target).data("mbMap").getModel();
            var sources = this.model.getSources();
            for(var i = (sources.length - 1); i > -1; i--){
                if(!sources[i].configuration.isBaseSource || (sources[i].configuration.isBaseSource && this.options.showBaseSource)){
                    if(this.options.displaytype === "tree"){
                        var li_s = this._createSourceTree(sources[i], sources[i], this.model.getScale());
                        //                        me.find("ul.layers:first").append($(li_s));
                        me.find("ul.layers:first").append(li_s);
                    } else if(this.options.displaytype === "list"){
                        var li_s = self._createSourceList(sources[i], sources[i], this.model.getScale());
                        me.find("ul.layers:first").append($(li_s));
                    }
                }
            }
            this._setSourcesCount();
            
            me.find(".layer-opacity-slider").slider();
            
            this._createSortable();

            $(this.element).find('li input[name="selected"]').live("change", $.proxy(self._toggleSelected, self));
            $(this.element).find('li input[name="info"]').live("change", $.proxy(self._toggleInfo, self));
            $(this.element).find('li span.toggleable').live("click", $.proxy(self._toggleContent, self));
            $(this.element).find('#delete-all').live("click", $.proxy(self._removeAllLayers, self));
            
            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            
            $(this.element).find('.layer-remove-btn').live("click", $.proxy(self._removeSource, self));
            $(this.element).find('.layer-menu-btn').live("click", $.proxy(self._toggleMenu, self));
            
            if(this.options.type === "dialog"){
                this._initDialog();
                if(this.options.autoOpen){
                    this.open();
                }
            }
        },
        
        _createSortable: function(){
            var self = this;
            $("ul.layers").each(function(){
                var that = this;
                $(that).sortable({
                    axis: 'y',
                    items: "> li:not(.notreorder)",
                    distance: 6,
                    cursor: "move",
                    update: function(event, ui) {
                        var elm = $(ui.item);
                        var before = null, after = null, tomove = null;
                        if(elm.prev().length !== 0){
                            var beforeEl = $(elm.prev()[0]);
                            before = {
                                source: self.model.getSource({
                                    id: $(beforeEl).attr('data-sourceid') ? $(beforeEl).attr('data-sourceid') : $(beforeEl).parents('li[data-sourceid]:first').attr('data-sourceid')
                                }), 
                                layerId: $(beforeEl).attr("data-id")
                            };
                        }
                        if(elm.next().length !== 0){
                            var afterEl = $(elm.next()[0]);
                            after = {
                                source:  self.model.getSource({
                                    id: $(afterEl).attr('data-sourceid') ? $(afterEl).attr('data-sourceid') : $(afterEl).parents('li[data-sourceid]:first').attr('data-sourceid')
                                }), 
                                layerId: $(afterEl).attr("data-id")
                            };
                        }
                        tomove = {
                            source: self.model.getSource({
                                id: $(elm).attr('data-sourceid') ? $(elm).attr('data-sourceid') : $(elm).parents('li[data-sourceid]:first').attr('data-sourceid')
                            })
                        };
                        if($(ui.item).attr("data-type") !== self.consts.root){
                            tomove['layerId'] = $(ui.item).attr("data-id");
                        }
                        var tochange = self.model.createToChangeObj(tomove.source);
                        if(tochange !== null){
                            tochange.type = {
                                layerTree: "move"
                            };
                            tochange.children.before = after;
                            tochange.children.after = before;
                            tochange.children.tomove = tomove;
                            self.model.changeSource(tochange);
                        } else {
                            $(that).sortable('cancel');
                            return;
                        }
                        
                    }
                });
            });
        },
        
        _onSourceAdded: function(event, added){
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
                    } else {
                        $(this.element).find('ul.layers:first').append($(li_s));
                    }
                }
            } else if(this.options.displaytype === "list"){
                var hasChildren = false;
                for(layerid in added.children){
                    hasChildren = true;
                    if($(!this.element).find('ul.layers:first li[data-id="'+added.layerId+'"]')){
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
                        $(this.element).find('ul.layers:first li[data-id="'+before.layerId+'"]').after(li_s);
                    } else if(after && after.layerId){
                        $(this.element).find('ul.layers:first li[data-id="'+after.layerId+'"]').before(li_s);
                    } else if(!this.options.showBaseSource && after.source.configuration.isBaseSource){
                        $(this.element).find('ul.layers:first').append(li_s);
                    } else if(!after.source.configuration.isBaseSource){
                        $(this.element).find('ul.layers:first').append(li_s);
                    }
                    this._createSortable();
                }
            }
            
            this._setSourcesCount();
        },
        
        _onSourceChanged: function(event, changed){
            window.console && console.log("mbLayertree._onSourceChanged:", changed);
            if(this.options.displaytype === "tree"){
                for(key in changed.children){
                    var changedEl = changed.children[key];
                    var lif = $(this.element).find('li[data-id="'+key+'"]:first');
                    if(changedEl.treeElm.state.visibility){
                        lif.removeClass("invisible").find('span.layer-state:first').attr("title","");
                    } else {
                        if(lif.find('input[name="selected"]:first').is(':checked')){
                            lif.addClass("invisible").find('span.layer-state:first').attr("title",changedEl.state.outOfScale ? "outOfScale" : "parent invisible");
                        } else {
                            lif.removeClass("invisible").find('span.layer-state:first').attr("title","");
                        }
                    }
                }
            } else if(this.options.displaytype === "list"){
                for(key in changed.children){
                    var changedEl = changed.children[key];
                    if(changedEl.treeElm.state.visibility){
                        $(this.element).find('li[data-sourceid="'+changed.source.id+'"][data-id="'+key+'"]').removeClass("invisible");
                        $(this.element).find('li[data-sourceid="'+changed.source.id+'"][data-id="'+key+'"] span.layer-state:first').attr("title", "");
                    } else {
                        $(this.element).find('li[data-sourceid="'+changed.source.id+'"][data-id="'+key+'"]').addClass("invisible");
                        var tooltip = changedEl.state.outOfBounds ? "outOfBounds" : changedEl.state.outOfScale ? "outOfScale" : "parent invisible or not defined?";
                        $(this.element).find('li[data-sourceid="'+changed.source.id+'"][data-id="'+key+'"] span.layer-state:first').attr("title", tooltip);
                    }
                }
            }
        },
        
        _onSourceRemoved: function(event, removed){
            var hasLayers = false;
            for(layerid in removed.children){
                hasLayers = true;
                $(this.element).find('ul.layers:first li[data-id="'+layerid+'"]').remove();
            }
            if(!hasLayers){
                $(this.element).find('ul.layers:first li[data-sourceid="'+removed.source.id+'"]').remove();
            }
            this._setSourcesCount();
        },
        
        _onSourceLoadStart: function(event, option){ // sets "loading" for layers
            window.console && console.log("mbLayertree._onSourceLoadStart:", event);
            if(!option.source)
                return;
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+source.id+'"]');
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
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+source.id+'"]').each(function(idx, elm){
                    if($(elm).find('input[name="selected"]:first').is(':checked')
                        && !$(elm).hasClass('invisible')){
                        $(elm).removeClass('error').addClass("loading");
                    }
                });
            }
        },
        
        _onSourceLoadEnd: function(event, option){ // removes "loading" from layers
            //            window.console && console.log("mbLayertree._onSourceLoadEnd:", event);
            if(!option.source)
                return;
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+source.id+'"]');
                source_li.removeClass('loading').removeClass('error').find('span.layer-state:first').attr("title", "");
                source_li.find('li').each(function(idx, el){
                    $(el).removeClass('loading').removeClass('error').find('span.layer-state:first').attr("title", "");
                });
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+source.id+'"]').removeClass('loading');
            }
        },
        _onSourceLoadError: function(event, option){ // sets "error" for layers
            //            window.console && console.log("mbLayertree._onSourceLoadError:", event);
            if(!option.source)
                return;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+option.source.id+'"]');
                source_li.removeClass('loading').removeClass('invisible').addClass('error').find('span.layer-state:first').attr("title", option.error.details);
                source_li.find('li').each(function(idx, el){
                    $(el).removeClass('loading').removeClass('invisible').addClass('error').find('span.layer-state:first').attr("title", option.error.details);
                });
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+option.source.id+'"]').each(function(idx, elm){
                    if($(elm).find('input[name="selected"]:first').is(':checked')){
                        $(elm).removeClass('invisible').removeClass('loading').addClass('error');
                        $(elm).find('span.state').attr({
                            title: option.error.details
                        });
                    }
                });
            }
        },
    
        _createSourceTree: function(source, sourceEl, scale, type, isroot){
            var self = this;
            if(sourceEl.type){ // source
                var li = "";
                sourceEl.layers = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li = this._createSourceTree(source, sourceEl.configuration.children[i], scale, sourceEl.type, true);
                }
            } else {
                var config = this._getNodeProporties(sourceEl);
                var li = this.template.clone();
                li.removeClass('hide-elm');
                li.attr('data-id', sourceEl.options.id);
                isroot ? li.attr('data-sourceid', source.id) : li.removeAttr('data-sourceid');
                li.attr('data-type', this._getNodeType(sourceEl, isroot));
                li.addClass(config.reorder);
                li.find('.layer-state').attr('title', config.visibility.tooltip);//.addClass('config.visibility.state');
                li.find('input.layer-selected').attr('checked', config.selected ? 'checked' : null);
                if(!config.selectable) li.find('input.layer-selected').attr('disabled', 'disabled');
                li.find('input.layer-info').attr('checked', config.info ? 'checked' : null);
                if(!config.infoable) li.find('input.layer-info').attr('disabled', 'disabled');
                li.find('.layer-title').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                if(config.toggleable) li.find('.layer-title').addClass('toggleable');
                if(!this.options.layerMenu){
                    li.find('.layer-menu-btn').remove();
                } else {
                    var menu = li.find('.layer-menu:first');
                    if(!sourceEl.options.legend){
                        menu.find('.layer-legend').addClass('btn-disabled');
                    } else {
                        menu.find('.layer-legend').bind("click", function(e){ e.stopPropagation(); self._showLegend(sourceEl); });
                    }
                    menu.find('.layer-kmlexport').bind("click", function(e){ e.stopPropagation(); self._exportKml(sourceEl); });
                    if(sourceEl.options.maxScale !== null){
                        menu.find('.layer-zoom').addClass('btn-disabled');
                    }else {
                        menu.find('.layer-zoom').bind("click", function(e){ e.stopPropagation(); self._zoomToLayer(sourceEl); });
                    }
                    menu.find('.layer-metadata').bind("click", function(e){ e.stopPropagation(); self._showMetadata(sourceEl); });
                    
                }
                if(!this.options.layerRemove) li.find('.layer-remove-btn').remove();
                if(sourceEl.children){
                    li.find('ul:first').attr('id', 'list-'+sourceEl.options.id);
                    if(config.toggle){
                        li.find('ul:first').addClass("closed");
                    }
                    for(var j = sourceEl.children.length; j > 0; j--){
                        li.find('ul:first').append(this._createSourceTree(source, sourceEl.children[j-1], scale, type, false));
                    }
                } else {
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
            } else {
                if(layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found){
                    found = true;
                    var config = this._getNodeProporties(sourceEl);
                    var li = this.template.clone();
                    li.removeClass('hide-elm');
                    li.attr('data-id', sourceEl.options.id);
                    isroot ? li.attr('data-sourceid', source.id) : li.removeAttr('data-sourceid');
                    li.attr('data-type', this._getNodeType(sourceEl, isroot));
                    li.addClass(config.reorder);
                    li.find('.layer-state').attr('title', config.visibility.tooltip);//.addClass(config.visibility.state);
                    li.find('input.layer-selected').attr('checked', config.selected ? 'checked' : null);
                    if(!config.selectable) li.find('input[name="selected"]').attr('disabled', 'disabled');
                    li.find('input.layer-info').attr('checked', config.info ? 'checked' : null);
                    if(!config.infoable) li.find('input[name="info"]').attr('disabled', 'disabled');
                    li.find('.layer-title').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                    if(config.toggleable) li.find('.layer-title').addClass('toggleable');
                    if(!this.options.layerMenu) li.find('.layer-menu-btn').remove();
                    if(!this.options.layerRemove) li.find('.layer-remove-btn').remove();
                    if(sourceEl.children){
                        li.find('ul:first').attr('id', 'list-'+sourceEl.options.id);
                        for(var j = 0; j < sourceEl.children.length; j++){
                            li.find('ul:first').append(this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found));
                        }
                    } else {
                        li.find('ul').remove();
                    }
                    found = false;
                    return li;
                }
                if(sourceEl.children){
                    parent = parent.find('li[data-id="'+sourceEl.options.id+'"]:first');
                    for(var j = 0; j < sourceEl.children.length; j++){
                        var li = this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        if(li !== null){
                            if(sourceEl.children.length === 1){
                                parent.add(li);
                            } else if(j === 0){
                                parent.find('li[data-id="'+sourceEl.children[j+1].options.id+'"]:first').after(li);
                            } else {
                                parent.find('li[data-id="'+sourceEl.children[j-1].options.id+'"]:first').before(li);
                            }
                        }
                    }
                }
            }
            return null;
        },
        
        _createSourceList: function(source, sourceEl, scale, type, isroot){
            if(sourceEl.type){ // source
                var liarr = [];
                sourceEl.layers = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    liarr.concat(this._createSourceList(source, sourceEl.configuration.children[i], scale, sourceEl.type, true));
                }
            } else {
                var liarr = [];
                if(sourceEl.children){
                    for(var j = sourceEl.children.length; j > 0; j--){
                        liarr.concat(this._createSourceList(source, sourceEl.children[j-1], scale, type, false));
                    }
                } else {
                    var config = this._getNodeProporties(sourceEl);
//                    var li ='<li data-sourceid="'+source.id+'" data-id="'+sourceEl.options.id+'" data-type="'+this._getNodeType(sourceEl, isroot)+'" class="' + config.reorder + '" >'
//                    +   '<span class="spinner"></span>'
//                    +   '<span class="state '+config.visibility.state+'" title="'+config.visibility.tooltip+'"></span>'
//                    +   '<input type="checkbox" title="selected" name="selected" '+ config.sel +' ' + config.selable + '/>'
//                    +   '<input type="checkbox" title="query" name="info" '+ config.info +' ' + config.infoable + '/>'
//                    +   '<span class="sourcetitle '+config.toggleable+'" title="'+sourceEl.options.title+'">' + this._subStringText(sourceEl.options.title) + '</span>';
//                    if(this.options.layerMenu){
//                        li += '<span class="menubutton">&#9776;</span>';
//                    }
//                    if(this.options.layerRemove){
//                        li += '<span class="removebutton">&times;</span>';
//                    }
//                    //                li += this._createMenu();
//
//                    li += '</li>';
                    
                    var li = this.template.clone();
                    li.removeClass('hide-elm');
                    li.attr('data-sourceid', sourceEl.options.id).attr('data-id', sourceEl.options.id).attr('data-type', this._getNodeType(sourceEl, isroot)).addClass(config.reorder);
                    li.find('.state').attr('title', config.visibility.tooltip).addClass('config.visibility.state');
                    li.find('input[name="selected"]').attr('checked', config.selected ? 'checked' : null);
                    if(!config.selectable) li.find('input[name="selected"]').attr('disabled', 'disabled');
                    li.find('input[name="info"]').attr('checked', config.info ? 'checked' : null);
                    if(!config.infoable) li.find('input[name="info"]').attr('disabled', 'disabled');
                    li.find('.sourcetitle').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                    if(config.toggleable) li.find('.layer-title').addClass('toggleable');
                    if(!this.options.layerMenu) li.find('.menubutton').remove();
                    if(!this.options.layerRemove) li.find('.removebutton').remove();
                    liarr.push(li);
                }
            }
            return liarr;
        },
        
        _createListNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found){
            alert("not defined");
            return;
            if(sourceEl.type){ // source
                var liarr = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    liarr.concat(this._createListNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent, sourceEl.type, true, false));
                }
            } else {
                if(layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found){
                    found = true;
                    var liarr = [];
                    var config = this._getNodeProporties(sourceEl);
                    var s_id = isroot ? '' : "";
                    var li ='<li data-sourceid="'+source.id+'" data-id="'+sourceEl.options.id+'" data-type="'+this._getNodeType(sourceEl, isroot)+'" class="' + config.reorder + '" >'
                    +   '<span class="spinner"></span>'
                    +   '<span class="state '+config.visibility.state+'" title="'+config.visibility.tooltip+'"></span>'
                    +   '<input type="checkbox" title="selected" name="selected" '+ config.sel +' ' + config.selable + '/>'
                    +   '<input type="checkbox" title="query" name="info" '+ config.info +' ' + config.infoable + '/>'
                    +   '<span class="sourcetitle '+config.toggleable+'" title="'+sourceEl.options.title+'">' + this._subStringText(sourceEl.options.title) + '</span>';
                    if(true){ //TODO check if menu available
                        li += '<span class="menubutton">&#9776;</span>';
                    }
                    if(true){ //TODO check if close claseable
                        li += '<span class="removebutton">&times;</span>';
                    }
                    li += '</li>';
                    if(sourceEl.children){
                        //                        li +=     '<ul id="list-'+sourceEl.options.id+'" class="layers ' + config.toggle + '">';
                        for(var j = 0; j < sourceEl.children.length; j++){
                            li += this._createListNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        }
                    //                        li +=     '</ul>';
                    }
                    found = false;
                    return li;
                }
                if(sourceEl.children){
                    //                    parent = parent.find('li[data-id="'+sourceEl.options.id+'"]:first');
                    for(var j = 0; j < sourceEl.children.length; j++){
                        var li = this._createListNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        if(li !== null){
                            if(sourceEl.children.length === 1){
                                parent.add(li);
                            } else if(j === 0){
                                parent.find('li[data-id="'+sourceEl.children[j+1].options.id+'"]:first').after(li);
                            } else {
                                parent.find('li[data-id="'+sourceEl.children[j-1].options.id+'"]:first').before(li);
                            }
                        }
                    }
                }
            }
            return null;
        },

        _subStringText: function(text){
            if(text.length <= this.options.titlemaxlength){
                return text;
            } else {
                for(var i = this.options.titlemaxlength; i > 0; i--){
                    if(text[i] === " "){
                        text = text.substring(0,i)+"...";
                        break;
                    }
                }
                if(text.length < 2 || text.length > this.options.titlemaxlength + 3)
                    return text.substring(0,this.options.titlemaxlength)+"...";
                else
                    return text;
            }
        },
    
        _getNodeType: function(node, isroot){
            if(node.children && isroot){
                return this.consts.root;
            } else if(node.children){
                return this.consts.group;
            } else {
                return this.consts.simple;
            }  
        },
    
        _getNodeProporties: function(nodeConfig) {
            var conf =  {
                selected: nodeConfig.options.treeOptions.selected,
                selectable: nodeConfig.options.treeOptions.allow.selected,
                info: nodeConfig.options.treeOptions.info,
                infoable: nodeConfig.options.treeOptions.allow.info,
                reorderable: nodeConfig.options.treeOptions.allow.reorder
//                ,
//                sel: nodeConfig.options.treeOptions.selected ? 'checked="checked"' : '',
//                selable: nodeConfig.options.treeOptions.allow.selected ? '' : 'disabled="disabled"',
//                info: nodeConfig.options.treeOptions.info ? 'checked="checked"' : '',
//                infoable: nodeConfig.options.treeOptions.allow.info ? '' : 'disabled="disabled"',
//                reorder: nodeConfig.options.treeOptions.allow.reorder ? '' : 'notreorder'
            };
            if(nodeConfig.children){
                conf["toggle"] = nodeConfig.options.treeOptions.toggle;
                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle;
            } else {
                conf["toggle"] = null;
                conf["toggleable"] = null;
            }
//            if(nodeConfig.children){
//                conf["toggle"] = nodeConfig.options.treeOptions.toggle ? '' : 'closed';
//                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle ? 'toggleable' : '';
//            } else {
//                conf["toggle"] = '';
//                conf["toggleable"] = '';
//            }
            if(nodeConfig.state.outOfScale){
                conf["visibility"] = {
                    state: "invisible", 
                    tooltip : "outOfScale"
                };
            } else if(nodeConfig.state.outOfBounds){
                conf["visibility"] = {
                    state: "invisible", 
                    tooltip : "outOfBounds"
                };
            } else if(!nodeConfig.state.visibility){
                conf["visibility"] = {
                    state: "invisible", 
                    tooltip : "parent invisible"
                };
            } else {
                conf["visibility"] = {
                    state: "", 
                    tooltip : ""
                };
            }
            return conf;
        },
        
        _toggleContent: function(e){
            if($(e.target).parents("li:first").find("ul.layers:first").hasClass("closed")){
                $(e.target).parents("li:first").find("ul.layers:first").removeClass("closed");
            } else {
                $(e.target).parents("li:first").find("ul.layers:first").addClass("closed");
            }
        },
    
        _toggleSelected: function(e){
            var id = $(e.target).parents('li:first').attr('data-id');
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var tochange = this.model.createToChangeObj(this.model.getSource({
                id: sourceId
            }));
            tochange.children[id] = {
                options:{
                    treeOptions:{
                        selected: $(e.target).is(':checked')
                    }
                }
            };
            tochange.type = {
                layerTree: "select"
            };
            this.model.changeSource(tochange);
        },
    
        _toggleInfo: function(e){
            var id = $(e.target).parents('li:first').attr('data-id');
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var tochange = this.model.createToChangeObj(this.model.getSource({
                id: sourceId
            }));
            tochange.children[id] = {
                options:{
                    treeOptions:{
                        info: $(e.target).is(':checked')
                    }
                }
            };
            tochange.type =  {
                layerTree: "info"
            };
            this.model.changeSource(tochange);
        },
        
        _toggleMenu: function(e){
            console.log("TOGGLE MENU",e);
            var menu = $(e.target).parent().find('div.layer-menu:first');
            if(menu.hasClass("hide-elm")){
                menu.removeClass("hide-elm");
            } else {
                menu.addClass("hide-elm");
            }
        },
        
        _removeSource: function(e){
            var layer_id = $(e.target).parents("li:first").attr("data-id");
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var toremove = this.model.createToChangeObj(this.model.getSource({
                id: sourceId
            }));
            var layerOpts = this.model.getSourceLayerById(toremove.source, layer_id);
            toremove.children[layer_id] = layerOpts.layer;
            toremove.type =  {
                layerTree: "remove"
            };
            this.model.removeSource(toremove);
            this._setSourcesCount();
        },
        
        _showLegend: function(elm){
            
            window.console && console.log("_showLegend", elm);
        },
    
        _exportKml: function(elm){
            window.console && console.log("_exportKml", elm);
        },
        
        _zoomToLayer: function(elm){
            window.console && console.log("_zoomToLayer", elm);
        },
        
        _showMetadata: function(elm){
            window.console && console.log("_showMetadata", elm);
        },
        
        _setSourcesCount: function(){
            var countObj = {};
            $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm){
                countObj[$(elm).attr('data-sourceid')] = true;
            });
            var num = 0;
            for(s in countObj)
                num ++;
            $(this.element).find('#counter').text(num);
        },
        
        _removeAllLayers: function(e){
            var self = this;
            if(confirm("Really all sources delete?")){
                $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm){
                    var layer_id = $(elm).attr("data-id");
                    var sourceId = $(elm).attr('data-sourceid');
                    var toremove = self.model.createToChangeObj(self.model.getSource({
                        id: sourceId
                    }));
                    var layerOpts = self.model.getSourceLayerById(toremove.source, layer_id);
                    toremove.children[layer_id] = layerOpts.layer;
                    toremove.type =  {
                        layerTree: "remove"
                    };
                    self.model.removeSource(toremove);
                });
            }
            this._setSourcesCount();
        },
    
        open: function(){
            if(this.options.type === 'dialog' && this.dlg !== null){
                this.dlg.dialog('open');
            }
        },
        
        close: function(){
            if(this.options.type === 'dialog' && this.dlg !== null){
                this.dlg.dialog('close');
            }
        },
    
        _initDialog: function() {
            var self = this;
            if(this.dlg === null) {
                this.dlg = $('<div></div>')
                .attr('id', 'mb-layertree-dialog')
                .appendTo($('body'))
                .dialog({
                    title: 'Layer Tree',
                    autoOpen: false,
                    modal: false
                });
                self.dlg.html($(self.element));
            }
        //            if(this.options.useAccordion){
        ////                $(this.element).find('ul.layers > li').each(function(){
        //var a = $(this.element).find('ul.layers li[data-type="root"]');
        //                    $(this.element).find('ul.layers li[data-type="root"]').accordion({
        //                        header: 'div.title',
        //                        autoHeight: false, 
        //                        collapsible: true, 
        //                        active: false
        //                    });
        ////                });
        //            }
        },
    
        _destroy: $.noop
    });

})(jQuery);


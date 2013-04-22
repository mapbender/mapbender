(function($) {

    $.widget("mapbender.mbLayertree", {
        options: {
            title: 'Table of Contents',
            autoOpen: false,
            target: null
        },
        model: null,
        dlg: null,
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
            // FIXME: fix to on when updating jquery 
            //        me.find('input[name="enabled"]').live("change",{self:self},self.on_layer_toggle_enabled);
            //        me.find('input[name="queryable"]').live("change",{self:self},self.on_layer_toggle_queryable);
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
    
        _setup: function(){
            var self = this;
            if(self.options.type === 'dialog' && new Boolean(self.options.autoOpen).valueOf() === true){
                self.open();
            }
            var me = this.element;
            this.model = $("#" + self.options.target).data("mbMap").getModel();
            var sources = this.model.getSources();
            for(var i = (sources.length - 1); i > -1; i--){
                if(!sources[i].configuration.baseSource || (sources[i].configuration.baseSource && this.options.showBaseSource)){
                    if(this.options.displaytype === "tree"){
                        var li_s = this._createSourceTree(sources[i], sources[i], this.model.getScale());
                        me.find("ul.layers:first").append($(li_s));
                    } else if(this.options.displaytype === "list"){
                        var li_s = self._createSourceList(sources[i], sources[i], this.model.getScale());
                        me.find("ul.layers:first").append($(li_s));
                    }
                }
            }
            
            me.find("slider").slider();
            
            this._createSortable();

            $(this.element).find('li input[name="selected"]').live("change", $.proxy(self._toggleSelected, self));
            $(this.element).find('li input[name="info"]').live("change", $.proxy(self._toggleInfo, self));
            $(this.element).find('li span.toggleable').live("click", $.proxy(self._toggleContent, self));
            
            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            
            $(this.element).find('.removebutton').live("click", $.proxy(self._removeSource, self));
            $(this.element).find('.menubutton').live("click", $.proxy(self._showMenu, self));
            
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
                    items: "li:not(.notreorder)",
                    distance: 6,
                    stop: function( event, ui) {
                        if($(ui.item).parent().attr("id") !== $(event.target).attr("id")){
                            $(that).sortable('cancel');
                            return;
                        }
                        var et = $(event.target);
                        var list = $(event.target).children("li");
                        for(var i = 0; i < list.length; i++){
                            var elm = list[i];
                            var a = $(elm).attr("data-id"), b = $(ui.item).attr("data-id");
                            if($(elm).attr("data-id")===$(ui.item).attr("data-id")){
                                var before = null, after = null, tomove;
                                if(i > 0){
                                    var beforeEl = list[i-1];
                                    var beforeId = $(beforeEl).attr("data-id");
                                    var beforeSourceId = $(beforeEl).attr('data-sourceid') ? $(beforeEl).attr('data-sourceid') : $(beforeEl).parents('li[data-sourceid]:first').attr('data-sourceid');//self._findSourceId($(beforeEl));
                                    before = {source: self.model.getSource({id: beforeSourceId}), layerId: beforeId};
                                }
                                if(i < list.length - 1){
                                    var afterEl = list[i+1];
                                    var afterId = $(afterEl).attr("data-id");
                                    var afterSourceId = $(afterEl).attr('data-sourceid') ? $(afterEl).attr('data-sourceid') : $(afterEl).parents('li[data-sourceid]:first').attr('data-sourceid');//self._findSourceId($(afterEl));
                                    after = {source:  self.model.getSource({id: afterSourceId}), layerId: afterId};
                                }
                                var tomoveId = $(ui.item).attr("data-id");
                                var tomoveSourceId = $(elm).attr('data-sourceid') ? $(elm).attr('data-sourceid') : $(elm).parents('li[data-sourceid]:first').attr('data-sourceid');
                                tomove = { source: self.model.getSource({id: tomoveSourceId})};
                                if($(ui.item).attr("data-type") !== self.consts.root){
                                    tomove['layerId'] = tomoveId;
                                }
                                var tochange = self.model.createToChangeObj(tomove.source);
                                tochange.type = "source_move";
                                tochange.children.before = after;
                                tochange.children.after = before;
                                tochange.children.tomove = tomove;
                                self.model.changeSource(tochange);
                                break;
                            }
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
                    if(first_li){
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
                    var li_s = this._createSourceList(added.source, added.source, this.model.getScale());
                    if(before && before.layerId){
                        $(this.element).find('ul.layers:first li[data-id="'+before.layerId+'"]').after(li_s);
                    } else if(after && after.layerId){
                        $(this.element).find('ul.layers:first li[data-id="'+after.layerId+'"]').before(li_s);
                    }
                }
            }
        },
        
        _onSourceChanged: function(event, changed){
            //            window.console && console.log("mbLayertree._onSourceChanged:", changed);
            if(this.options.displaytype === "tree"){
                for(key in changed.children){
                    var changedEl = changed.children[key];
                    if(changedEl.treeElm.state.visibility){
                        $(this.element).find('li[data-id="'+key+'"] span.state:first').removeClass("invisible").attr({
                            title: ""
                        });
                    } else {
                        if($(this.element).find('li[data-id="'+key+'"] input[name="selected"]:first').is(':checked')){
                            $(this.element).find('li[data-id="'+key+'"] span.state:first').addClass("invisible").attr({
                                title: changedEl.state.outOfScale ? "outOfScale" : "parent invisible"
                            });
                        } else {
                            $(this.element).find('li[data-id="'+key+'"] span.state:first').removeClass("invisible").attr({
                                title: ""
                            });
                        }
                    }
                }
            } else if(this.options.displaytype === "list"){
                var a = 0;
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
        },
        
        _onSourceLoadStart: function(event, option){
            //            window.console && console.log("mbLayertree._onSourceLoadStart:", event);
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+source.id+'"]');
                if(source_li.find('input[name="selected"]:first').is(':checked')
                    && !source_li.find('span.state:first').hasClass('invisible')){
                    source_li.find('span.spinner:first').addClass('loading');
                    source_li.find('li').each(function(idx, el){
                        var li_el = $(el);
                        if(li_el.find('input[name="selected"]:first').is(':checked')
                            && !li_el.find('span.state:first').hasClass('invisible')){
                            li_el.find('span.spinner:first').addClass('loading');
                        }
                    });
                }
                source_li.find('span.state.error').removeClass('error').attr({
                    title: ""
                });
                
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+source.id+'"]').each(function(idx, elm){
                    if($(elm).find('input[name="selected"]:first').is(':checked')){
//                        && !$(elm).find('span.state:first').hasClass('invisible')){
                        $(elm).find('span.state:first').removeClass('invisible').removeClass('error');
                        $(elm).find('span.spinner:first').addClass('loading');
                    }
                });
            }
        },
        
        _onSourceLoadEnd: function(event, option){
            //            window.console && console.log("mbLayertree._onSourceLoadEnd:", event);
            var source = option.source;
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+source.id+'"]');
                if(source_li.find('span.spinner:first').hasClass('loading')){
                    source_li.find('span.spinner').removeClass('loading ');
                    
                }
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+source.id+'"] span.spinner').removeClass('loading');
            }
        },
        _onSourceLoadError: function(event, option){
            //            window.console && console.log("mbLayertree._onSourceLoadError:", event);
            if(this.options.displaytype === "tree"){
                var source_li = $(this.element).find('li[data-sourceid="'+option.source.id+'"]');
                if(source_li.find('span.spinner:first').hasClass('loading')){
                    source_li.find('span.spinner:first').removeClass('loading');
                // @TODO for layer ??
                }
                source_li.find('span.state').removeClass('invisible').addClass('error').attr({
                    title: option.error.details
                });
            } else if(this.options.displaytype === "list"){
                $(this.element).find('li[data-sourceid="'+option.source.id+'"]').each(function(idx, elm){
                    $(elm).find('span.spinner:first').removeClass('loading');
                    if($(elm).find('input[name="selected"]:first').is(':checked')
                        && !$(elm).find('span.state:first').hasClass('invisible')){
                        $(elm).find('span.state').removeClass('invisible').addClass('error').attr({
                            title: option.error.details
                        });
                        
                    }
                });
            }
        },
    
        _createSourceTree: function(source, sourceEl, scale, type, isroot){
            if(sourceEl.type){ // source
                var li = "";
                sourceEl.layers = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li += this._createSourceTree(source, sourceEl.configuration.children[i], scale, sourceEl.type, true);
                }
            } else {
                var config = this._getNodeProporties(sourceEl);
                var s_id = isroot ? ' data-sourceid="'+source.id+'"' : "";
                var li ='<li'+s_id+' data-id="'+sourceEl.options.id+'" data-type="'+this._getNodeType(sourceEl, isroot)+'" class="' + config.reorder + '" >'
                +   '<span class="spinner"></span>'
                +   '<span class="state '+config.visibility.state+'" title="'+config.visibility.tooltip+'"></span>'
                +   '<input type="checkbox" title="selected" name="selected" '+ config.sel +' ' + config.selable + '/>'
                +   '<input type="checkbox" title="query" name="info" '+ config.info +' ' + config.infoable + '/>'
                +   '<span class="sourcetitle '+config.toggleable+'" title="'+sourceEl.options.title+'">' + this._subStringText(sourceEl.options.title) + '</span>'
                var added = "";
                if(true){ //TODO check if menu available
                    added += '<span class="menubutton">&#9776;</span>';
                }
                if(true){ //TODO check if close claseable
                    added += '<span class="removebutton">&times;</span>';
                }
                if(added !== ""){
                    li += added
//                li += this._createMenu();
                }
                if(sourceEl.children){
                    li +=     '<ul id="list-'+sourceEl.options.id+'" class="layers ' + config.toggle + '">';
                    for(var j = sourceEl.children.length; j > 0; j--){
                        li += this._createSourceTree(source, sourceEl.children[j-1], scale, type, false);
                    }
                    li +=     '</ul>';
                }
                
                li +=
//                    isroot ? '<div>' : ''+    
                    '</li>';
            }
            return li;
        },
        
        _createTreeNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found){
            if(sourceEl.type){ // source
                var li = "";
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li += this._createTreeNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent, sourceEl.type, true, false);
                }
            } else {
                if(layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found){
                    found = true;
                    var config = this._getNodeProporties(sourceEl);
                    var s_id = isroot ? ' data-sourceid="'+source.id+'"' : "";
                    var li ='<li'+s_id+' data-id="'+sourceEl.options.id+'" data-type="'+this._getNodeType(sourceEl, isroot)+'" class="' + config.reorder + '" >'
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
    //                li += this._createMenu();
                    if(sourceEl.children){
                        li +=     '<ul id="list-'+sourceEl.options.id+'" class="layers ' + config.toggle + '">';
                        for(var j = 0; j < sourceEl.children.length; j++){
                            li += this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        }
                        li +=     '</ul>';
                    }
                    li += '</li>';
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
                var li = "";
                sourceEl.layers = [];
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li += this._createSourceList(source, sourceEl.configuration.children[i], scale, sourceEl.type, true);
                }
            } else {
                var li ='';
                if(sourceEl.children){
                    for(var j = sourceEl.children.length; j > 0; j--){
                        li += this._createSourceList(source, sourceEl.children[j-1], scale, type, false);
                    }
                } else {
                    var config = this._getNodeProporties(sourceEl);
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
    //                li += this._createMenu();

                    li += '</li>';
                }
            }
            return li;
        },
        
        _createListNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found){
            if(sourceEl.type){ // source
                var li = "";
                for(var i = 0; i < sourceEl.configuration.children.length; i++){
                    li += this._createListNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent, sourceEl.type, true, false);
                }
            } else {
                if(layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found){
                    found = true;
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
                sel: nodeConfig.options.treeOptions.selected ? 'checked="checked"' : '',
                selable: nodeConfig.options.treeOptions.allow.selected ? '' : 'disabled="disabled"',
                info: nodeConfig.options.treeOptions.info ? 'checked="checked"' : '',
                infoable: nodeConfig.options.treeOptions.allow.info ? '' : 'disabled="disabled"',
                reorder: nodeConfig.options.treeOptions.allow.reorder ? '' : 'notreorder'
            };
            if(nodeConfig.children){
                conf["toggle"] = nodeConfig.options.treeOptions.toggle ? '' : 'closed';
                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle ? 'toggleable' : '';
            } else {
                conf["toggle"] = '';
                conf["toggleable"] = '';
            }
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
//    
//        _findSourceId: function(elm) {
//            if(elm.attr("data-sourceid")){
//                return elm.attr("data-sourceid");
//            } else {
//                return this._findSourceId(elm.parent());
//            }
//        },
        
        _toggleContent: function(e){
            if($(e.target).parent().find("ul.layers").hasClass("closed")){
                $(e.target).parent().find("ul.layers").removeClass("closed");
            } else {
                $(e.target).parent().find("ul.layers").addClass("closed");
            }
        },
    
        _toggleSelected: function(e){
            var id = $(e.target).parents('li:first').attr('data-id');
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var tochange = this.model.createToChangeObj(this.model.getSource({id: sourceId}));
            tochange.children[id] = {
                options:{
                    treeOptions:{
                        selected: $(e.target).is(':checked')
                    }
                }
            };
            tochange.type = "tree_selected";
            this.model.changeSource(tochange);
        },
    
        _toggleInfo: function(e){
            var id = $(e.target).parents('li:first').attr('data-id');
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var tochange = this.model.createToChangeObj(this.model.getSource({id: sourceId}));
            tochange.children[id] = {
                options:{
                    treeOptions:{
                        info: $(e.target).is(':checked')
                    }
                }
            };
            tochange.type = "tree_info";
            this.model.changeSource(tochange);
        },
        
        _removeSource: function(e){
            var layer_id = $(e.target).parents("li:first").attr("data-id");
            var sourceId = $(e.target).parents('li[data-sourceid]:first').attr('data-sourceid');
            var toremove = this.model.createToChangeObj(this.model.getSource({id: sourceId}));
            var layerOpts = this.model.getSourceLayerById(toremove.source, layer_id);
            toremove.children[layer_id] = layerOpts.layer;
            this.model.removeSource(toremove);
        },
        
        _showMenu: function(e){
            var layer = 0;
        },
    
        _createRootNode: function(ss){
        
        },
    
        _createGroupNode: function(ss){
        
        },
        _createLayerNode: function(ss){
        
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


(function($) {
    $.widget("mapbender.mbLayertree", {
        options: {
            autoOpen: false,
            target: null,
            layerInfo: true,
            menu: []
        },
        model: null,
        dlg: null,
        template: null,
        menuTemplate: null,
        layerconf: null,
        popup: null,
        created: false,
        loadStarted: {},
        sourceAtTree: {},
        consts: {
            source: "source",
            root: "root",
            group: "group",
            simple: "simple"
        },
        transConst: {outOfScale: '', outOfBounds: '', parentInvisible: ''},
        _create: function() {
            if (!Mapbender.checkTarget("mbLayertree", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            this.transConst.outOfScale = Mapbender.trans("mb.core.layertree.const.outofscale");
            this.transConst.outOfBounds = Mapbender.trans("mb.core.layertree.const.outofbounds");
            this.transConst.parentInvisible = Mapbender.trans("mb.core.layertree.const.parentinvisible");
            this.options.titlemaxlength = parseInt(this.options.titlemaxlength);
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.template = $('li', this.element).remove();
            this.template.removeClass('hidden');
            this.menuTemplate = $('#layer-menu', this.template).remove();

            this.model = $("#" + self.options.target).data("mapbenderMbMap").getModel();
            if (this.options.type === 'element') {
                this._createTree();
            } else if (this.options.type === 'dialog' && new Boolean(self.options.autoOpen).valueOf() === true) {
                this.open();
            }
            this.element.removeClass('hidden');
            this._trigger('ready');
            this._ready();
        },
        _createTree: function() {
            var self = this;
            var sources = this.model.getSources();
            if (this.created)
                this._unSortable();
            for (var i = (sources.length - 1); i > -1; i--) {
                if (!sources[i].configuration.isBaseSource || (sources[i].configuration.isBaseSource && this.options.showBaseSource)) {
                    if (this.options.displaytype === "tree") {
                        var li_s = this._createSourceTree(sources[i], sources[i], this.model.getScale());
                        $("ul.layers:first", this.element).append(li_s);
                    } else if (this.options.displaytype === "list") {
                        var li_s = self._createSourceList(sources[i], sources[i], this.model.getScale());
                        $("ul.layers:first", this.element).append($(li_s));
                    }
                    this.sourceAtTree[sources[i].id ] = {id: sources[i].id};
                    this._resetSourceAtTree(sources[i]);
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
        _reset: function() {
            this._resetEvents();
            this._resetSortable();
            this._resetCheckboxes();
            this._setSourcesCount();
        },
        _createEvents: function() {
            var self = this;
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.on('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.on('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));
        },
        _removeEvents: function() {
            var self = this;
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.off('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.off('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.off('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));

        },
        _resetEvents: function() {
            this._removeEvents();
            this._createEvents();
        },
        _resetCheckboxes: function() {
            var self = this;
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleInfo, self));
            if (initCheckbox) {
                $('.checkbox', self.element).each(function() {
                    initCheckbox.call(this);
                });
            }
        },
        _resetSortable: function() {
            this._unSortable();
            this._createSortable();
        },
        _unSortable: function() {
        },
        _createSortable: function() {
            var self = this;
            $("ul.layers", this.element).each(function() {
                var that = this;
                $(that).sortable({
                    axis: 'y',
                    items: "> li:not(.notreorder)",
                    distance: 6,
                    cursor: "move",
                    update: function(event, ui) {
                        var elm = $(ui.item), beforeObj = null, afterObj = null, tomoveObj = null;
                        if (elm.prev().length !== 0) {
                            var beforeEl = $(elm.prev()[0]);
                            var bid = $(beforeEl).attr('data-sourceid');
                            if (!bid)
                                bid = $(beforeEl).parents('li[data-sourceid]:first').attr('data-sourceid');
                            beforeObj = {sourceIdx: {id: bid}, layerIdx: {id: $(beforeEl).attr("data-id")}};
                        }
                        if (elm.next().length !== 0) {
                            var afterEl = $(elm.next()[0]);
                            var aid = $(afterEl).attr('data-sourceid');
                            if (!aid)
                                aid = $(afterEl).parents('li[data-sourceid]:first').attr('data-sourceid');
                            afterObj = {sourceIdx: {id: aid}, layerIdx: {id: $(afterEl).attr("data-id")}};
                        }
                        var id = $(elm).attr('data-sourceid');
                        if (!id)
                            id = $(elm).parents('li[data-sourceid]:first').attr('data-sourceid');
                        tomoveObj = {sourceIdx: {id: id}};
                        if ($(ui.item).attr("data-type") !== self.consts.root) {
                            tomoveObj['layerIdx'] = {id: $(ui.item).attr("data-id")};
                        }
                        /* "before" at layerTree is "after" at model.sourceTree */
                        self.model.changeSource({change: {move: {tomove: tomoveObj, before: afterObj, after: beforeObj}}});
                    }
                });
            });
        },
        _createSourceTree: function(source, sourceEl, scale, type, isroot) {
            if (sourceEl.type) {
                var li = "";
                sourceEl.layers = [];
                for (var i = 0; i < sourceEl.configuration.children.length; i++) {
                    li = this._createSourceTree(source, sourceEl.configuration.children[i], scale, sourceEl.type, true);
                }
            } else {
                var config = this._getNodeProporties(sourceEl);
                var li = this.template.clone();
                li.removeClass('hide-elm');
                li.attr('data-id', sourceEl.options.id);
                li.attr('data-sourceid', source.id);
                var nodeType = this._getNodeType(sourceEl, isroot);
                li.attr('data-type', nodeType).attr('data-title', sourceEl.options.title);
                if (nodeType === this.consts.root || nodeType === this.consts.group) {
                    $('.featureInfoWrapper:first', li).remove();
                    if (nodeType === this.consts.root) {
                        li.addClass("serviceContainer");
                    } else if (nodeType === this.consts.group) {
                        li.addClass("groupContainer");
                    }
                    li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    /** @TODO add check config.toggleable if config.toggleable !== true -> '.iconFolder' has no folder and no event, sublayers are unvisible */
                    if (config.toggle === true)
                        li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    else
                        li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");
                }
                li.addClass(config.reorder);
                li.find('.layer-state').attr('title', config.visibility.tooltip);
                li.find('input.layer-selected').prop('checked', config.selected ? true : false);
                if (!config.selectable)
                    li.find('input.layer-selected').prop('disabled', true);
                li.find('input.layer-info').prop('checked', config.info ? true : false);
                if (!config.infoable)
                    li.find('input.layer-info').prop('disabled', true);
                li.find('.layer-title:first').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                if (config.toggleable)
                    li.addClass('toggleable');
                if (this.options.menu.length === 0 || (this.options.menu.length === 1 && $.inArray("opacity", this.options.menu) !== -1 && nodeType === this.consts.simple)){
                    li.find('.layer-menu-btn').remove();
                }
                if (!this.options.layerInfo)
                    li.find('.iconInfo').remove();
                if (sourceEl.children) {
                    li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                    if (config.toggle) {
                        li.find('ul:first').addClass("closed");
                    }
                    for (var j = sourceEl.children.length; j > 0; j--) {
                        li.find('ul:first').append(this._createSourceTree(source, sourceEl.children[j - 1], scale, type, false));
                    }
                } else {
                    li.find('ul:first').remove();
                }
            }
            return li;
        },
        _createTreeNode: function(source, sourceEl, scale, layerToAdd, parent, type, isroot, found) {
            if (sourceEl.type) {
                var li = "";
                for (var i = 0; i < sourceEl.configuration.children.length; i++) {
                    li = this._createTreeNode(source, sourceEl.configuration.children[i], scale, layerToAdd, parent, sourceEl.type, true, false);
                }
            } else {
                if (layerToAdd.options.id.toString() === sourceEl.options.id.toString() || found) {
                    found = true;
                    var config = this._getNodeProporties(sourceEl);
                    var li = this.template.clone();
                    li.removeClass('hide-elm');
                    li.attr('data-id', sourceEl.options.id);
                    isroot ? li.attr('data-sourceid', source.id) : li.removeAttr('data-sourceid');
                    var nodeType = this._getNodeType(sourceEl, isroot);
                    li.attr('data-type', nodeType).attr('data-title', sourceEl.options.title);

                    if (nodeType === this.consts.root) {
                        li.addClass("serviceContainer showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    } else if (nodeType === this.consts.group) {
                        li.addClass("groupContainer showLeaves").find(".iconFolder").addClass("iconFolderActive");
                    }
                    li.addClass(config.reorder);
                    li.find('.layer-state').attr('title', config.visibility.tooltip);
                    li.find('input.layer-selected').prop('checked', config.selected ? true : false);
                    if (!config.selectable)
                        li.find('input.layer-selected').prop('disabled', true);
                    li.find('input.layer-info').prop('checked', config.info ? true : false);
                    if (!config.infoable)
                        li.find('input.layer-info').prop('disabled', true);
                    li.find('.layer-title:first').attr('title', sourceEl.options.title).text(this._subStringText(sourceEl.options.title));
                    if (config.toggleable)
                        li.addClass('toggleable');
                    if (this.options.menu.length === 0 || (this.options.menu.length === 1 && $.inArray("opacity", this.options.menu) !== -1 && nodeType === this.consts.simple)){
                        li.find('.layer-menu-btn').remove();
                    }
                    if (sourceEl.children) {
                        li.find('ul:first').attr('id', 'list-' + sourceEl.options.id);
                        if (config.toggle) {
                            li.find('ul:first').addClass("closed");
                        }
                        for (var j = 0; j < sourceEl.children.length; j++) {
                            li.find('ul:first').append(this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found));
                        }
                    } else {
                        li.find('ul').remove();
                    }
                    found = false;
                    return li;
                }
                if (sourceEl.children) {
                    parent = parent.find('li[data-id="' + sourceEl.options.id + '"]:first');
                    for (var j = 0; j < sourceEl.children.length; j++) {
                        var li = this._createTreeNode(source, sourceEl.children[j], scale, layerToAdd, parent, type, false, found);
                        if (li !== null) {
                            if (sourceEl.children.length === 1) {
                                parent.add(li);
                            } else if (j === 0) {
                                parent.find('li[data-id="' + sourceEl.children[j + 1].options.id + '"]:first').after(li);
                            } else {
                                parent.find('li[data-id="' + sourceEl.children[j - 1].options.id + '"]:first').before(li);
                            }
                        }
                    }
                }
            }
            return null;
        },
        _onSourceAdded: function(event, options) {
            if (!this.created || !options.added)
                return;
            var added = options.added;
            var before = added.after, after = added.before;
            if (added.source.configuration.baseSource && !this.options.showBaseSource) {
                return;
            }
            if (this.options.displaytype === "tree") {
                var hasChildren = false;
                for (layerid in added.children) {
                    this._createTreeNode(added.source, added.source, this.model.getScale(), added.children[layerid], $(this.element).find('ul.layers:first'));
                }
                if (!hasChildren) {
                    var li_s = this._createSourceTree(added.source, added.source, this.model.getScale());
                    var first_li = $(this.element).find('ul.layers:first li:first');
                    if (first_li && first_li.length !== 0) {
                        first_li.before(li_s);
                    } else {
                        $(this.element).find('ul.layers:first').append($(li_s));
                    }
                }
            } else if (this.options.displaytype === "list") {
                var hasChildren = false;
                for (layerid in added.children) {
                    hasChildren = true;
                    if ($(!this.element).find('ul.layers:first li[data-id="' + added.layerId + '"]')) {
                        this._createListNode(added.source, added.source, this.model.getScale(), added.children[layerid], $(this.element).find('ul.layers:first'));
                    }
                }
                if (!hasChildren) {
                    $("ul.layers").each(function() {
                        var that = this;
                        $(that).sortable("destroy");
                    });
                    var li_s = this._createSourceList(added.source, added.source, this.model.getScale());
                    if (before && before.layerId) {
                        $(this.element).find('ul.layers:first li[data-id="' + before.layerId + '"]').after(li_s);
                    } else if (after && after.layerId) {
                        $(this.element).find('ul.layers:first li[data-id="' + after.layerId + '"]').before(li_s);
                    } else if (!this.options.showBaseSource && after.source.configuration.isBaseSource) {
                        $(this.element).find('ul.layers:first').append(li_s);
                    } else if (!after.source.configuration.isBaseSource) {
                        $(this.element).find('ul.layers:first').append(li_s);
                    }
                }
            }
            this.sourceAtTree[added.source.id ] = {id: added.source.id};
            this._reset();
        },
        _onSourceChanged: function(event, options) {
            if (options.changed && options.changed.children) {
                this._changeChildren(options.changed);
            } else if (options.changed && options.changed.childRemoved) {
                this._removeChild(options.changed);
            }
        },
        _resetNodeOutOfScale: function($li, layerDef) {
            if (layerDef.state.outOfScale) {
                $li.addClass("invisible").find('span.layer-state').attr("title", "out of scale");
            } else if (!layerDef.state.outOfScale) {
                $li.removeClass("invisible").find('span.layer-state').attr("title", "");
            }
        },
        _resetNodeSelected: function($li, layerOptions) {
            var chk_selected = $('input[name="selected"]:first', $li);
            chk_selected.prop('checked', layerOptions.treeOptions.selected);
            initCheckbox.call(chk_selected);
        },
        _resetNodeInfo: function($li, layerOptions) {
            var chk_info = $('input[name="info"]:first', $li);
            chk_info.prop('checked', layerOptions.treeOptions.info);
            chk_info.each(function(k, v) {
                initCheckbox.call(v);
            });
        },
        _resetNodeVisible: function($li, layerDef) {
            if (layerDef.state.visibility) {
                $li.removeClass("invisible").find('span.layer-state:first').attr("title", "");
            }
            this._resetNodeOutOfScale($li, layerDef);
        },
        _resetSourceAtTree: function(source) {
            var self = this;
            function resetSourceAtTree(layer, parent) {
                var $li = $('li[data-id="' + layer.options.id + '"]', self.element);
                self._resetNodeSelected($li, layer.options);
                self._resetNodeInfo($li, layer.options);
                self._resetNodeVisible($li, layer);
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        resetSourceAtTree(layer.children[i], layer);
                    }
                }
            }
            ;
            resetSourceAtTree(source.configuration.children[0], null);
        },
        _changeChildren: function(changed) {
            if (changed.children) {
                for (var layerId in changed.children) {
                    var $li = $('li[data-id="' + layerId + '"]', this.element);
                    if ($li.length !== 0) {
                        if (changed.children[layerId].options) {
                            this._resetNodeSelected($li, changed.children[layerId].options);
                            this._resetNodeInfo($li, changed.children[layerId].options);
                            if (changed.children[layerId].options.state) {
                                this._resetNodeVisible($li, changed.children[layerId].options);
                            }
                        } else if (changed.children[layerId].state) {
                            this._resetNodeOutOfScale($li, changed.children[layerId]);
                        }
                    }
                }
            }
        },
        _removeChild: function(changed) {
            var self = this;
            if (changed && changed.sourceIdx && changed.childRemoved) {
                var source = this.model.getSource(changed.sourceIdx);
                $('ul.layers:first li[data-id="' + changed.childRemoved.layer.options.id + '"]', self.element).remove();
            }
        },
        _onSourceRemoved: function(event, removed) {
            if (removed && removed.source && removed.source.id) {
                $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element).remove();
                this._setSourcesCount();
            }
        },
        _onSourceLoadStart: function(event, option) {
            if (option.source && this.sourceAtTree[option.source.id ]) {
                this.loadStarted[option.source.id ] = true;
                var source_li = $('li[data-sourceid="' + option.source.id + '"][data-type="root"]', this.element);
                if ($('input.layer-selected:first', source_li).is(':checked') && !source_li.hasClass('invisible')) {
                    source_li.attr('data-state', 'loading').find('span.layer-state:first').attr("title", source_li.attr('data-title'));
                }
            }
        },
        _onSourceLoadEnd: function(event, option) {
            if (option.source && this.sourceAtTree[option.source.id ] && this.loadStarted[option.source.id]) {
                this.loadStarted[option.source.id] = false;
                var source_li = $('li[data-sourceid="' + option.source.id + '"][data-type="root"]', this.element);
                source_li.attr('data-state', '');
                this._resetSourceAtTree(option.source);
            }
        },
        _onSourceLoadError: function(event, option) {
            if (option.source && this.sourceAtTree[option.source.id ] && this.loadStarted[option.source.id]) {
                this.loadStarted[option.source.id] = false;
                var source_li = $('li[data-sourceid="' + option.source.id + '"][data-type="root"]', this.element);
                source_li.attr('data-state', 'error').find('span.layer-title:first').attr("title", option.error.details);
            }
        },
        _subStringText: function(text) {
            if (text.length <= this.options.titlemaxlength) {
                return text;
            } else {
                for (var i = this.options.titlemaxlength; i > 0; i--) {
                    if (text[i] === " ") {
                        text = text.substring(0, i) + "...";
                        break;
                    }
                }
                if (text.length < 2 || text.length > this.options.titlemaxlength + 3)
                    return text.substring(0, this.options.titlemaxlength) + "...";
                else
                    return text;
            }
        },
        _getNodeType: function(node, isroot) {
            if (isroot) {
                return this.consts.root;
            } else if (node.children) {
                return this.consts.group;
            } else {
                return this.consts.simple;
            }
        },
        _getNodeProporties: function(nodeConfig) {
            var conf = {
                selected: nodeConfig.options.treeOptions.selected,
                selectable: nodeConfig.options.treeOptions.allow.selected,
                info: nodeConfig.options.treeOptions.info,
                infoable: nodeConfig.options.treeOptions.allow.info,
                reorderable: nodeConfig.options.treeOptions.allow.reorder
            };
            if (nodeConfig.children) {
                conf["toggle"] = nodeConfig.options.treeOptions.toggle;
                conf["toggleable"] = nodeConfig.options.treeOptions.allow.toggle;
            } else {
                conf["toggle"] = null;
                conf["toggleable"] = null;
            }

            if (nodeConfig.state.outOfScale) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.outOfScale
                };
            } else if (nodeConfig.state.outOfBounds) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.outOfBounds
                };
            } else if (!nodeConfig.state.visibility) {
                conf["visibility"] = {
                    state: "invisible",
                    tooltip: this.transConst.parentinvisible
                };
            } else {
                conf["visibility"] = {
                    state: "",
                    tooltip: ""
                };
            }
            return conf;
        },
        _toggleContent: function(e) {
            var me = $(e.target);
            if (!me.parents('li:first').hasClass('toggleable'))
                return false;
            if (me.hasClass("iconFolderActive")) {
                me.removeClass("iconFolderActive");
                me.parents('li:first').removeClass("showLeaves");
            } else {
                me.addClass("iconFolderActive");
                me.parents('li:first').addClass("showLeaves");
            }
            var li = me.parents('li:first[data-sourceid]');
            if (li.length > 0) {
                this._resetSourceAtTree(this.model.getSource({id: li.attr('data-sourceid')}));
            }
            return false;
        },
        _toggleSelected: function(e) {
            var li = $(e.target).parents('li:first');
            var tochange = {sourceIdx: {id: li.attr('data-sourceid')}, options: {}};
            if (li.attr('data-type') === this.consts.root) {
                tochange.options = {configuration: {options: {visibility: $(e.target).is(':checked')}}};
            } else {
                tochange.options = {children: {}};
                tochange.options.children[li.attr('data-id')] = {options: {treeOptions: {selected: $(e.target).is(':checked')}}};
            }
            tochange.options['type'] = 'selected';
            this.model.changeSource({change: tochange});
        },
        _toggleInfo: function(e) {
            var li = $(e.target).parents('li:first');
            var tochange = {sourceIdx: {id: li.attr('data-sourceid')}, options: {children: {}, type: 'info'}};
            tochange.options.children[li.attr('data-id')] = {options: {treeOptions: {info: $(e.target).is(':checked')}}};
            this.model.changeSource({change: tochange});
        },
        currentMenu: null,
        closeMenu: function(menu) {
            //menu.find('.layer-zoom').off('click');
            //menu.find('.layer-metadata').off('click');
            menu.off('click').remove();
        },
        _toggleMenu: function(e) {
            var self = this;
            function createMenu($element, sourceId, layerId) {
                var source = self.model.findSource({id: sourceId})[0];
                var menu = $(self.menuTemplate.clone().attr("data-menuLayerId", layerId).attr("data-menuSourceId", sourceId));
                var exitButton = menu.find('.exit-button');
                var previousMenu = self.currentMenu;

                if (self.currentMenu == menu) {
                    return;
                }

                self.currentMenu = menu;

                if (previousMenu) {
                    self.closeMenu(previousMenu);
                }

                exitButton.on('click', function(e) {
                    self.closeMenu(menu)
                });

                if ($element.parents('li:first').attr('data-type') !== self.consts.root) {
                    menu.find('#layer-opacity').remove();
                    menu.find('#layer-opacity-title').remove();
                }

                menu.removeClass('hidden');
                $element.append(menu);
                $(menu).on('mousedown mousemove', function(e) {
                    e.stopPropagation();
                });
                
                if ($.inArray("layerremove", self.options.menu) !== -1){
                    menu.find('.layer-remove-btn').on('click', $.proxy(self._removeSource, self));
                } else {
                    menu.find('.layer-remove-btn').remove();
                }
                if ($.inArray("opacity", self.options.menu) !== -1 && menu.find('#layer-opacity').length > 0) {
                    $('.layer-opacity-handle').attr('unselectable', 'on');
                    new Dragdealer('layer-opacity', {
                        x: source.configuration.options.opacity,
                        horizontal: true,
                        vertical: false,
                        speed: 1,
                        steps: 100,
                        handleClass: "layer-opacity-handle",
                        animationCallback: function(x, y) {
                            var percentage = Math.round(x * 100);
                            $("#layer-opacity").find(".layer-opacity-handle").text(percentage);
                            self._setOpacity(self.model.findSource({id: sourceId})[0], percentage / 100.0);
                        }
                    });
                }
                if ($.inArray("zoomtolayer", self.options.menu) !== -1 && menu.find('.layer-zoom').length > 0) {
                    if (self.model.getLayerExtents({sourceId: sourceId, layerId: layerId, inherit: true})) {
                        $('.layer-zoom', menu).removeClass('inactive').on('click', $.proxy(self._zoomToLayer, self));
                    }
                }
                if ($.inArray("metadata", self.options.menu) === -1 || menu.find('.layer-metadata').length === 0 || isNaN(parseInt(source.origId))) {
                    $('.layer-metadata', menu).remove();
                } else {
                    var layer = self.model.findLayer({id: sourceId}, {id: layerId});
                    if (layer) {
                        $('.layer-metadata', menu).removeClass('inactive').on('click', $.proxy(self._showMetadata, self));
                    }
                }
            }

            var $btnMenu = $(e.target);
            var currentLayerId = $btnMenu.parents('li:first').attr("data-id");
            var currentSourceId = $btnMenu.parents('li[data-sourceid]:first').attr("data-sourceid");
            if ($('#layer-menu').length !== 0) {
                var layerIdMenu = $('#layer-menu').attr("data-menuLayerId");
                if (layerIdMenu !== currentLayerId) {
                    createMenu($btnMenu, currentSourceId, currentLayerId);
                    if ((self.element.offset().top + self.element.innerHeight()) < ($('.layer-menu').offset().top + $('.layer-menu').innerHeight())) {
                        $('#layer-menu').addClass('placeSouth');
                    }
                }
            } else {
                createMenu($btnMenu, currentSourceId, currentLayerId);
                if ((self.element.offset().top + self.element.innerHeight()) < ($('.layer-menu').offset().top + $('.layer-menu').innerHeight())) {
                    $('#layer-menu').addClass('placeSouth');
                }
            }
        },
        _setOpacity: function(source, opacity) {
            this.model.setOpacity(source, opacity);
        },
        _removeSource: function(e) {
            var layer = $(e.currentTarget).closest("li").data();
            var types = this.consts;
            var model = this.model;

            if (layer.sourceid && layer.type) {
                switch (layer.type) {
                    case types.root:
                        model.removeSource({remove: {sourceIdx: {id: layer.sourceid}}});
                        break;
                    case types.group:
                    case types.simple:
                        model.changeSource({change: {layerRemove: {sourceIdx: {id: layer.sourceid}, layer: {options: {id: layer.id}}}}});
                        break;
                }
            }

            this._setSourcesCount();
        },
        _showLegend: function(elm) {
        },
        _exportKml: function(elm) {
        },
        _zoomToLayer: function(e) {
            var options = {
                sourceId: $(e.target).parents('div.layer-menu:first').attr("data-menuSourceId"),
                layerId: $(e.target).parents('div.layer-menu:first').attr("data-menuLayerId"),
                inherit: true
            };
            this.model.zoomToLayer(options);
        },
        _showMetadata: function(e) {
            Mapbender.Metadata.call(
                    this.options.target,
                    {id: $(e.target).parents('div.layer-menu:first').attr("data-menuSourceId")},
            {id: $(e.target).parents('div.layer-menu:first').attr("data-menuLayerId")}
            );
        },
        _setSourcesCount: function() {
            var countObj = {};
            $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm) {
                countObj[$(elm).attr('data-sourceid')] = true;
            });
            var num = 0;
            for (s in countObj)
                num++;
            $(this.element).find('#counter').text(num);
        },
        _removeAllSources: function(e) {
            var self = this;
            if (Mapbender.confirm(Mapbender.trans("mb.core.layertree.confirm.allremove"))) {
                $(this.element).find("#list-root li[data-sourceid]").each(function(idx, elm) {
                    var sourceId = $(elm).attr('data-sourceid');
                    self.model.removeSource({remove: {sourceIdx: {id: sourceId}}});
                });
            }
            this._setSourcesCount();
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback) {
            this.open(callback);
        },
        /**
         * Opens a dialog with a layertree (if options.type == 'dialog')
         */
        open: function(callback) {
            this.callback = callback ? callback : null;
            if (this.options.type === 'dialog') {
                var self = this;
                if (!this.popup || !this.popup.$element) {
                    this._createTree();
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('title'),
                        modal: false,
                        resizable: true,
                        draggable: true,
                        closeButton: false,
                        closeOnESC: false,
                        content: [self.element.show()],
                        destroyOnClose: true,
                        width: 350,
                        height: 500,
                        cssClass: 'customLayertree',
                        buttons: {
                            'ok': {
                                label: Mapbender.trans("mb.core.layertree.popup.btn.ok"),
                                cssClass: 'button right',
                                callback: function() {
                                    self.close();
                                }
                            }
                        }
                    });
                    this._reset();
                    this.popup.$element.on('close', $.proxy(this.close, this));
                } else {
                    this._reset();
                    this.popup.open();
                }
            }
        },
        /**
         * closes a dialog with a layertree (if options.type == 'dialog')
         */
        close: function() {
            if (this.options.type === 'dialog') {
                if (this.popup) {
                    $("ul.layers:first", this.element).empty();
                    $(this.element).hide().appendTo("body");
                    this._unSortable();
                    this.created = false;
                    if (this.popup.$element) {
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
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);

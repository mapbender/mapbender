(function($) {
    $.widget("mapbender.mbLayertree", {
        options: {
            type: 'element',
            displaytype: 'tree',
            autoOpen: false,
            useTheme: false,
            target: null,
            titlemaxlength: 40,
            layerInfo: true, //!!!
            showBaseSource: true,
            showHeader: false,
            hideNotToggleable: false,
            hideSelect: false,
            hideInfo: false,
            themes: null,
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
            theme: "theme",
            root: "root",
            group: "group",
            simple: "simple"
        },
        transConst: {
            outOfScale: '',
            outOfBounds: '',
            parentInvisible: ''
        },
        _create: function() {
            this.loadStarted = {};
            this.sourceAtTree = {};
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
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.template = $('li', this.element).remove();
            this.template.removeClass('hidden');
            this.menuTemplate = $('#layer-menu', this.template).remove();

            this.mbMap = $("#" + this.options.target).data("mapbenderMbMap");
            this.model = this.mbMap.getModel();
            if (this.options.type === 'element') {
                this._createTree();
            } else if (this.options.type === 'dialog' && new Boolean(this.options.autoOpen).valueOf() === true) {
                this.open();
            }
            this.element.removeClass('hidden');
            this._trigger('ready');
            this._ready();
        },
        _getConfiguredLayersetConfigs: function() {
            var layerSetIds = this.mbMap.options.layersets.reverse();
            var lsConfigs = [];
            for (var i = 0; i < layerSetIds.length; ++i) {
                var layerSetId = layerSetIds[i];
                var layerSetConfig = Mapbender.configuration.layersets[layerSetId] || Mapbender.configuration.layersets["" + layerSetId];
                if (typeof layerSetConfig === 'undefined') {
                    console.error("Can't find layerset in config", layerSetId, Mapbender.configuration.layersets);
                    throw new Error("Missing layerset with id '" + layerSetId + "'");
                }
                lsConfigs.push(layerSetConfig);
            }
            return lsConfigs;
        },
        _getConfiguredSourceConfigs: function() {
            var lsConfigs = this._getConfiguredLayersetConfigs();
            var sourceConfigs = [];
            for (var i = 0; i < lsConfigs.length; ++i) {
                var lsConfig = lsConfigs[i];
                for (var j = 0; j < lsConfig.length; ++j) {
                    var sourceConfigWrapper = lsConfig[j];
                    _.forEach(sourceConfigWrapper, function(sourceConfig, sourceId) {
                        // HACK: put a string id on the source, so we can render it in an attribute later
                        // @todo: fix the configuration server-side. Source definition should always come with a built-in id
                        sourceConfig.id = "" + sourceId;
                        sourceConfigs.push(sourceConfig);
                    });
                }
            }
            return sourceConfigs.reverse();
        },
        _createTree: function() {
            var self = this;
            var sources = this._getConfiguredSourceConfigs();
            if (this.created)
                this._unSortable();
            for (var i = (sources.length - 1); i > -1; i--) {
                if (!sources[i].configuration.isBaseSource
                    || (sources[i].configuration.isBaseSource && this.options.showBaseSource)) {
                    if (this.options.displaytype === "tree") {
                        var li_s = this._createSourceTree(sources[i], this.model.getScale());
                        this._addNode(li_s, sources[i]);
                    } else {
                        return;
                    }
                    this.sourceAtTree[sources[i].id ] = {
                        id: sources[i].id
                    };
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
            $(document).bind('mbmapsourcemoved', $.proxy(self._onSourceChanged, self));
            this.created = true;
        },
        _addNode: function($toAdd, source) {
            var $targetList = $("ul.layers:first", this.element);
            if (this.options.useTheme) {
                // Collect layerset <=> theme relations
                // @todo 3.1.0: this should happen server-side
                var layerset = this._findLayersetWithSource(source);
                var theme = {};
                $.each(this.options.themes, function(idx, item) {
                    if (item.id === layerset.id)
                        theme = item;
                });
                if (theme.useTheme) {
                    var $layersetEl = this._createThemeNode(layerset, theme);
                    $targetList = $("ul.layers:first", $layersetEl);
                }
            }
            $targetList.append($toAdd);
        },
        _reset: function() {
            this._resetEvents();
            this._resetSortable();
            this._resetCheckboxes();
            this._setSourcesCount();
        },
        _createEvents: function() {
            var self = this;
            this.element.on('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleSelected, self));
            this.element.on('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.on('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.on('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));
            this.element.on('click', '.selectAll', $.proxy(self._selectAll, self));
        },
        _removeEvents: function() {
            var self = this;
            this.element.off('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleSelected, self));
            this.element.off('click', '.iconFolder', $.proxy(self._toggleContent, self));
            this.element.off('click', '#delete-all', $.proxy(self._removeAllSources, self));
            this.element.off('click', '.layer-menu-btn', $.proxy(self._toggleMenu, self));
            this.element.off('click', '.selectAll', $.proxy(self._selectAll, self));

        },
        _resetEvents: function() {
            this._removeEvents();
            this._createEvents();
        },
        _resetCheckboxes: function() {
            var self = this;
            this.element.off('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.off('change', 'input[name="info"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="sourceVisibility"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="selected"]', $.proxy(self._toggleSelected, self));
            this.element.on('change', 'input[name="info"]', $.proxy(self._toggleSelected, self));
            $('input[type="checkbox"]', this.element).mbCheckbox();
        },
        _resetSortable: function() {
            this._unSortable();
            this._createSortable();
        },
        _unSortable: function() {
        },
        /**
         * Applies the new (going by DOM) layer order inside a source.
         *
         * @param $sourceContainer
         * @private
         */
        _updateSource: function($sourceContainer) {
            // this will capture the "configurationish" layer ids (e.g. "1_0_4_1") from
            // all layers in the source container in DOM order
            var sourceId = $sourceContainer.attr('data-sourceid');
            var layerIdOrder = [];
            $('li.leave[data-type="simple"]', $sourceContainer).each(function() {
                var $t = $(this);
                var layerId = $t.attr('data-id');
                if (typeof layerId !== "undefined") {
                    layerIdOrder.push("" + layerId);
                }
            });
            this.mbMap.setSourceLayerOrder(sourceId, layerIdOrder.reverse());
        },
        /**
         * Applies the new (going by DOM) ordering between sources.
         *
         * @private
         */
        _updateSourceOrder: function() {
            var $roots = $('ul.layers li[data-type="root"]', this.element);
            var sourceIds = $roots.map(function() {
                return $(this).attr('data-sourceid');
            }).get().reverse();
            this.mbMap.reorderSources(sourceIds);
        },
        _createSortable: function() {
            var self = this;
            $("ul.layers", this.element).each(function() {
                $(this).sortable({
                    axis: 'y',
                    items: "> li:not(.notreorder)",
                    distance: 6,
                    cursor: "move",
                    update: function(event, ui) {
                        var $elm = $(ui.item);
                        var type = $elm.attr('data-type');
                        switch (type) {
                            case 'theme':
                            case 'root':
                                self._updateSourceOrder();
                                break;
                            case 'simple':
                            case 'group':
                                self._updateSource($elm.closest('.serviceContainer'));
                                break;
                            default:
                                console.warn("Warning: unhandled element in layertree sorting", type, $elm);
                                break;
                        }
                    }
                });
            });
        },
        _isThemeVisible: function(layerset) {
            for (var i = 0; i < layerset.content.length; i++) {
                for (id in layerset.content[i]) {
                    if (layerset.content[i][id].configuration.children[0].options.treeOptions.selected) {
                        return true;
                    }
                }
            }
            return false;
        },
        _createThemeNode: function(layerset, theme) {
            var $li = $('ul.layers:first > li[data-layersetid="' + layerset.id + '"]', this.element);
            if ($li.length === 1) {
                return $li;
            } else {
                $li = this.template.clone();
            }
            $('ul.layers:first', this.element).append($li);
            $li.removeClass('hide-elm').addClass('toggleable');
            $li.attr('data-layersetid', layerset.id);
            $li.removeAttr('data-id');
            $li.removeAttr('data-sourceid');
            $li.attr('data-type', this.consts.theme).attr('data-title', layerset.title);
            $li.addClass("themeContainer");
            if (theme.opened)
                $li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
            else
                $li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");
            $('span.layer-title:first', $li).text(layerset.title);
            $('span.layer-spinner:first', $li).remove();
            $('span.layer-state:first', $li).remove();
            $('div.featureInfoWrapper', $li).remove();
            $('div.selectedWrapper', $li).remove();
            if (!theme.allSelected) {
                $('div.selectAll', $li).remove();
            }
            if (!theme.sourceVisibility) {
                $('div.sourceVisibilityWrapper', $li).remove();
            } else {
                $('div.sourceVisibilityWrapper input[name="sourceVisibility"]', $li).prop('checked',
                    this._isThemeVisible(layerset));
            }
            $('.layer-menu-btn', $li).remove();
            return $li;
        },
        _createNode: function(source, sourceEl, config, isroot) {
            var $li = this.template.clone();
            $li.data('options', sourceEl.options || {});

            $li.removeClass('hide-elm');
            $li.attr('data-id', sourceEl.options.id);
            $li.attr('data-sourceid', source.id);
            var nodeType = this._getNodeType(sourceEl, isroot);
            $('div.selectAll', $li).remove();
            $('div.sourceVisibilityWrapper', $li).remove();
            $li.attr('data-type', nodeType).attr('data-title', sourceEl.options.title);
            if (nodeType === 'simple') {
                if (!sourceEl.options.name) {
                    console.warn("Source element is missing the layer name", nodeType, sourceEl);
                }
                $li.attr('data-layername', sourceEl.options.name);
            }
            if (nodeType === this.consts.root || nodeType === this.consts.group) {
                $('.featureInfoWrapper:first', $li).remove();
                if (nodeType === this.consts.root) {
                    $li.addClass("serviceContainer");
                } else if (nodeType === this.consts.group) {
                    $li.addClass("groupContainer");
                }
                if (config.toggle === true) {
                    $li.addClass("showLeaves").find(".iconFolder").addClass("iconFolderActive");
                } else {
                    $li.removeClass("showLeaves").find(".iconFolder").removeClass("iconFolderActive");
                }
                if (config.toggleable) {
                    $li.addClass('toggleable');
                }
            }
            $li.addClass(config.reorder);
            $li.find('.layer-state').attr('title', config.visibility.tooltip);
            $li.find('input.layer-selected').prop('checked', config.selected ? true : false);
            if (!config.selectable)
                $li.find('input.layer-selected').prop('disabled', true);
            $li.find('input.layer-info').prop('checked', config.info ? true : false);
            if (!config.infoable || config.infoable === '0')
                $li.find('input.layer-info').prop('disabled', true);
            var infoHidden = false;
            if (this.options.hideInfo) {
                infoHidden = true;
                $('input[name="info"]', $li).parents('.checkWrapper:first').remove();
            }
            var selectHidden = false;
            if (this.options.hideSelect && config.selected && !config.selectable &&
                (nodeType === this.consts.root || nodeType === this.consts.group)) {
                selectHidden = true;
                $('input[name="selected"]', $li).parents('.checkWrapper:first').remove();
            }
            if (config.toggleable === false && this.options.hideNotToggleable) {
                var $folder = $li.find('.iconFolder');
                if (selectHidden && infoHidden) {
                    $folder.addClass('placeholder')
                }
                $folder.removeClass('iconFolder');
            }
            $li.find('.layer-title:first').attr('title', sourceEl.options.title).text(this._subStringText(
                sourceEl.options.title));
            if (this.options.menu.length === 0) {
                $li.find('.layer-menu-btn').remove();
            }
            var $childList = $li.find('ul:first');
            if (sourceEl.children) {
                $childList.attr('id', 'list-' + sourceEl.options.id);
                if (config.toggle) {
                    $childList.addClass("closed");
                }
            } else {
                $childList.remove();
            }

            return $li;
        },
        _createSourceTree: function(source, scale) {
            var li = this._createLayerNode(source, source.configuration.children[0], scale, source.type, true);
            if (source.configuration.status !== 'ok') {
                li.attr('data-state', 'error').find('span.layer-title:first').attr("title",
                    source.configuration.status);
            }
            return li;
        },
        _createLayerNode: function(source, sourceEl, scale, isroot) {
            var config = this._getNodeProporties(sourceEl);
            var li = this._createNode(source, sourceEl, config, isroot);
            if (sourceEl.children) {
                for (var j = sourceEl.children.length; j > 0; j--) {
                    li.find('ul:first').append(this._createLayerNode(source, sourceEl.children[j - 1], scale, false));
                }
            }
            return li;
        },
        _onSourceAdded: function(event, options) {
            if (!this.created || !options.added)
                return;
            var added = options.added;
            if (added.source.configuration.baseSource && !this.options.showBaseSource) {
                return;
            }
            if (this.options.displaytype === "tree") {
                var li_s = this._createSourceTree(added.source, this.model.getScale());
                var first_li = $(this.element).find('ul.layers:first li:first');
                if (first_li && first_li.length !== 0) {
                    first_li.before(li_s);
                } else {
                    $(this.element).find('ul.layers:first').append($(li_s));
                }
            } else {
                return;
            }
            this.sourceAtTree[added.source.id ] = {
                id: added.source.id
            };
            this._reset();
        },
        _onSourceChanged: function(event, options) {
            if (options.changed && options.changed.children) {
                this._changeChildren(options.changed);
            } else if (options.changed && options.changed.childRemoved) {
                this._removeChild(options.changed);
            } else {
                console.warn("Layertree: unhandled event", arguments);
                // @todo: synchronize with new source states from model
                // _createTree almost works but creates duplicates for every source
                // this._createTree();
            }

        },
        _isThemeChecked: function($li){
            if(this.options.useTheme === false) { // a theme exists
                return true;
            }
            var $lith = $li.parents('li.themeContainer:first');
            if($lith.length === 1){
                var theme = {};
                var lsid = $lith.attr('data-layersetid');
                $.each(this.options.themes, function(idx, item) {
                    if (item.id === lsid)
                        theme = item;
                });
                if(theme.sourceVisibility){
                    return $('input[name="sourceVisibility"]:first', $lith).prop('checked');
                } else {
                    return true;
                }
            } else if($lith.length === 0){ // no theme exists
                return true;
            }
            return false;
        },
        _redisplayLayerState: function($li, state) {
            if (state.outOfScale) {
                $li.addClass("invisible").find('span.layer-state').attr("title", "out of scale");
            } else if (state.visibility) {
                $li.removeClass("invisible").find('span.layer-state:first').attr("title", "");
            } else {
                // @todo (TBD): is this really a separate state, or is visibility always := !outOfScale?
                $li.addClass("invisible").find('span.layer-state').attr("title", "");
            }
        },
        _resetSourceAtTree: function(source) {
            // console.warn("Skipping _resetSourceAtTree call"); return;
            var self = this;
            function resetSourceAtTree(layer, parent) {
                var $li = $('li[data-id="' + layer.options.id + '"]', self.element);
                self._redisplayLayerState($li, layer.state);
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        resetSourceAtTree(layer.children[i], layer);
                    }
                }
            }
            resetSourceAtTree(source.configuration.children[0], null);
        },
        _changeChildren: function(changed) {
            if (changed.children) {
                for (var layerId in changed.children) {
                    var $li = $('li[data-id="' + layerId + '"]', this.element);
                    if ($li.length !== 0) {
                        if ($li.attr("data-type") === this.consts.root && !this._isThemeChecked($li)){
                            continue;
                        }
                        if (changed.children[layerId].state) {
                            this._redisplayLayerState($li, changed.children[layerId].state);
                        }
                        if (changed.children[layerId].options) {
                            if(changed.children[layerId].options.treeOptions.allow){
                                var chk_selected = $('input[name="selected"]:first', $li);
                                if(changed.children[layerId].options.treeOptions.allow.selected === true){
                                    chk_selected.prop('disabled', false).mbCheckbox();
                                    $li.removeClass('invisible');
                                } else if(changed.children[layerId].options.treeOptions.allow.selected === false){
                                    chk_selected.prop('disabled', true).mbCheckbox();
                                    $li.addClass('invisible');
                                }
                            }
                        }
                    }
                }
            }
        },
        _removeChild: function(changed) {
            var self = this;
            if (changed && changed.sourceIdx && changed.childRemoved) {
                $('ul.layers:first li[data-id="' + changed.childRemoved.layer.options.id + '"]', self.element).
                    remove();
            }
        },
        _onSourceRemoved: function(event, removed) {
            if (removed && removed.source && removed.source.id) {
                var $source = $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element);
                var $theme = $source.parents('.themeContainer:first');
                $('ul.layers:first li[data-sourceid="' + removed.source.id + '"]', this.element).remove();
                if ($theme.length && $theme.find('.serviceContainer').length === 0){
                    $theme.remove();
                }
                this._setSourcesCount();
            }
        },
        _onSourceLoadStart: function(event, options) {
            if (options.source && this.sourceAtTree[options.source.id ]) {
                this.loadStarted[options.source.id ] = true;
                var source_li = $('li[data-sourceid="' + options.source.id + '"][data-type="root"]', this.element);
                if (options.source.configuration.children[0].options.treeOptions.selected && !source_li.hasClass(
                    'invisible')) {
                    source_li.attr('data-state', 'loading').find('span.layer-state:first').attr("title",
                        source_li.attr('data-title'));
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
                source_li.attr('data-state', 'error').find('span.layer-title:first').attr("title",
                    option.error.details);
            }
        },
        _subStringText: function(text) {
            if(text === null) {
                return '';
            }
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
            var $me = $(e.target);
            var $parent = $me.parents('li:first');
            if (!$parent.hasClass('toggleable'))
                return false;
            if ($me.hasClass("iconFolderActive")) {
                $me.removeClass("iconFolderActive");
                $parent.removeClass("showLeaves");
            } else {
                $me.addClass("iconFolderActive");
                $parent.addClass("showLeaves");
            }
            var li = $me.parents('li:first[data-sourceid]');
            return false;
        },
        _selectAll: function(e) {
            var $sourceVsbl = $(e.target);
            var $li = $sourceVsbl.parents('li:first');
            $('.serviceContainer input[name="selected"]', $li).prop('checked', true).trigger('change');
            return false;
        },
        _toggleSelected: function(e) {
            var $target = $(e.target);
            var $listNode = $target.closest('.leave[data-type]');
            switch ($listNode.attr('data-type')) {
                case 'theme':
                    var serviceNodes = $('.serviceContainer', $listNode).get();
                    for (var i = 0; i < serviceNodes.length; ++i) {
                        this._updateServiceState($(serviceNodes[i]));
                    }
                    break;
                case 'root':
                    this._updateServiceState($listNode);
                    break;
                case 'group':
                case 'simple':
                    this._updateLeafStates($listNode);
                    break;
                default:
                    throw new Error("Unhandled node type " + $listNode.attr('data-type'));
            }
        },
        _updateServiceState: function($node) {
            var sourceId = $node.attr('data-sourceid');
            var themeActive = $('input[name="sourceVisibility"]', $node.parentsUntil(this.element, 'li.themeContainer')).prop('checked');
            var sourceObj = this.model.getSourceById(sourceId);
            var active = $('.leaveContainer:first input[name="selected"]', $node).prop('checked');
            if (typeof themeActive !== 'undefined') {
                active &= themeActive;
            }
            if (active) {
                // apply any layer order changes made while the source was inactive
                // this avoids "blinking in" with wrong data followed by a layer
                // ordering update
                this._updateLeafStates($node);
            }
            this.mbMap.setSourceState(sourceObj,  active);
        },
        _updateLeafStates: function($startNode) {
            var $serviceNode = $startNode.closest('.serviceContainer');
            var sourceId = $serviceNode.attr('data-sourceid');
            var sourceObj = this.model.getSourceById(sourceId);
            // collect affected layer names tree-down (to support events on group / root)
            var affectedLeaves = $('.leave[data-type="simple"]', $serviceNode).get();
            for (var i = 0; i < affectedLeaves.length; ++i) {
                // for each layer: collect checkbox values up the entire tree (layer, layer group, source, theme)
                var layerNode = affectedLeaves[i];
                var layerName = $(layerNode).attr('data-layername');
                if (!layerName) {
                    console.error("Can't find layername from layer node", layerNode);
                    throw new Error("Can't change layer visibility; no layer name");
                }
                // checkboxes to scan are inside leaf layer node itself plus all parent containers
                var scanNodes = [layerNode].concat($(layerNode).parents('li.leave').get());
                var newStateVisible = 1;
                var newStateInfo = 1;
                for (var j = 0; j < scanNodes.length; ++j) {
                    // NOTE: "theme" checkboxes use a different input name ("sourceVisibility")
                    var $cbVisible = $('>.leaveContainer input[name="selected"],input[name="sourceVisibility"]', scanNodes[j]);
                    var $cbInfo = $('>.leaveContainer input[name="info"],input[name="sourceVisibility"]', scanNodes[j]);
                    if ($cbVisible.length) {
                        newStateVisible &= $cbVisible.prop('checked');
                    }
                    if ($cbInfo.length) {
                        newStateInfo &= $cbInfo.prop('checked');
                    }
                }
                // apply
                sourceObj.updateLayerState(layerName, {
                    visible: !!newStateVisible,
                    queryable: !!newStateInfo
                });
            }
        },
        currentMenu: null,
        closeMenu: function(menu) {
            //menu.find('.layer-zoom').off('click');
            //menu.find('.layer-metadata').off('click');
            menu.off('click').remove();
            return false;
        },
        _toggleMenu: function(e) {
            var self = this;
            function createMenu($element, sourceId, layerId) {
                var atLeastOne = false;
                var $node = $element.closest('li.leave');
                var nodeOptions = $node.data('options') || {};
                var isRootLayerNode = $node.attr('data-type') === 'root';

                var source = self.model.getSourceById(sourceId);
                var menu = $(self.menuTemplate.clone().attr("data-menuLayerId", layerId).attr("data-menuSourceId",
                    sourceId));
                var exitButton = menu.find('.exit-button');
                var previousMenu = self.currentMenu;

                if (self.currentMenu === menu) {
                    return;
                }

                self.currentMenu = menu;

                if (previousMenu) {
                    self.closeMenu(previousMenu);
                }

                exitButton.on('click', function(e) {
                    self.closeMenu(menu);
                });

                var removeButton = menu.find('.layer-remove-btn');
                atLeastOne = removeButton.length > 0;
                removeButton.on('click', $.proxy(self._removeSource, self));

                if ($element.parents('li:first').attr('data-type') !== self.consts.root) {
                    menu.find('#layer-opacity').remove();
                    menu.find('#layer-opacity-title').remove();
                }

                menu.removeClass('hidden');
                $element.after(menu);
                $(menu).on('mousedown mousemove', function(e) {
                    e.stopPropagation();
                });

                if ($.inArray("opacity", self.options.menu) !== -1 && menu.find('#layer-opacity').length > 0) {
                    atLeastOne = true;
                    $('.layer-opacity-handle').attr('unselectable', 'on');
                    new Dragdealer('layer-opacity', {
                        x: self.model.getSourceById(sourceId).getOpacity(),
                        horizontal: true,
                        vertical: false,
                        speed: 1,
                        steps: 100,
                        handleClass: "layer-opacity-handle",
                        animationCallback: function(x, y) {
                            var percentage = Math.round(x * 100);
                            $("#layer-opacity").find(".layer-opacity-handle").text(percentage);
                            self.model.getSourceById(sourceId).setOpacity(x);
                        }
                    });
                }
                var showZoomTo = $.inArray("zoomtolayer", self.options.menu) !== -1 && menu.find('.layer-zoom').length > 0;
                var currentSrs = self.model.getCurrentProjectionCode();
                showZoomTo = showZoomTo && nodeOptions.bbox && nodeOptions.bbox[currentSrs];
                if (showZoomTo) {
                    $('.layer-zoom', menu).removeClass('inactive').on('click', function() {
                        // SRS may theoretically have changed since binding the event handler, get it again
                        var currentSrsOnClick = self.model.getCurrentProjectionCode();
                        var extent = (nodeOptions.bbox || {})[currentSrsOnClick];
                        if (extent) {
                            // @todo 3.1.0: move to model
                            self.model.map.getView().fit(extent);
                        } else {
                            console.warn("Empty extent for current projection", currentSrsOnClick);
                        }
                    });
                    atLeastOne = true;
                } else {
                    $('.layer-zoom', menu).remove();
                }
                var showMetaData = $.inArray("metadata", self.options.menu) !== -1;
                showMetaData = showMetaData && menu.find('.layer-metadata').length;
                showMetaData = showMetaData && (!source.wmsloader);
                var $mdMenu = $('.layer-metadata', menu);
                if (showMetaData) {
                    var layerName = nodeOptions.name;
                    $mdMenu.removeClass('inactive');
                    $mdMenu.on('click', function() {
                        Mapbender.Metadata.loadAsPopup(sourceId, layerName);
                    });
                    atLeastOne = true;
                } else {
                    $mdMenu.remove();
                }

                var dims = source.configuration.options.dimensions || [];
                var showDims = $.inArray("dimension", self.options.menu) !== -1;
                showDims = showDims && dims.length && source.type === 'wms';
                showDims = showDims && isRootLayerNode;

                if (showDims) {
                    atLeastOne = true;
                    var lastItem = $('.layer-dimension-checkbox', menu).prev();
                    var dimCheckbox = $('.layer-dimension-checkbox', menu).remove();
                    var dimTitle = $('.layer-dimension-title', menu).remove();
                    var dimBar = $('.layer-dimension-bar', menu).remove();
                    var dimTextfield = $('.layer-dimension-textfield', menu).remove();
                    $.each(dims, function(idx, item) {
                        var chkbox = dimCheckbox.clone();
                        var title = dimTitle.clone();
                        lastItem.after(chkbox);
                        var inpchkbox = chkbox.find('.checkbox');
                        inpchkbox.data('dimension', item);
                        inpchkbox.on('change', function(e) {
                            self._callDimension(source, $(e.target));
                        });
                        $(inpchkbox).mbCheckbox();
                        title.attr('title', title.attr('title') + ' ' + item.name);
                        title.attr('id', title.attr('id') + item.name);
                        chkbox.after(title);
                        if (item.type === 'single') {
                            var textf = dimTextfield.clone();
                            title.after(textf);
                            textf.val(item.extent);
                            inpchkbox.attr('data-value', item.extent);
                            lastItem = textf;
                        } else if (item.type === 'multiple' || item.type === 'interval') {
                            var bar = dimBar.clone();
                            title.after(bar);
                            bar.removeClass('layer-dimension-bar');
                            bar.attr('id', bar.attr('id') + item.name);
                            bar.find('.layer-dimension-handle').removeClass('layer-dimension-handle').
                                addClass('layer-dimension-' + item.name + '-handle').attr('unselectable', 'on');
                            lastItem = bar;
                            var dimHandler = Mapbender.Dimension(item);
                            var label = $('#layer-dimension-value-' + item.name, menu);
                            new Dragdealer('layer-dimension-' + item.name, {
                                x: dimHandler.partFromValue(dimHandler.getDefault()),
                                horizontal: true,
                                vertical: false,
                                speed: 1,
                                steps: dimHandler.getStepsNum(),
                                handleClass: 'layer-dimension-' + item.name + '-handle',
                                callback: function(x, y) {
                                    self._callDimension(source, inpchkbox);
                                },
                                animationCallback: function(x, y) {
                                    var value = dimHandler.valueFromPart(x);
                                    label.text(value);
                                    inpchkbox.attr('data-value', value);
                                }
                            });
                        } else {
                            Mapbender.error("Source dimension " + item.type + " is not supported.");
                            return;
                        }
                    });
                } else {
                    $('.layer-dimension-checkbox', menu).remove();
                    $('.layer-dimension-title', menu).remove();
                    $('.layer-dimension-bar', menu).remove();
                    $('.layer-dimension-textfield', menu).remove();
                }
                if(!atLeastOne) {
                    self.closeMenu(menu);
                    Mapbender.info(Mapbender.trans('mb.core.layertree.contextmenu.nooption'));
                }
            }

            var $btnMenu = $(e.target);
            var currentLayerId = $btnMenu.parents('li:first').attr("data-id");
            var currentSourceId = $btnMenu.parents('li[data-sourceid]:first').attr("data-sourceid");
            if ($('#layer-menu').length !== 0) {
                var layerIdMenu = $('#layer-menu').attr("data-menuLayerId");
                //removeMenu($('#layer-menu'));
                if (layerIdMenu !== currentLayerId) {
                    createMenu($btnMenu, currentSourceId, currentLayerId);
                }
            } else {
                createMenu($btnMenu, currentSourceId, currentLayerId);
            }
            return false;
        },
        _callDimension: function(source, chkbox) {
            var dimension = chkbox.data('dimension');
            var params = {};
            params[dimension['__name']] = (chkbox.is(':checked') && chkbox.attr('data-value')) || undefined;
            source.updateRequestParams(params);
            return true;
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
                        model.removeSource({
                            remove: {
                                sourceIdx: {
                                    id: layer.sourceid
                                }
                            }
                        });
                        break;
                    case types.group:
                    case types.simple:
                        model.changeSource({
                            change: {
                                layerRemove: {
                                    sourceIdx: {
                                        id: layer.sourceid
                                    },
                                    layer: {
                                        options: {
                                            id: layer.id
                                        }
                                    }
                                }
                            }
                        });
                        break;
                }
            }

            this._setSourcesCount();
        },
        _showLegend: function(elm) {
        },
        _exportKml: function(elm) {
        },
        _showMetadata: function(e) {
            Mapbender.Metadata.call(
                this.options.target,
                {
                    id: $(
                        e.target).
                        parents(
                            'div.layer-menu:first').
                        attr(
                            "data-menuSourceId")
                },
            {
                id: $(
                    e.target).
                    parents(
                        'div.layer-menu:first').
                    attr(
                        "data-menuLayerId")
            }
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
                    self.model.removeSource({
                        remove: {
                            sourceIdx: {
                                id: sourceId
                            }
                        }
                    });
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
                        title: self.element.attr('data-title'),
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
            }
        },
        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        },
        _findLayersetWithSource: function(source) {
            var layerset = null;
            Mapbender.Util.SourceTree.iterateLayersets(function(layersetDef, layersetId) {
                for (var i = 0; i < layersetDef.length; i++) {
                    if (layersetDef[i][source.origId]) {
                        layerset = {
                            id: layersetId,
                            title: Mapbender.configuration.layersetmap[layersetId],
                            content: layersetDef
                        };
                        // stop iteration
                        return false;
                    }
                }
            });
            return layerset;
        },
        _destroy: $.noop
    });

})(jQuery);

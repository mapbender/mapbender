(function ($) {
    $.widget("mapbender.mbLayertree", $.mapbender.mbDialogElement, {
        options: {
            useTheme: false,
            target: null,
            showBaseSource: true,
            allowReorder: true,
            hideSelect: false,
            hideInfo: false,
            themes: null,
            menu: []
        },
        model: null,
        template: null,
        menuTemplate: null,
        popup: null,
        _mobilePane: null,
        useDialog_: false,

        _create: function () {
            // see https://stackoverflow.com/a/4819886
            this.useDialog_ = this.checkDialogMode();
            var self = this;
            this._mobilePane = $(this.element).closest('#mobilePane').get(0) || null;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self._setup(mbMap);
            }, function () {
                Mapbender.checkTarget('mbLayertree');
            });
        },
        _setup: function (mbMap) {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.template = $('li.-fn-template', this.element).remove();
            this.template.removeClass('hidden -fn-template');
            this.menuTemplate = $('.layer-menu', this.template).remove();
            this.menuTemplate.removeClass('hidden');
            this.themeTemplate = $('li.-fn-theme-template', this.element).remove();
            this.themeTemplate.removeClass('hidden -fn-theme-template');

            this.model = mbMap.getModel();
            this._createTree();
            if (this.checkAutoOpen()) {
                this.open();
            }
            this._createEvents();
            this._trigger('ready');

            const hasNonPersistentScrollbars = navigator.userAgent.indexOf('Mac') >= 0 || navigator.userAgent.indexOf('Firefox') >= 0;
            if (hasNonPersistentScrollbars && this.element.closest('.sideContent').length) {
                this.element.closest('.container-accordion').css('width', 'calc(100% + 15px)');
                this.element.closest('.accordion-cell').css('padding-right', '15px');
            }
        },
        _createTree: function () {
            var sources = this.model.getSources();
            var $rootList = $('ul.layers:first', this.element);
            $rootList.empty();
            for (var i = (sources.length - 1); i > -1; i--) {
                if (this.options.showBaseSource || !sources[i].configuration.isBaseSource) {
                    var source = sources[i];
                    var $sourceNode = this._createSourceTree(sources[i]);
                    var themeOptions = this.options.useTheme && this._getThemeOptions(source.layerset);
                    if (themeOptions) {
                        var $themeNode = this._findThemeNode(source.layerset);
                        if (!$themeNode.length) {
                            $themeNode = this._createThemeNode(source.layerset, themeOptions);
                            $rootList.append($themeNode);
                        }
                        $('ul.layers:first', $themeNode).append($sourceNode);
                    } else {
                        $rootList.append($sourceNode);
                    }
                    this._resetSourceAtTree(sources[i]);
                }
            }

            this.reIndent_($rootList, false);
            this._reset();
        },
        _reset: function () {
            if (this.options.allowReorder) {
                this._createSortable();
            }
        },
        _createEvents: function () {
            var self = this;
            this.element.on('click', '.-fn-toggle-info:not(.disabled)', this._toggleInfo.bind(this));
            this.element.on('click', '.-fn-toggle-children', this._toggleFolder.bind(this));
            this.element.on('click', '.-fn-toggle-selected:not(.disabled)', this._toggleSelected.bind(this));
            this.element.on('click', '.layer-menu-btn', this._toggleMenu.bind(this));
            this.element.on('click', '.layer-menu .exit-button', function () {
                $(this).closest('.layer-menu').remove();
            });
            this.element.on('click', '.layer-remove-btn', function () {
                var $node = $(this).closest('li.leave');
                var layer = $node.data('layer');
                self.model.removeLayer(layer);
            });
            this.element.on('click', '.layer-metadata', function (evt) {
                self._showMetadata(evt);
            });
            $(document).bind('mbmapsourceloadstart', $.proxy(self._onSourceLoadStart, self));
            $(document).bind('mbmapsourceloadend', $.proxy(self._onSourceLoadEnd, self));
            $(document).bind('mbmapsourceloaderror', $.proxy(self._onSourceLoadError, self));
            $(document).bind('mbmapsourceadded', $.proxy(self._onSourceAdded, self));
            $(document).bind('mbmapsourcechanged', $.proxy(self._onSourceChanged, self));
            $(document).bind('mbmapsourceremoved', $.proxy(self._onSourceRemoved, self));
            $(document).bind('mbmapsourcelayerremoved', $.proxy(this._onSourceLayerRemoved, this));
            $(document).on('mb.sourcenodeselectionchanged', function (e, data) {
                if (data.node instanceof (Mapbender.Layerset)) {
                    self._updateThemeNode(data.node);
                }
            });
            if (this._mobilePane) {
                $(this.element).on('click', '.leaveContainer', function () {
                    $('.-fn-toggle-selected', this).click();
                });
            }
        },
        /**
         * Applies the new (going by DOM) layer order inside a source.
         *
         * @param $sourceContainer
         * @private
         */
        _updateSource: function ($sourceContainer) {
            // this will capture the "configurationish" layer ids (e.g. "1_0_4_1") from
            // all layers in the source container in DOM order
            var sourceId = $sourceContainer.attr('data-sourceid');
            var layerIdOrder = [];
            $('.-js-leafnode', $sourceContainer).each(function () {
                var $t = $(this);
                var layerId = $t.attr('data-id');
                if (typeof layerId !== "undefined") {
                    layerIdOrder.push("" + layerId);
                }
            });
            this.model.setSourceLayerOrder(sourceId, layerIdOrder.reverse());
        },
        /**
         * Applies the new (going by DOM) ordering between sources.
         *
         * @private
         */
        _updateSourceOrder: function () {
            var $roots = $('.serviceContainer[data-sourceid]', this.element);
            var sourceIds = $roots.map(function () {
                return $(this).attr('data-sourceid');
            }).get().reverse();
            this.model.reorderSources(sourceIds);
        },
        _createSortable: function () {
            var self = this;
            var onUpdate = function (event, ui) {
                if (ui.item.is('.themeContainer,.serviceContainer')) {
                    self._updateSourceOrder();
                } else {
                    self._updateSource(ui.item.closest('.serviceContainer'));
                }
            };

            $("ul.layers", this.element).each(function () {
                $(this).sortable({
                    axis: 'y',
                    items: "> li",
                    distance: 6,
                    cursor: "move",
                    update: onUpdate
                });
            });
        },
        _createThemeNode: function (layerset, options) {
            var $li = this.themeTemplate.clone();
            $li.attr('data-layersetid', layerset.id);
            $li.toggleClass('showLeaves', options.opened);
            $('span.layer-title:first', $li).text(layerset.getTitle() || '');
            this._updateFolderState($li);
            this._updateThemeNode(layerset, $li);
            return $li;
        },
        _updateThemeNode: function (layerset, $node) {
            var $node_ = $node || this._findThemeNode(layerset);
            var $themeControl = $('> .leaveContainer .-fn-toggle-selected', $node_);
            var newState = layerset.getSelected();
            this.updateIconVisual_($themeControl, newState, true);
        },
        _getThemeOptions: function (layerset) {
            var matches = (this.options.themes || []).filter(function (item) {
                return item.useTheme && ('' + item.id) === ('' + layerset.id);
            });
            return matches[0] || null;
        },
        _findThemeNode: function (layerset) {
            return $('ul.layers:first > li[data-layersetid="' + layerset.id + '"]', this.element);
        },
        _createLayerNode: function (layer) {
            var treeOptions = layer.options.treeOptions;
            if (!treeOptions.selected && !treeOptions.allow.selected) {
                return null;
            }

            var $li = this.template.clone();
            $li.data('layer', layer);

            $li.attr('data-id', layer.options.id);
            $li.attr('data-sourceid', layer.source.id);

            var $childList = $('ul.layers', $li);
            if (this.options.hideInfo || (layer.children && layer.children.length)) {
                $('.-fn-toggle-info', $li).remove();
            }
            if (!this._filterMenu(layer).length) {
                $('.layer-menu-btn', $li).remove();
            }
            if (!layer.getParent()) {
                $li.addClass("serviceContainer");
            }
            $li.toggleClass('-js-leafnode', !layer.children || !layer.children.length);
            $li.toggleClass('showLeaves', treeOptions.toggle);

            if (layer.children && layer.children.length && treeOptions.allow.toggle) {
                this._updateFolderState($li);
            } else {
                $('.-fn-toggle-children', $li).addClass('disabled-placeholder');
            }
            if (layer.children && layer.children.length && (treeOptions.allow.toggle || treeOptions.toggle)) {
                if (this.options.hideSelect && treeOptions.selected && !treeOptions.allow.selected) {
                    $('.-fn-toggle-selected', $li).remove();
                }
                for (var j = layer.children.length - 1; j >= 0; j--) {
                    $childList.append(this._createLayerNode(layer.children[j]));
                }
            } else {
                $childList.remove();
            }
            this._updateLayerDisplay($li, layer);
            $li.find('.layer-title:first')
                .attr('title', layer.options.title)
                .text(layer.options.title)
            ;

            return $li;
        },
        _createSourceTree: function (source) {
            var li = this._createLayerNode(source.configuration.children[0]);
            return li;
        },
        _onSourceAdded: function (event, data) {
            var source = data.source;
            if (source.configuration.baseSource && !this.options.showBaseSource) {
                return;
            }
            var $sourceTree = this._createSourceTree(source);
            var $rootList = $('ul.layers:first', this.element);
            // Insert on top
            $rootList.prepend($sourceTree);
            this.reIndent_($rootList, false);
            this._reset();
        },
        _onSourceChanged: function (event, data) {
            this._resetSourceAtTree(data.source);
        },
        _onSourceLayerRemoved: function (event, data) {
            var layer = data.layer;
            var layerId = layer.options.id;
            var sourceId = layer.source.id;
            var $node = $('[data-sourceid="' + sourceId + '"][data-id="' + layerId + '"]', this.element);
            $node.remove();
        },
        _redisplayLayerState: function ($li, layer) {
            var $title = $('>.leaveContainer .layer-title', $li);
            // NOTE: outOfScale is only calculated for leaves. May be null
            //       for intermediate nodes.
            $li.toggleClass('state-outofscale', !!layer.state.outOfScale);
            $li.toggleClass('state-outofbounds', !!layer.state.outOfBounds);
            $li.toggleClass('state-deselected', !layer.getSelected());
            var tooltipParts = [layer.options.title];
            if (layer.state.outOfScale) {
                tooltipParts.push(Mapbender.trans("mb.core.layertree.const.outofscale"));
            }
            if (layer.state.outOfBounds) {
                tooltipParts.push(Mapbender.trans("mb.core.layertree.const.outofbounds"));
            }
            $title.attr('title', tooltipParts.join("\n"));
        },
        _resetSourceAtTree: function (source) {
            var self = this;

            function resetLayer(layer) {
                var $li = $('li[data-id="' + layer.options.id + '"]', self.element);
                self._updateLayerDisplay($li, layer);
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        resetLayer(layer.children[i]);
                    }
                }
            }

            resetLayer(source.configuration.children[0]);
        },
        _updateLayerDisplay: function ($li, layer) {
            if (layer && layer.state && Object.keys(layer.state).length) {
                this._redisplayLayerState($li, layer);
            }
            if (layer && Object.keys((layer.options || {}).treeOptions).length) {
                var $checkboxScope = $('>.leaveContainer', $li);
                this._updateLayerCheckboxes($checkboxScope, layer.options.treeOptions);
            }
        },
        _updateLayerCheckboxes: function ($scope, treeOptions) {
            var allow = treeOptions.allow || {};
            var $layerControl = $('.-fn-toggle-selected', $scope);
            var $infoControl = $('.-fn-toggle-info', $scope);
            this.updateIconVisual_($layerControl, treeOptions.selected, allow.selected);
            this.updateIconVisual_($infoControl, treeOptions.info, allow.info);
        },
        _onSourceRemoved: function (event, removed) {
            if (removed && removed.source && removed.source.id) {
                var $source = this._getSourceNode(removed.source.id);
                var $theme = $source.closest('.themeContainer', this.element);
                $source.remove();
                if (!$('.serviceContainer', $theme).length) {
                    $theme.remove();
                }
            }
        },
        _getSourceNode: function (sourceId) {
            return $('.serviceContainer[data-sourceid="' + sourceId + '"]', this.element);
        },
        _onSourceLoadStart: function (event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.addClass('state-loading');
        },
        _onSourceLoadEnd: function (event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.removeClass('state-loading state-error');

            if ($sourceEl && $sourceEl.length) {
                this._resetSourceAtTree(options.source);
            }
        },
        _onSourceLoadError: function (event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.removeClass('state-loading').addClass('state-error');
        },
        _toggleFolder: function (e) {
            var $me = $(e.currentTarget);
            var layer = $me.closest('li.leave').data('layer');
            if (layer && (!layer.children || !layer.options.treeOptions.allow.toggle)) {
                return false;
            }
            var $node = $me.closest('.leave,.themeContainer');
            $node.toggleClass('showLeaves')

            this._updateFolderState($node);
            return false;
        },
        _updateFolderState: function($node) {
            const active = $node.hasClass('showLeaves');
            $node.children('.leaveContainer').children('.-fn-toggle-children').children('i')
                .toggleClass('fa-folder-open', active)
                .toggleClass('fa-folder', !active)
            ;
        },
        _toggleSelected: function (e) {
            var $target = $(e.currentTarget);
            var newState = $target.toggleClass('active').hasClass('active');
            this.updateIconVisual_($target, newState, null);
            var layer = $target.closest('li.leave').data('layer');
            var source = layer && layer.source;
            var themeId = !source && $target.closest('.themeContainer').attr('data-layersetid');
            if (themeId) {
                var theme = Mapbender.layersets.filter(function (x) {
                    return x.id === themeId;
                })[0];
                this.model.controlTheme(theme, newState);
            } else {
                if (layer.parent) {
                    this.model.controlLayer(layer, newState);
                } else {
                    this.model.setSourceVisibility(source, newState);
                }
            }

            return false;
        },
        _toggleInfo: function (e) {
            var $target = $(e.currentTarget);
            var newState = $target.toggleClass('active').hasClass('active');
            this.updateIconVisual_($target, newState, null);
            var layer = $target.closest('li.leave').data('layer');
            this.model.controlLayer(layer, null, newState);
        },
        _initMenu: function ($layerNode) {
            var layer = $layerNode.data('layer');
            var source = layer.source;
            var menu = $(this.menuTemplate.clone());
            var mapModel = this.model;
            if (layer.getParent()) {
                $('.layer-control-root-only', menu).remove();
            }
            var atLeastOne = !!$('.layer-remove-btn', menu).length;

            // element must be added to dom and sized before Dragdealer init...
            $('.leaveContainer:first', $layerNode).after(menu);

            var $opacityControl = $('.layer-control-opacity', menu);
            if ($opacityControl.length) {
                atLeastOne = true;
                var $handle = $('.layer-opacity-handle', $opacityControl);
                $handle.attr('unselectable', 'on');
                new Dragdealer($('.layer-opacity-bar', $opacityControl).get(0), {
                    x: source.configuration.options.opacity,
                    horizontal: true,
                    vertical: false,
                    speed: 1,
                    steps: 100,
                    handleClass: "layer-opacity-handle",
                    animationCallback: function (x, y) {
                        var opacity = Math.max(0.0, Math.min(1.0, x));
                        var percentage = Math.round(opacity * 100);
                        $handle.text(percentage);
                        mapModel.setSourceOpacity(source, opacity);
                    }
                });
            }
            var $zoomControl = $('.layer-zoom', menu);
            if ($zoomControl.length && layer.hasBounds()) {
                atLeastOne = true;
                $zoomControl.on('click', $.proxy(this._zoomToLayer, this));
            } else {
                $zoomControl.remove();
            }
            if (layer.options.metadataUrl && $('.layer-metadata', menu).length) {
                atLeastOne = true;
            } else {
                $('.layer-metadata', menu).remove();
            }

            var dims = source.configuration.options.dimensions || [];
            var $dimensionsControl = $('.layer-control-dimensions', menu);
            if (dims.length && $dimensionsControl.length) {
                this._initDimensionsMenu($layerNode, menu, dims, source);
                atLeastOne = true;
            } else {
                $dimensionsControl.remove();
            }
            if (!atLeastOne) {
                menu.remove();
            }
        },
        _toggleMenu: function (e) {
            var $target = $(e.target);
            var $layerNode = $target.closest('li.leave');
            if (!$('>.layer-menu', $layerNode).length) {
                $('.layer-menu', this.element).remove();
                this._initMenu($layerNode);
            }
            return false;
        },
        _filterMenu: function (layer) {
            var enabled = this.options.menu;
            var supported = ['layerremove'];
            if (layer.options.metadataUrl) {
                supported.push('metadata');
            }
            // opacity + dimension are only available on root layer
            if (!layer.getParent()) {
                supported.push('opacity');
                if ((layer.source.configuration.options.dimensions || []).length) {
                    supported.push('dimension');
                }
            }
            if (layer.hasBounds()) {
                supported.push('zoomtolayer');
            }

            return supported.filter(function (name) {
                return -1 !== enabled.indexOf(name);
            });
        },
        _initDimensionsMenu: function ($element, menu, dims, source) {
            var self = this;
            var dimData = $element.data('dimensions') || {};
            var template = $('.layer-control-dimensions', menu);
            var $controls = [];
            var dragHandlers = [];
            var updateData = function (key, props) {
                $.extend(dimData[key], props);
                var ourData = {};
                ourData[key] = dimData[key];
                var mergedData = $.extend($element.data('dimensions') || {}, ourData);
                $element.data('dimensions', mergedData);
            };
            $.each(dims, function (idx, item) {
                var $control = template.clone();
                var label = $('.layer-dimension-title', $control);

                var dimDataKey = source.id + '~' + idx;
                dimData[dimDataKey] = dimData[dimDataKey] || {
                    checked: false
                };
                var inpchkbox = $('input[type="checkbox"]', $control);
                inpchkbox.data('dimension', item);
                inpchkbox.prop('checked', dimData[dimDataKey].checked);
                inpchkbox.on('change', function (e) {
                    updateData(dimDataKey, {checked: $(this).prop('checked')});
                    self._callDimension(source, $(e.target));
                });
                label.attr('title', label.attr('title') + ' ' + item.name);
                $('.layer-dimension-bar', menu).toggleClass('hidden', item.type === 'single');
                $('.layer-dimension-textfield', $control)
                    .toggleClass('hidden', item.type !== 'single')
                    .val(dimData.value || item.extent)
                ;
                if (item.type === 'single') {
                    inpchkbox.attr('data-value', dimData.value || item.extent);
                    updateData(dimDataKey, {value: dimData.value || item.extent});
                } else if (item.type === 'multiple' || item.type === 'interval') {
                    var dimHandler = Mapbender.Dimension(item);
                    dragHandlers.push(new Dragdealer($('.layer-dimension-bar', $control).get(0), {
                        x: dimHandler.getStep(dimData[dimDataKey].value || dimHandler.getDefault()) / dimHandler.getStepsNum(),
                        horizontal: true,
                        vertical: false,
                        speed: 1,
                        steps: dimHandler.getStepsNum(),
                        handleClass: 'layer-dimension-handle',
                        callback: function (x, y) {
                            self._callDimension(source, inpchkbox);
                        },
                        animationCallback: function (x) {
                            var step = Math.round(dimHandler.getStepsNum() * x);
                            var value = dimHandler.valueFromStep(step);
                            label.text(value);
                            updateData(dimDataKey, {value: value});
                            inpchkbox.attr('data-value', value);
                        }
                    }));
                } else {
                    Mapbender.error("Source dimension " + item.type + " is not supported.");
                }
                $controls.push($control);
            });
            template.replaceWith($controls);
            dragHandlers.forEach(function (dh) {
                dh.reflow();
            });
        },
        _callDimension: function (source, chkbox) {
            var dimension = chkbox.data('dimension');
            var paramName = dimension['__name'];
            if (chkbox.is(':checked') && paramName) {
                var params = {};
                params[paramName] = chkbox.attr('data-value');
                source.addParams(params);
            } else if (paramName) {
                source.removeParams([paramName]);
            }
            return true;
        },
        _zoomToLayer: function (e) {
            var layer = $(e.target).closest('li.leave', this.element).data('layer');
            var options = {
                sourceId: layer.source.id,
                layerId: layer.options.id
            };
            this.model.zoomToLayer(options);
        },
        _showMetadata: function (e) {
            var layer = $(e.target).closest('li.leave', this.element).data('layer');
            var url = layer.options.metadataUrl;
            var useModal = !!this._mobilePane;
            $.ajax(url)
                .then(function (response) {
                    var metadataPopup = new Mapbender.Popup2({
                        title: Mapbender.trans("mb.core.metadata.popup.title"),
                        cssClass: 'metadataDialog',
                        modal: useModal,
                        resizable: !useModal,
                        draggable: !useModal,
                        content: $(response),
                        destroyOnClose: true,
                        width: !useModal && 850 || '100%',
                        height: !useModal && 600 || null,
                        buttons: [{
                            label: Mapbender.trans('mb.actions.close'),
                            cssClass: 'button popupClose critical'
                        }]
                    });
                    if (initTabContainer) {
                        initTabContainer(metadataPopup.$element);
                    }
                }, function (jqXHR, textStatus, errorThrown) {
                    Mapbender.error(errorThrown);
                })
            ;
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function (callback) {
            this.open(callback);
        },
        /**
         * Opens a popup dialog
         */
        open: function (callback) {
            this.callback = callback ? callback : null;
            if (this.useDialog_) {
                if (!this.popup || !this.popup.$element) {
                    var popupOptions = Object.assign(this.getPopupOptions(), {
                        content: [this.element.show()]
                    });
                    this.popup = new Mapbender.Popup2(popupOptions);
                    this.popup.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popup.$element.show();
                }
            }
            this._reset();
            this.notifyWidgetActivated();
        },
        getPopupOptions: function () {
            return {
                title: this.element.attr('data-title'),
                modal: false,
                resizable: true,
                draggable: true,
                closeOnESC: false,
                detachOnClose: false,
                width: 350,
                height: 500,
                cssClass: 'layertree-dialog customLayertree',
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.close'),
                        cssClass: 'button popupClose'
                    }
                ]
            };
        },
        /**
         * Closes the popup dialog
         */
        close: function () {
            if (this.useDialog_) {
                if (this.popup && this.popup.$element) {
                    this.popup.$element.hide();
                }
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            this.notifyWidgetDeactivated();
        },
        updateIconVisual_: function ($el, active, enabled) {
            $el.toggleClass('active', !!active);
            var icons;
            if ($el.is('.-fn-toggle-info')) {
                icons = ['fa-info', 'fa-info-circle'];
            } else {
                icons = ['fa-square', 'fa-check-square'];
            }
            $('>i', $el)
                .toggleClass(icons[1], !!active)
                .toggleClass(icons[0], !active)
            ;
            if (enabled !== null && (typeof enabled !== 'undefined')) {
                $el.toggleClass('disabled', !enabled);
            }
        },
        reIndent_: function ($lists, recursive) {
            for (var l = 0; l < $lists.length; ++l) {
                var list = $lists[l];
                var $folderToggles = $('>li >.leaveContainer .-fn-toggle-children', list);
                // If all folder toggles on this level of the tree are placeholders,
                // "unindent" the whole list.
                if ($folderToggles.filter('.disabled-placeholder').length === $folderToggles.length) {
                    $folderToggles.addClass('hidden');
                } else {
                    $folderToggles.removeClass('hidden');
                }
                if (recursive) {
                    this.reIndent_($('>li > .layers', list), recursive);
                }
            }
        },
        _destroy: $.noop
    });

})(jQuery);

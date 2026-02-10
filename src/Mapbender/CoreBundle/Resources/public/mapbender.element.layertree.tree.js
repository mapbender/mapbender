(function ($) {
    $.widget("mapbender.mbLayertree", $.mapbender.mbDialogElement, {
        options: {
            useTheme: false,
            target: null,
            showBaseSource: true,
            allowReorder: true,
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
        treeCreated: false,

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

            Mapbender.ElementUtil.adjustScrollbarsIfNecessary(this.element);
        },
        _createTree: function () {
            this.treeCreated = false;
            var sources = this.model.getSources();
            var $rootList = $('ul.layers:first', this.element);
            $rootList.empty();
            for (var i = (sources.length - 1); i > -1; i--) {
                if (this.options.showBaseSource || !sources[i].isBaseSource) {
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
            this.treeCreated = true;
            this._reset();
        },
        _reset: function () {
            if (this.options.allowReorder && !this._sortableInitialized) {
                this._createSortable();
                this._sortableInitialized = true;
            }
            if (this.options.showFilter) {
                this._filterLayer();
            }
        },
        _createEvents: function () {
            var self = this;
            this.element.on('click', '.-fn-toggle-info:not(.disabled)', this._toggleInfo.bind(this));
            this.element.on('click', '.-fn-toggle-children', this._toggleFolder.bind(this));
            this.element.on('click', '.-fn-toggle-selected:not(.disabled)', this._toggleSelected.bind(this));
            this.element.on('click', '.layer-title:not(.disabled)', this._toggleSelectedLayer.bind(this));
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
            this.element.on('input', '.layer-filter-input', this._filterLayer.bind(this));
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
            this._initLayerStyleEvents();
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
                    handle: '.leaveContainer',
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
            let title = layerset.getTitle() || '';
            if (options && options.title) title = Mapbender.trans(options.title);
            $('span.layer-title:first', $li).text(title);
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
            if (layer.getParent() && layer.children.length) {
                $li.addClass("subContainer");
            }
            const li = $li[0];
            const isLeafNode = !layer.children || !layer.children.length || !treeOptions.toggle;
            li.classList.toggle('-js-leafnode', isLeafNode);
            li.classList.toggle('showLeaves', treeOptions.toggle);

            if (layer.children && layer.children.length && treeOptions.allow.toggle) {
                this._updateFolderState($li);
            } else {
                $('.-fn-toggle-children', $li).addClass('disabled-placeholder');
            }
            if (layer.children && layer.children.length && (treeOptions.allow.toggle || treeOptions.toggle)) {
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
            return this._createLayerNode(source.getRootLayer());
        },
        _onSourceAdded: function (event, data) {
            var source = data.source;
            if (source.isBaseSource && !this.options.showBaseSource) {
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
            if ($li.length === 1) {
                const li = $li[0];
                li.classList.toggle('state-outofscale', !!layer.state.outOfScale);
                li.classList.toggle('state-outofbounds', !!layer.state.outOfBounds);
                li.classList.toggle('state-unsupportedprojection', !!layer.state.unsupportedProjection);
                li.classList.toggle('state-deselected', !layer.getSelected());
            }

            const tooltipParts = [layer.options.title];
            if (layer.state.outOfScale) {
                tooltipParts.push(Mapbender.trans("mb.core.layertree.const.outofscale"));
            }
            if (layer.state.outOfBounds) {
                tooltipParts.push(Mapbender.trans("mb.core.layertree.const.outofbounds"));
            }
            if (layer.state.unsupportedProjection) {
                tooltipParts.push(Mapbender.trans("mb.core.layertree.const.unsupportedprojection"));
            }
            $title.text(layer.options.title);
            $title.attr('title', tooltipParts.join("\n"));
        },
        _resetSourceAtTree: function (source) {
            this._resetLayer(source.getRootLayer(), null);
            // for performance reasons, only re-initialise sortable if tree has already been created
            if (this.treeCreated) this._reset();
        },
        _resetLayer: function (layer, $parent) {
            const $li = this.element.find('li[data-id="' + layer.options.id + '"]');
            const treeOptions = layer.options.treeOptions;
            const symbolState = layer.children && layer.children.length && (treeOptions.allow.toggle || treeOptions.toggle);
            const $toggleChildrenButton = $li.find('>.leaveContainer>.-fn-toggle-children');
            $toggleChildrenButton.toggleClass('disabled-placeholder', !symbolState);

            if (!$li.length && $parent) {
                let $layers = $parent.find('>ul.layers');
                if (!$layers.length) {
                    $layers = $(document.createElement('ul')).addClass('layers');
                    $parent.append($layers);
                    $toggleChildrenButton.toggleClass('fa-folder-open', treeOptions.toggle).toggleClass('fa-folder', !treeOptions.toggle);
                }

                $layers.append(this._createLayerNode(layer));
            }
            this._updateLayerDisplay($li, layer);
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    this._resetLayer(layer.children[i], $li);
                }
            }
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
        _updateFolderState: function ($node) {
            const active = $node.hasClass('showLeaves');
            $node.children('.leaveContainer').children('.-fn-toggle-children').children('i')
                .toggleClass('fa-folder-open', active)
                .toggleClass('fa-folder', !active)
            ;
        },
        _toggleSelectedLayer: function (e) {
            const clickEvent = $.Event("click");
            clickEvent.shiftKey = e.shiftKey;
            $(e.currentTarget.parentNode).find('span.-fn-toggle-selected').trigger(clickEvent);
        },
        _toggleSelected: function (e) {
            const $target = $(e.currentTarget);
            const newState = $target.toggleClass('active').hasClass('active');
            this.updateIconVisual_($target, newState, null);
            const $layer = $target.closest('li.leave');
            const layer = $layer.data('layer');
            const source = layer && layer.source;
            const themeId = !source && $target.closest('.themeContainer').attr('data-layersetid');
            if (themeId) {
                const theme = Mapbender.layersets.filter((x) => x.id === themeId)[0];
                this.model.controlTheme(theme, newState);
            } else {
                if (layer.parent) {
                    this.model.controlLayer(layer, newState);
                } else {
                    this.model.setSourceVisibility(source, newState);
                }
            }

            if (newState) {
                this._updateParentState($(e.currentTarget).closest('li.leave'));
            }

            if (e.shiftKey) {
                // $layer is only set for "regular" layers, for themes the css class is themeContainer
                this._updateChildrenState($layer.length ? $layer : $target.closest('li.themeContainer'), newState);
            }

            return false;
        },
        /**
         * ensure all parent layers become visible when toggling a child layer
         */
        _updateParentState: function ($target) {
            const $parentTarget = $target.parent().closest('li.leave, li.themeContainer');
            const $parentCheckbox = $parentTarget.find('> .leaveContainer > .-fn-toggle-selected');

            const layer = $parentTarget.data('layer');
            const source = layer && layer.source;
            const themeId = !source && $parentTarget.closest('.themeContainer').attr('data-layersetid');

            // already in root level, no parents left to check
            if (!(layer && layer.source) && !themeId) return;

            if ($parentCheckbox.hasClass('active')) {
                // recursively check the next higher hierarchy level
                this._updateParentState($parentTarget);
            } else {
                // only trigger checkbox click when it was not checked before
                // _toggleSelected will take care of checking for the higher hierarchy levels
                $parentCheckbox.trigger('click');
            }
        },
        _updateChildrenState: function ($layer, newState) {
            $layer.children('.layers').children('.leave').each((index, child) => {
                const $child = $(child);
                const $childToggle = $child.children('.leaveContainer').children('.-fn-toggle-selected');
                this.updateIconVisual_($childToggle, newState, null);
                this.model.controlLayer($child.data('layer'), newState);
            });
        },

        _toggleInfo: function (e) {
            var $target = $(e.currentTarget);
            var newState = $target.toggleClass('active').hasClass('active');
            this.updateIconVisual_($target, newState, null);
            var layer = $target.closest('li.leave').data('layer');
            this.model.controlLayer(layer, null, newState);
        },
        _resetLayerFilter: function () {
            this.element.find('.layer-highlight').each((index, span) => {
                $(span).replaceWith($(span).text());
            });
            this.element.find('.filtered').removeClass('filtered');
            this.element.find('.layer-title').parent().show();
        },
        _filterLayer: function () {
            const value = this.element.find('.layer-filter-input').val().toLowerCase();

            if (typeof this._lastFilterLength === 'undefined') {
                this._lastFilterLength = 0;
            }
            if (value.length < 2) {
                if (this._lastFilterLength > 1) this._resetLayerFilter(false);
                return;
            }

            // Mark all filter hits
            const $layerTitles = this.element.find('.layer-title');
            $layerTitles.each((index, element) => {
                const title = $(element).text()?.toString().toLowerCase();
                if (title) {
                    $(element).toggleClass('filtered', title.includes(value));
                }
            });

            // Hide all parent containers
            $layerTitles.parent().hide();

            this.element.find('.filtered').each((index, match) => {
                const $match = $(match);

                // Highlight the matching text in the layer title
                const regex = new RegExp('(' + value + ')', 'i');
                $match.html($match.text().replace(regex, '<span class="layer-highlight">$1</span>'));

                // Show parent containers and decide whether to open the folders
                $match.parent().show();
                ['subContainer', 'serviceContainer', 'themeContainer'].forEach(containerClass => {
                    const $container = $match.parents('.' + containerClass);
                    $container.find('.leaveContainer:first').show();

                    const isInContainer = $match.parent().parent().hasClass(containerClass);

                    if (isInContainer) {
                        $container.find('ul.layers .leaveContainer').show();
                    }
                    $container.toggleClass('showLeaves', !isInContainer);
                    $container.find('.-fn-toggle-children:first > i')
                        .toggleClass('fa-folder-open', !isInContainer)
                        .toggleClass('fa-folder', isInContainer);

                });
            });

            // Remove highlighted strings that are shorter than value
            this.element.find('.layer-highlight').each((index, span) => {
                if ($(span).text().length < value.length) {
                    $(span).replaceWith($(span).text());
                }
            });

            this._lastFilterLength = value.length;
        },

        /**
         * initalise a layer menu, called when the burger menu is clicked
         * @param $layerNode jQuery
         */
        _initMenu: function ($layerNode) {
            const layer = $layerNode.data('layer');
            const $menu = $(this.menuTemplate.clone());

            const activeMenuItems = this._filterMenu(layer);
            if (!activeMenuItems.length) {
                $menu.remove();
                return;
            }
            $layerNode.find('.leaveContainer:first', $layerNode).after($menu);

            $menu.find('[data-menu-action]').each((index, el) => {
                const $actionElement = $(el);
                const action = $actionElement.attr('data-menu-action');
                if (activeMenuItems.includes(action)) {
                    this._initMenuAction($menu, action, $actionElement, $layerNode, layer);
                } else {
                    $actionElement.remove();
                }
            });
        },
        /**
         *
         * @param {string} action
         * @param {jQuery} $menu the menu container dom element
         * @param {jQuery} $actionElement the dom element with the data-action attribute
         * @param {jQuery} $layerNode the dom element for the layer node
         * @param {Mapbender.SourceLayer} layer
         * @private
         */
        _initMenuAction($menu, action, $actionElement, $layerNode, layer) {
            switch (action) {
                case 'opacity':
                    return this._initOpacitySlider($actionElement, layer);
                case 'dimension':
                    return this._initDimensionsMenu($layerNode, $actionElement, layer.source);
                case 'select_style':
                    return this._initLayerStyleSelector($actionElement, layer);
                case 'zoomtolayer':
                    return $actionElement.on('click', this._zoomToLayer.bind(this));
            }
        },
        _initOpacitySlider: function ($opacityControl, layer) {
            const source = layer.source;
            if (!$opacityControl.length) return;

            const $wrapper = $opacityControl.find('.layer-opacity-bar');
            const $handle = $opacityControl.find('.layer-opacity-handle');
            const dragDealer = new Dragdealer($wrapper[0], {
                x: source.options.opacity,
                horizontal: true,
                vertical: false,
                speed: 1,
                steps: 100,
                handleClass: "layer-opacity-handle",
                animationCallback: (x, y) => {
                    var opacity = Math.max(0.0, Math.min(1.0, x));
                    var percentage = Math.round(opacity * 100);
                    $handle.text(percentage);
                    this.model.setSourceOpacity(source, opacity);
                }
            });

            const $slider = $opacityControl.find('.layer-slider-handle');
            $slider.attr('tabindex', '0'); // Make the handle focusable

            // Add keyboard event listener for left and right arrow keys
            $slider.on('keydown', (event) => {
                if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                    event.preventDefault();
                    const currentX = dragDealer.getValue()[0];
                    const step = 1 / 100; // Step size for opacity adjustment
                    let newX = currentX;

                    if (event.key === 'ArrowLeft') {
                        newX = Math.max(0, currentX - step);
                    } else if (event.key === 'ArrowRight') {
                        newX = Math.min(1, currentX + step);
                    }

                    dragDealer.setValue(newX, 0);
                    const opacity = Math.max(0.0, Math.min(1.0, newX));
                    const percentage = Math.round(opacity * 100);
                    $handle.text(percentage);
                    this.model.setSourceOpacity(source, opacity);
                }
            });
        },
        _initLayerStyleEvents: function () {
            const self = this;
            this.element.on('change', '.select-layer-styles', function () {
                let layer = $(this).data('layer');
                layer.options.style = $(this).val();
                self.model.updateSource(layer.source);
            });
        },
        _initLayerStyleSelector: function ($layerStyleControl, layer) {
            const availableStyles = layer.options.availableStyles || [];
            const $selectLayerStyles = $layerStyleControl.find('.select-layer-styles');
            $selectLayerStyles.data('layer', layer);
            if (availableStyles.length && $selectLayerStyles.length) {
                for (let i = 0; i < availableStyles.length; i++) {
                    const selected = availableStyles[i].name === layer.options.style;
                    $selectLayerStyles.append(new Option(availableStyles[i].title, availableStyles[i].name, false, selected));
                }
            } else {
                $layerStyleControl.remove();
            }
        },
        _toggleMenu: function (e) {
            var $target = $(e.target);
            var $layerNode = $target.closest('li.leave');
            if (!$('>.layer-menu', $layerNode).length) {
                $('.layer-menu', this.element).remove();
                this._initMenu($layerNode);

                const $menu = $layerNode.find('>.layer-menu');

                $menu.find('.exit-button:visible, .layer-opacity-handle:visible, .clickable:visible').attr('tabindex', '0');
                const $firstFocusable = $menu.find('[tabindex="0"]').first();
                if ($firstFocusable.length) {
                    $firstFocusable.focus();
                }
            }

            return false;
        },
        _filterMenu: function (layer) {
            const enabled = this.options.menu;
            const supported = this._getSupportedMenuOptions(layer);

            return supported.filter(function (name) {
                return -1 !== enabled.indexOf(name);
            });
        },
        /**
         * returns a list of supported menu options for this layer. Override this if you have a custom menu option
         * @param {Mapbender.SourceLayer} layer
         * @returns {string[]}
         */
        _getSupportedMenuOptions(layer) {
            return layer.getSupportedMenuOptions();
        },
        _initDimensionsMenu: function ($element, $actionElement, source) {
            var dims = source.options.dimensions || [];
            var self = this;
            var dimData = $element.data('dimensions') || {};
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
                var $control = $actionElement.clone();
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
                $('.layer-dimension-bar', $actionElement).toggleClass('hidden', item.type === 'single');
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
            $actionElement.replaceWith($controls);
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
                    var metadataPopup = new Mapbender.Popup({
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
                            cssClass: 'btn btn-sm btn-light popupClose critical'
                        }]
                    });
                    metadataPopup.$element.find('button').focus();
                    if (initTabContainer) {
                        initTabContainer(metadataPopup.$element);
                    }
                })
                .fail((errorEvent) => Mapbender.handleAjaxError(errorEvent, () => this._showMetadata(e)))
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
                    this.popup = new Mapbender.Popup(popupOptions);
                    this.popup.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popup.$element.show();
                    this.popup.focus();
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
                        cssClass: 'btn btn-sm btn-light popupClose'
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
            var hideLayerNameWhenCheckboxDisabled = false;
            if ($el.is('.-fn-toggle-info')) {
                icons = ['fa-info', 'fa-info-circle'];
            } else {
                icons = ['fa-square', 'fa-square-check'];
                hideLayerNameWhenCheckboxDisabled = !enabled && active;
            }
            $('>i', $el)
                .toggleClass(icons[1], !!active)
                .toggleClass(icons[0], !active)
            ;
            if (enabled !== null && (typeof enabled !== 'undefined')) {
                $el.toggleClass('disabled', !enabled);
                if (hideLayerNameWhenCheckboxDisabled) {
                    $('>span', $el.prevObject).closest('.layer-title').toggleClass('disabled', true);
                }
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

// Ensure all mouse click events can also be triggered by pressing the Enter key
$(document).on('keydown', function (event) {
    if (event.key === 'Enter') {
        var target = $(event.target);
        if (target.is(':focus') && target.is(':visible') && target.attr('tabindex') !== undefined) {
            target.trigger("click");
        }
    }
});

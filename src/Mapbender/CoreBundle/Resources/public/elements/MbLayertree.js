(function () {
    class MbLayertree extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.model = null;
            this.template = null;
            this.menuTemplate = null;
            this.popup = null;
            this.treeCreated = false;
            this.cssClasses = {
                menuClose: 'fa-xmark',
                menuOpen: 'fa-ellipsis-vertical',
                checkboxUnchecked: 'far fa-square',
                checkboxChecked: 'fas fa-square-check',
                infoInactive: 'fa-info',
                infoActive: 'fa-info-circle',
                folderExpanded: 'fa-caret-down',
                folderCollapsed: 'fa-caret-right'
            };

            this.useDialog_ = this.checkDialogMode();
            this._mobilePane = this.$element.closest('#mobilePane').get(0) || null;

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, () => {
                Mapbender.checkTarget('mbLayertree');
            });
        }

        _setup(mbMap) {
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.$element.attr('id') + '/';
            this.template = $('li.-fn-template', this.$element).remove();
            this.template.removeClass('hidden -fn-template');
            this.menuTemplate = $('.layer-menu', this.template).remove();
            this.menuTemplate.removeClass('hidden');
            this.themeTemplate = $('li.-fn-theme-template', this.$element).remove();
            this.themeTemplate.removeClass('hidden -fn-theme-template');

            this.model = mbMap.getModel();
            this._createTree();
            if (this.checkAutoOpen()) {
                this.activateByButton();
            }
            this._createEvents();
            Mapbender.elementRegistry.markReady(this);
        }

        _createTree() {
            this.treeCreated = false;
            var sources = this.model.getSources();
            var $rootList = $('ul.layers:first', this.$element);
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
        }

        _reset() {
            if (this.options.allowReorder && !this._sortableInitialized) {
                this._createSortable();
                this._sortableInitialized = true;
            }
            if (this.options.showFilter) {
                this._filterLayer();
            }
        }

        _createEvents() {
            var self = this;
            this.$element.on('click', '.-fn-toggle-info:not(.disabled)', this._toggleInfo.bind(this));
            this.$element.on('click', '.-fn-toggle-children', this._toggleFolder.bind(this));
            this.$element.on('click', '.-fn-toggle-selected:not(.disabled)', this._toggleSelected.bind(this));
            this.$element.on('click', '.layer-title:not(.disabled)', this._toggleSelectedLayer.bind(this));
            this.$element.on('click', '.layer-menu-btn', this._toggleMenu.bind(this));
            this.$element.on('click', '.layer-remove-btn', function () {
                var $node = $(this).closest('li.leave');
                var layer = $node.data('layer');
                self.model.removeLayer(layer);
            });
            this.$element.on('click', '.layer-metadata', function (evt) {
                self._showMetadata(evt);
            });
            this.$element.on('input', '.layer-filter-input', this._filterLayer.bind(this));
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
                $(this.$element).on('click', '.leaveContainer', function () {
                    $('.-fn-toggle-selected', this).click();
                });
            }
            this._initLayerStyleEvents();
        }

        _updateSource($sourceContainer) {
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
        }

        _updateSourceOrder() {
            var $roots = $('.serviceContainer[data-sourceid]', this.$element);
            var sourceIds = $roots.map(function () {
                return $(this).attr('data-sourceid');
            }).get().reverse();
            this.model.reorderSources(sourceIds);
        }

        _createSortable() {
            var self = this;
            var onUpdate = function (event, ui) {
                if (ui.item.is('.themeContainer,.serviceContainer')) {
                    self._updateSourceOrder();
                } else {
                    self._updateSource(ui.item.closest('.serviceContainer'));
                }
            };

            $("ul.layers", this.$element).each(function () {
                $(this).sortable({
                    axis: 'y',
                    handle: '.leaveContainer',
                    items: "> li",
                    distance: 6,
                    cursor: "move",
                    update: onUpdate
                });
            });
        }

        _createThemeNode(layerset, options) {
            var $li = this.themeTemplate.clone();
            $li.attr('data-layersetid', layerset.id);
            $li.toggleClass('showLeaves', options.opened);
            let title = layerset.getTitle() || '';
            if (options && options.title) title = Mapbender.trans(options.title);
            $('span.layer-title:first', $li).text(title);
            this._updateFolderState($li);
            this._updateThemeNode(layerset, $li);
            return $li;
        }

        _updateThemeNode(layerset, $node) {
            var $node_ = $node || this._findThemeNode(layerset);
            var $themeControl = $('> .leaveContainer .-fn-toggle-selected', $node_);
            var newState = layerset.getSelected();
            this.updateIconVisual_($themeControl, newState, true);
        }

        _getThemeOptions(layerset) {
            var matches = (this.options.themes || []).filter(function (item) {
                return item.useTheme && ('' + item.id) === ('' + layerset.id);
            });
            return matches[0] || null;
        }

        _findThemeNode(layerset) {
            return $('ul.layers:first > li[data-layersetid="' + layerset.id + '"]', this.$element);
        }

        _createLayerNode(layer) {
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
        }

        _createSourceTree(source) {
            return this._createLayerNode(source.getRootLayer());
        }

        _onSourceAdded(event, data) {
            var source = data.source;
            if (source.isBaseSource && !this.options.showBaseSource) {
                return;
            }
            var $sourceTree = this._createSourceTree(source);
            var $rootList = $('ul.layers:first', this.$element);
            $rootList.prepend($sourceTree);
            this.reIndent_($rootList, false);
            this._reset();
        }

        _onSourceChanged(event, data) {
            this._resetSourceAtTree(data.source);
        }

        _onSourceLayerRemoved(event, data) {
            var layer = data.layer;
            var layerId = layer.options.id;
            var sourceId = layer.source.id;
            var $node = $('[data-sourceid="' + sourceId + '"][data-id="' + layerId + '"]', this.$element);
            $node.remove();
        }

        _redisplayLayerState($li, layer) {
            var $title = $('>.leaveContainer .layer-title', $li);
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
        }

        _resetSourceAtTree(source) {
            this._resetLayer(source.getRootLayer(), null);
            if (this.treeCreated) this._reset();
        }

        _resetLayer(layer, $parent) {
            const $li = this.$element.find('li[data-id="' + layer.options.id + '"]');
            const treeOptions = layer.options.treeOptions;
            const symbolState = layer.children && layer.children.length && (treeOptions.allow.toggle || treeOptions.toggle);
            const $toggleChildrenButton = $li.find('>.leaveContainer>.-fn-toggle-children');
            $toggleChildrenButton.toggleClass('disabled-placeholder', !symbolState);

            if (!$li.length && $parent) {
                let $layers = $parent.find('>ul.layers');
                if (!$layers.length) {
                    $layers = $(document.createElement('ul')).addClass('layers');
                    $parent.append($layers);
                    $toggleChildrenButton
                        .toggleClass(this.cssClasses.folderExpanded, treeOptions.toggle)
                        .toggleClass(this.cssClasses.folderCollapsed, !treeOptions.toggle);
                }

                $layers.append(this._createLayerNode(layer));
            }
            this._updateLayerDisplay($li, layer);
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    this._resetLayer(layer.children[i], $li);
                }
            }
        }

        _updateLayerDisplay($li, layer) {
            if (layer && layer.state && Object.keys(layer.state).length) {
                this._redisplayLayerState($li, layer);
            }
            if (layer && Object.keys((layer.options || {}).treeOptions).length) {
                var $checkboxScope = $('>.leaveContainer', $li);
                this._updateLayerCheckboxes($checkboxScope, layer.options.treeOptions);
            }
        }

        _updateLayerCheckboxes($scope, treeOptions) {
            var allow = treeOptions.allow || {};
            var $layerControl = $('.-fn-toggle-selected', $scope);
            var $infoControl = $('.-fn-toggle-info', $scope);
            this.updateIconVisual_($layerControl, treeOptions.selected, allow.selected);
            this.updateIconVisual_($infoControl, treeOptions.info, allow.info);
        }

        _onSourceRemoved(event, removed) {
            if (removed && removed.source && removed.source.id) {
                var $source = this._getSourceNode(removed.source.id);
                var $theme = $source.closest('.themeContainer', this.$element);
                $source.remove();
                if (!$('.serviceContainer', $theme).length) {
                    $theme.remove();
                }
            }
        }

        _getSourceNode(sourceId) {
            return $('.serviceContainer[data-sourceid="' + sourceId + '"]', this.$element);
        }

        _onSourceLoadStart(event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.addClass('state-loading');
        }

        _onSourceLoadEnd(event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.removeClass('state-loading state-error');

            if ($sourceEl && $sourceEl.length) {
                this._resetSourceAtTree(options.source);
            }
        }

        _onSourceLoadError(event, options) {
            var sourceId = options.source && options.source.id;
            var $sourceEl = sourceId && this._getSourceNode(sourceId);
            $sourceEl.removeClass('state-loading').addClass('state-error');
        }

        _toggleFolder(e) {
            var $me = $(e.currentTarget);
            var layer = $me.closest('li.leave').data('layer');
            if (layer && (!layer.children || !layer.options.treeOptions.allow.toggle)) {
                return false;
            }
            var $node = $me.closest('.leave,.themeContainer');
            $node.toggleClass('showLeaves');

            // Close all layer-menus of its children as well
            if (!$node.hasClass('showLeaves')) {
                this._closeChildrenMenus($node);
            }

            // Update menuBackground positions after folder toggle
            const self = this;
            setTimeout(() => {
                $('.menuBackground', this.$element).each(function() {
                    const $menuBackground = $(this);
                    self._updateMenuBackgroundPosition($menuBackground);
                });
            }, 10);

            this._updateFolderState($node);
            return false;
        }

        _closeChildrenMenus($parentNode) {
            const $childrenLayers = $parentNode.find('ul.layers li.leave');

            $childrenLayers.each((index, childNode) => {
                const $childNode = $(childNode);
                const $childMenu = $childNode.find('>.layer-menu');

                if ($childMenu.length) {
                    $childMenu.remove();
                    const $menuBtn = $childNode.find('>.leaveContainer .layer-menu-btn i');
                    $menuBtn.removeClass(this.cssClasses.menuClose).addClass(this.cssClasses.menuOpen);
                    $menuBtn.prevObject.removeClass('menuBackground');
                }
            });
        }
        _updateFolderState($node) {
            const active = $node.hasClass('showLeaves');
            $node.children('.leaveContainer').children('.-fn-toggle-children').children('i')
                .toggleClass(this.cssClasses.folderExpanded, active)
                .toggleClass(this.cssClasses.folderCollapsed, !active)
            ;
        }

        _toggleSelectedLayer(e) {
            const clickEvent = $.Event("click");
            clickEvent.shiftKey = e.shiftKey;
            $(e.currentTarget.parentNode).find('span.-fn-toggle-selected').trigger(clickEvent);
        }

        _toggleSelected(e) {
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
                this._updateChildrenState($layer.length ? $layer : $target.closest('li.themeContainer'), newState);
            }

            return false;
        }

        _updateParentState($target) {
            const $parentTarget = $target.parent().closest('li.leave, li.themeContainer');
            const $parentCheckbox = $parentTarget.find('> .leaveContainer > .-fn-toggle-selected');

            const layer = $parentTarget.data('layer');
            const source = layer && layer.source;
            const themeId = !source && $parentTarget.closest('.themeContainer').attr('data-layersetid');

            if (!(layer && layer.source) && !themeId) return;

            if ($parentCheckbox.hasClass('active')) {
                this._updateParentState($parentTarget);
            } else {
                $parentCheckbox.trigger('click');
            }
        }

        _updateChildrenState($layer, newState) {
            $layer.children('.layers').children('.leave').each((index, child) => {
                const $child = $(child);
                const $childToggle = $child.children('.leaveContainer').children('.-fn-toggle-selected');
                this.updateIconVisual_($childToggle, newState, null);
                this.model.controlLayer($child.data('layer'), newState);
            });
        }

        _toggleInfo(e) {
            var $target = $(e.currentTarget);
            var newState = $target.toggleClass('active').hasClass('active');
            this.updateIconVisual_($target, newState, null);
            var layer = $target.closest('li.leave').data('layer');
            this.model.controlLayer(layer, null, newState);
        }

        _resetLayerFilter() {
            this.$element.find('.layer-highlight').each((index, span) => {
                $(span).replaceWith($(span).text());
            });
            this.$element.find('.filtered').removeClass('filtered');
            this.$element.find('.layer-title').parent().show();
        }

        _filterLayer() {
            const value = this.$element.find('.layer-filter-input').val().toLowerCase();

            if (typeof this._lastFilterLength === 'undefined') {
                this._lastFilterLength = 0;
            }
            if (value.length < 2) {
                if (this._lastFilterLength > 1) this._resetLayerFilter(false);
                return;
            }

            const $layerTitles = this.$element.find('.layer-title');
            $layerTitles.each((index, element) => {
                const title = $(element).text().toLowerCase();
                if (title) {
                    $(element).toggleClass('filtered', title.includes(value));
                }
            });

            $layerTitles.parent().hide();

            this.$element.find('.filtered').each((index, match) => {
                const $match = $(match);
                const regex = new RegExp('(' + value + ')', 'i');
                $match.html($match.text().replace(regex, '<span class="layer-highlight">$1</span>'));

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
                        .toggleClass(this.cssClasses.folderExpanded, !isInContainer)
                        .toggleClass(this.cssClasses.folderCollapsed, isInContainer);

                });
            });

            this.$element.find('.layer-highlight').each((index, span) => {
                if ($(span).text().length < value.length) {
                    $(span).replaceWith($(span).text());
                }
            });

            this._lastFilterLength = value.length;
        }

        _initMenu($layerNode) {
            const layer = $layerNode.data('layer');
            const $menu = $(this.menuTemplate.clone());

            const activeMenuItems = this._filterMenu(layer);
            if (!activeMenuItems.length) {
                $menu.remove();
                return;
            }

            const $leaveContainer = $layerNode.find('.leaveContainer:first');
            $leaveContainer.after($menu);

            $menu.find('[data-menu-action]').each((index, el) => {
                const $actionElement = $(el);
                const action = $actionElement.attr('data-menu-action');
                if (activeMenuItems.includes(action)) {
                    this._initMenuAction($menu, action, $actionElement, $layerNode, layer);
                } else {
                    $actionElement.remove();
                }
            });
        }

        /**
         *
         * @param {jQuery} $menu the menu container dom element
         * @param {string} action
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
        }
        /**
         * Creates a reusable resize handler for sliders (opacity/dimension)
         * @param {Object} options Configuration object
         * @param {string} options.type - Type of slider ('opacity' or 'dimension')
         * @param {string} options.id - Unique identifier for the slider
         * @param {jQuery} options.$container - Container element to observe for resize
         * @param {Function} options.handleResize - Custom resize handling function
         * @private
         */
        _setupSliderResizeHandler(options) {
            const eventNamespace = 'resize.' + options.type + 'Slider' + options.id;

            const handleSidePaneResize = () => {
                setTimeout(() => {
                    options.handleResize();
                }, 10);
            };

            let resizeObserver = null;
            const $sidePane = options.$container.closest('.sidePane');

            // Case: Layertree is displayed in sidepane
            if ($sidePane.length && window.ResizeObserver) {
                resizeObserver = new ResizeObserver(handleSidePaneResize);
                resizeObserver.observe($sidePane[0]);

                options.$container.data('resizeObserver', resizeObserver);
            } else {
                // Case: Layertree is displayed in a popup
                $(window).on(eventNamespace, handleSidePaneResize);
            }
        }

        /**
         * Setup keyboard navigation for slider elements
         * @param {Object} options - Configuration object
         * @param {jQuery} options.$slider - Slider element to add keyboard support to
         * @param {Object} options.dragHandler - Dragdealer instance
         * @param {number} options.stepSize - Size of each increment/decrement step
         * @param {Function} [options.onValueChange] - Optional callback when value changes
         * @private
         */
        _setupSliderKeyboardNavigation(options) {
            const { $slider, dragHandler, stepSize, onValueChange } = options;

            $slider.on('keydown', (event) => {
                if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                    event.preventDefault();
                    const currentX = dragHandler.getValue()[0];
                    let newX = currentX;

                    if (event.key === 'ArrowLeft') {
                        newX = Math.max(0, currentX - stepSize);
                    } else if (event.key === 'ArrowRight') {
                        newX = Math.min(1, currentX + stepSize);
                    }

                    dragHandler.setValue(newX, 0);

                    // Call optional callback for additional value handling
                    if (onValueChange) {
                        onValueChange(newX);
                    }
                }
            });
        }

        _initOpacitySlider($opacityControl, layer) {
            const source = layer.source;
            if (!$opacityControl.length) return;

            const $wrapper = $opacityControl.find('.layer-opacity-bar');
            const $handle = $opacityControl.find('.layer-opacity-handle');
            const $valueDisplay = $opacityControl.find('.layer-opacity-value');
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
                    $valueDisplay.text(percentage);
                    this.model.setSourceOpacity(source, opacity);
                }
            });

            // Positioning the slider-handle when resizing sidepane or popup:
            $opacityControl.data('dragDealer', dragDealer);

            // Get layer ID for unique event namespace
            const layerId = layer.options.id;

            // Setup resize handling using the common function
            this._setupSliderResizeHandler({
                type: 'opacity',
                id: layerId,
                $container: $opacityControl,
                handleResize: () => {
                    const currentOpacity = source.options.opacity;

                    if (dragDealer) {
                        dragDealer.reflow();
                        dragDealer.setValue(currentOpacity, 0);
                    }
                }
            });

            // Add keyboard event listener for left and right arrow keys
            const $slider = $opacityControl.find('.layer-opacity-handle');
            this._setupSliderKeyboardNavigation({
                $slider: $slider,
                dragHandler: dragDealer,
                stepSize: 1 / 100, // Step size for opacity adjustment
                onValueChange: (newX) => {
                    const opacity = Math.max(0.0, Math.min(1.0, newX));
                    const percentage = Math.round(opacity * 100);
                    $handle.text(percentage);
                    $valueDisplay.text(percentage);
                    this.model.setSourceOpacity(source, opacity);
                }
            });
        }

        _initLayerStyleEvents() {
            const self = this;
            this.$element.on('change', '.select-layer-styles', function () {
                let layer = $(this).data('layer');
                layer.options.style = $(this).val();
                self.model.updateSource(layer.source);
            });
        }

        _initLayerStyleSelector($layerStyleControl, layer) {
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
        }

        _toggleMenu(e) {
            var $target = $(e.target);
            var $layerNode = $target.closest('li.leave');
            if (!$('>.layer-menu', $layerNode).length) {
                // Close all other menus first
                $('.layer-menu', this.$element).remove();
                // Reset all menu button icons back to bars
                $('.layer-menu-btn i', this.$element).removeClass(this.cssClasses.menuClose).addClass(this.cssClasses.menuOpen);
                $('.layer-menu-btn', this.$element).offsetParent().removeClass('menuBackground');
                this._initMenu($layerNode);

                const $menu = $layerNode.find('>.layer-menu');
                const $menuBtn = $layerNode.find('>.leaveContainer .layer-menu-btn i');
                $menuBtn.removeClass(this.cssClasses.menuOpen).addClass(this.cssClasses.menuClose);
                $menuBtn.prevObject.addClass('menuBackground');
                $menu.find('.exit-button:visible, .layer-opacity-handle:visible, .clickable:visible').attr('tabindex', '0');

                // Only focus the first element if the menu was opened via keyboard (Enter key)
                // Check if the event was triggered by Enter key (originalEvent will have key === 'Enter')
                const isKeyboardTriggered = e.originalEvent && e.originalEvent.key === 'Enter';
                if (isKeyboardTriggered) {
                    const $firstFocusable = $menu.find('[tabindex="0"]').first();
                    if ($firstFocusable.length) {
                        $firstFocusable.focus();
                    }
                }

                // calculate bottom of class menuBackground
                this._updateMenuBackgroundPosition($menuBtn.prevObject);
            } else {
                $('>.layer-menu', $layerNode).remove();
                const $menuBtn = $layerNode.find('>.leaveContainer .layer-menu-btn i');
                $menuBtn.removeClass(this.cssClasses.menuClose).addClass(this.cssClasses.menuOpen);
                $menuBtn.prevObject.removeClass('menuBackground');
            }

            return false;
        }

        _updateMenuBackgroundPosition ($container) {
            const $menu = $container.find('>.layer-menu');
            const $menuBackground = $container.filter('.menuBackground');
            if ($menu.length && $menuBackground.length) {
                setTimeout(() => {
                    const menuRect = $menu[0].getBoundingClientRect();
                    const menuBackgroundRect = $menuBackground[0].getBoundingClientRect();
                    const distanceFromBottom = menuBackgroundRect.bottom - menuRect.bottom - 8;
                    $menuBackground[0].style.setProperty('--menu-background-bottom', `${distanceFromBottom}px`);
                }, 10);
            }
        }

        _filterMenu(layer) {
            const enabled = this.options.menu;
            const supported = this._getSupportedMenuOptions(layer);

            return supported.filter(function (name) {
                return -1 !== enabled.indexOf(name);
            });
        }

        /**
         * returns a list of supported menu options for this layer. Override this if you have a custom menu option
         * @param {Mapbender.SourceLayer} layer
         * @returns {string[]}
         */
        _getSupportedMenuOptions(layer) {
            return layer.getSupportedMenuOptions();
        }

        _initDimensionsMenu($element, $actionElement, source) {
            var dims = source.options.dimensions || [];
            var self = this;
            var dimData = $element.data('dimensions') || {};
            var $controls = [];
            var dragHandlers = [];
            var dimensionHandlers = [];
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
                var inpchkbox = $('.-fn-toggle-dimension', $control);
                inpchkbox.data('dimension', item);
                inpchkbox.data('checked', dimData[dimDataKey].checked);
                // Update visual state
                self.updateIconVisual_(inpchkbox, dimData[dimDataKey].checked, true);
                inpchkbox.on('click', function (e) {
                    var $this = $(this);
                    var newState = !$this.data('checked');
                    $this.data('checked', newState);
                    self.updateIconVisual_($this, newState, true);
                    updateData(dimDataKey, {checked: newState});
                    self._callDimension(source, $this);
                    return false;
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
                    dimensionHandlers.push(dimHandler);
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
                    dimensionHandlers.push(null);
                    Mapbender.error("Source dimension " + item.type + " is not supported.");
                }
                $controls.push($control);
            });
            $actionElement.replaceWith($controls);
            dragHandlers.forEach(function (dh) {
                dh.reflow();
            });

            // Resize handling for dimension sliders
            if ($controls.length > 0) {
                const $dimensionControls = $($controls);
                const sourceId = source.id;

                // Setup resize handling using the common function
                this._setupSliderResizeHandler({
                    type: 'dimension',
                    id: sourceId,
                    $container: $(self.element),
                    handleResize: () => {
                        dragHandlers.forEach(function (dh, index) {
                            if (dh && dimensionHandlers[index]) {
                                dh.reflow();

                                // Reposition the slider based on current value
                                const $control = $($dimensionControls[index]);
                                const $checkbox = $control.find('.-fn-toggle-dimension');
                                const currentValue = $checkbox.attr('data-value');

                                if (currentValue) {
                                    const dimHandler = dimensionHandlers[index];
                                    const currentStep = dimHandler.getStep(currentValue);
                                    const currentX = currentStep / dimHandler.getStepsNum();
                                    dh.setValue(currentX, 0);
                                }
                            }
                        });
                    }
                });

                // Keyboard support for dimension sliders
                $dimensionControls.each((index, control) => {
                    const $control = $(control);
                    const $slider = $control.find('.layer-dimension-handle');
                    const dragHandler = dragHandlers[index];

                    if ($slider.length && dragHandler) {
                        this._setupSliderKeyboardNavigation({
                            $slider: $slider,
                            dragHandler: dragHandler,
                            stepSize: 1 / dragHandler.options.steps
                        });
                    }
                });
            }
        }

        _callDimension (source, chkbox) {
            // Check if checkbox still exists in DOM and has dimension data
            if (!chkbox || !chkbox.length || !chkbox.closest('body').length) {
                return false;
            }

            var dimension = chkbox.data('dimension');
            if (!dimension) {
                return false;
            }
            var paramName = dimension['__name'];
            var isChecked = chkbox.data('checked');
            if (isChecked && paramName) {
                var params = {};
                params[paramName] = chkbox.attr('data-value');
                source.addParams(params);
            } else if (paramName) {
                source.removeParams([paramName]);
            }
            return true;
        }

        _zoomToLayer(e) {
            var layer = $(e.target).closest('li.leave', this.$element).data('layer');
            var options = {
                sourceId: layer.source.id,
                layerId: layer.options.id
            };
            this.model.zoomToLayer(options);
        }

        _showMetadata(e) {
            var layer = $(e.target).closest('li.leave', this.$element).data('layer');
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
                        buttons: []
                    });
                    metadataPopup.$element.find('button').focus();
                    if (initTabContainer) {
                        initTabContainer(metadataPopup.$element);
                    }
                }, function (jqXHR, textStatus, errorThrown) {
                    Mapbender.error(errorThrown);
                })
            ;
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                modal: false,
                resizable: true,
                draggable: true,
                closeOnESC: false,
                detachOnClose: false,
                content: this.$element,
                width: 350,
                height: 500,
                cssClass: 'layertree-dialog customLayertree',
                buttons: []
            };
        }

        activateByButton(callback, mbButton) {
            if (this.useDialog_) {
                super.activateByButton(callback, mbButton);
            }
            this._reset();
            this.notifyWidgetActivated();
        }

        closeByButton() {
            if (this.useDialog_) {
                super.closeByButton();
            }
            this.notifyWidgetDeactivated();
        }

        updateIconVisual_($el, active, enabled) {
            $el.toggleClass('active', !!active);
            var icons;
            var hideLayerNameWhenCheckboxDisabled = false;
            if ($el.is('.-fn-toggle-info')) {
                icons = [this.cssClasses.infoInactive, this.cssClasses.infoActive];
            } else {
                icons = [this.cssClasses.checkboxUnchecked, this.cssClasses.checkboxChecked];
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
        }

        reIndent_($lists, recursive) {
            for (var l = 0; l < $lists.length; ++l) {
                var list = $lists[l];
                var $folderToggles = $('>li >.leaveContainer .-fn-toggle-children', list);
                if ($folderToggles.filter('.disabled-placeholder').length === $folderToggles.length) {
                    $folderToggles.addClass('hidden');
                } else {
                    $folderToggles.removeClass('hidden');
                }
                if (recursive) {
                    this.reIndent_($('>li > .layers', list), recursive);
                }
            }
        }

    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbLayertree = MbLayertree;
})();

// Ensure all mouse click events can also be triggered by pressing the Enter key
$(document).on('keydown', function (event) {
    if (event.key === 'Enter') {
        var target = $(event.target);
        if (target.is(':focus') && target.is(':visible') && target.attr('tabindex') !== undefined) {
            // Create a click event with the original keyboard event as originalEvent
            var clickEvent = $.Event('click');
            clickEvent.originalEvent = event;
            target.trigger(clickEvent);
        }
    }
});

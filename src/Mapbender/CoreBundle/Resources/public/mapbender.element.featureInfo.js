(function ($) {

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            autoActivate: false,
            deactivateOnClose: true,
            displayType: 'tabs',
            printResult: false,
            onlyValid: false,
            highlighting: false,
            fillColorDefault: 'rgba(255,165,0,0.4)',
            fillColorHover: 'rgba(255,0,0,0.7)',
            fontColorDefault: '#000000',
            fontSizeDefault: 12,
            fontColorHover: '#000000',
            fontSizeHover: 12,
            maxCount: 100,
            width: 700,
            height: 500
        },
        mbMap: null,
        isPopup: true,
        popup: null,
        mobilePane: null,
        isActive: false,
        highlightLayer: null,
        showingSources: [],
        template: {
            header: null,
            content: null
        },
        iframeScriptContent_: '',
        headerIdPrefix_: '',
        // when requesting feature info for new coordinates, the previous
        // features should be deleted (but not when results from different sources arrive one by one)
        startedNewRequest: false,

        _create: function () {
            this.iframeScriptContent_ = $('.-js-iframe-script-template[data-script]', this.element).remove().attr('data-script');
            this.mobilePane = this.element.closest('.mobilePane');
            // in any case prevent the mobile pane from opening when the feature info is toggled by a button
            this.element.data('open-mobilepane', false);

            this.template = {
                header: $('.js-header', this.element).remove(),
                content: $('.js-content', this.element).remove()
            };
            if (this.options.displayType === 'tabs') {
                this.headerIdPrefix_ = 'tab';
            } else {
                this.headerIdPrefix_ = 'accordion';
            }
            // Avoid shared access to prototype values on non-scalar properties
            this.showingSources = [];
            initTabContainer(this.element);

            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self._setup(mbMap);
            }, function () {
                Mapbender.checkTarget("mbFeatureInfo");
            });
        },


        _setup: function (mbMap) {
            this.isPopup = Mapbender.ElementUtil.checkDialogMode(this.element);
            this.mbMap = mbMap;
            this._setupMapClickHandler();
            if (this.options.autoActivate || this.options.autoOpen) { // autoOpen old configuration
                this.activate();
            }

            if (this.options.highlighting) {
                this.highlightLayer = new ol.layer.Vector({
                    source: new ol.source.Vector({}),
                    style: this._createLayerStyle()
                });

                this.mbMap.getModel().olMap.addLayer(this.highlightLayer);
                window.addEventListener("message", (message) => this._postMessage(message));
                this._createHighlightControl();
            }

            $(document).bind('mbmapsourcechanged', this._reorderTabs.bind(this));
            $(document).bind('mbmapsourcesreordered', this._reorderTabs.bind(this));
            if (this.options.printResult) {
                this.element.on('mb.shown.tab', '.tab', () => this._checkPrintVisibility());
                this.element.on('selected', '.accordion', () => this._checkPrintVisibility());
            }

            this._trigger('ready');
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function (callback) {
            this.activate(callback);
        },
        reveal: function () {
            this.activate();
        },
        hide: function () {
            this.deactivate();
        },
        activate: function (callback) {
            this.callback = callback;
            this.mbMap.element.addClass('mb-feature-info-active');
            this.isActive = true;

            $(this.element).trigger('mapbender.elementactivated', {
                widget: this,
                sender: this,
                active: true
            });
        },
        deactivate: function () {
            this.mbMap.element.removeClass('mb-feature-info-active');
            this.isActive = false;
            this.clearAll();

            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            $(this.element).trigger('mapbender.elementdeactivated', {
                widget: this,
                sender: this,
                active: false
            });
        },
        /**
         * Trigger the Feature Info call for each layer.
         * Also set up feature info dialog if needed.
         */
        _triggerFeatureInfo: function (x, y) {
            if (!this.isActive || !Mapbender.ElementUtil.checkResponsiveVisibility(this.element)) {
                return;
            }
            var self = this, i;
            var model = this.mbMap.getModel();
            var showingPreviously = this.showingSources.slice();
            this.showingSources.splice(0);  // clear
            var sources = model.getSources();
            const validSources = [];
            var promises = [];

            // Iterate in reverse to match layertree display order
            for (var s = sources.length - 1; s >= 0; --s) {
                const source = sources[s];
                if (!source.featureInfoEnabled()) continue;
                validSources.push(source);

                const options = {...this.options, injectionScript: this._getInjectionScript(source.id)};
                const [url, fiPromise] = source.loadFeatureInfo(this.mbMap.getModel(), x, y, options, this.element.attr('id'));
                fiPromise.then((result) => {
                    if (result) {
                        this.showingSources.push(source);
                        this.showResponseContent_(source, result);
                        this._open();
                    } else {
                        this._removeContent(source);
                    }
                }).catch((err) => {
                    console.log(err);
                    this._removeContent(source);
                });

                promises.push(fiPromise);

                this.addDisplayStub_(source, url);
            }

            // remove popup tabs where feature info is no longer available
            for (i = 0; i < showingPreviously.length; ++i) {
                if (-1 === validSources.indexOf(showingPreviously[i])) {
                    this._removeContent(showingPreviously[i]);
                }
            }

            if (!validSources.length) {
                self._handleZeroResponses();
            }

            this.startedNewRequest = true;

            Promise.allSettled(promises).then(() => {
                if (!this.showingSources.length) {
                    // No response content to display, no more requests pending
                    // Remain active, but hide popup
                    self._handleZeroResponses();
                }
            });
        },
        _open: function () {
            if (this.highlightLayer && this.startedNewRequest) {
                this.highlightLayer.getSource().clear();
                this.startedNewRequest = false;
            }

            if (this.mobilePane.length) {
                $(document).trigger('mobilepane.switch-to-element', {
                    element: this.element
                });
                return;
            }

            if (!this.isPopup) return; // no intialization necessary for sidepane

            if (!this.popup || !this.popup.$element) {
                this.popup = new Mapbender.Popup({
                    title: this.element.attr('data-title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: false,
                    detachOnClose: false,
                    content: this.element,
                    resizable: true,
                    cssClass: 'featureinfoDialog',
                    width: this.options.width,
                    height: this.options.height,
                    buttons: this._getPopupButtonOptions()
                });
                this.popup.$element.on('close', () => this._close());
            }
            this.popup.$element.show();
            this.popup.$element.find('.popupClose').focus();
        },
        _hide: function () {
            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
        },
        _close: function () {
            if (this.options.deactivateOnClose) {
                this.deactivate();
            } else {
                this._hide();
            }
        },
        _handleZeroResponses: function () {
            this.element.find('.-js-placeholder').addClass("hidden");

            if (this.popup) {
                this._hide();
            } else {
                this.element.find('.-js-no-content').removeClass("hidden");
                this.element.find('.js-content-parent').addClass("hidden");
            }
        },
        /**
         * @returns {Array<Object>}
         */
        _getPopupButtonOptions: function () {
            var buttons = [{
                label: Mapbender.trans('mb.actions.close'),
                cssClass: 'btn btn-sm btn-light popupClose'
            }];
            if (this.options.printResult) {
                buttons.unshift({
                    label: Mapbender.trans('mb.actions.print'),
                    // both buttons float right => will visually appear in reverse dom order, Print first
                    cssClass: 'btn btn-sm btn-primary js-btn-print',
                    callback: () => this._printContent(),
                });
            }
            return buttons;
        },
        _removeContent: function (source) {
            $('[data-source-id="' + source.id + '"]', this.element).remove();
            $('.js-content-content[data-source-id="' + source.id + '"]', this.element).remove();
            this._removeFeaturesBySourceId(source.id);
            // If there are tabs / accordions remaining, ensure at least one of them is active
            var $container = this.element.find('.tabContainer,.accordionContainer');
            if (!$container.find('.active').not('.hidden').length) {
                $container.find('>.tabs .tab, >.accordion').not('hidden').first().click();
            }
        },
        clearAll: function () {
            if (this.highlightLayer) {
                this.highlightLayer.getSource().clear();
            }
            if (this.isPopup) {
                $('>.accordionContainer', this.element).empty();
                $('>.tabContainer > .tabs', this.element).empty();
                $('>.tabContainer > :not(.tabs)', this.element).remove();
                this.showingSources.splice(0);
            }
        },
        _getContentId: function (source) {
            return ['container', source.id].join('-');
        },
        _getHeaderId: function (source) {
            return [this.headerIdPrefix_, source.id].join('-');
        },
        addDisplayStub_: function (source, url) {
            var headerId = this._getHeaderId(source);
            var $header = $('#' + headerId, this.element);
            if ($header.length === 0) {
                $header = this.template.header.clone();
                $header.attr('id', headerId);
                $header.attr('data-source-id', source.id);
                $header.addClass('hidden');
                $('.js-header-parent', this.element).append($header);
            }
            $header.text(source.getTitle());
            $('a', $header).remove();

            var contentId = this._getContentId(source);
            var $content = $('#' + contentId, this.element);
            if ($content.length === 0) {
                $content = this.template.content.clone();
                $content.attr('id', contentId);
                $content.attr('data-source-id', source.id);
                $content.addClass('hidden');
                $('.js-content-parent', this.element).append($content);
            }

            if (url) {
                $header.append($(document.createElement('a'))
                    .attr('href', url)
                    .attr('target', '_blank')
                    .append($(document.createElement('i')).addClass('fa fas fa-fw fa-external-link'))
                );
                // For print interaction
                $content.attr('data-url', url);
            }
        },
        showResponseContent_: function (source, content) {
            this.element.find('.-js-no-content').addClass("hidden");
            this.element.find('.js-content-parent').removeClass("hidden");
            this.element.find('.-js-placeholder').addClass("hidden");

            var headerId = this._getHeaderId(source);
            var $header = $('#' + headerId, this.element);
            if (!$('>.active', $header.closest('.tabContainer,.accordionContainer')).not('.hidden').length) {
                $header.addClass('active');
                if (this.options.printResult) {
                    setTimeout(() => {
                        this._checkPrintVisibility();
                    });
                }
            }
            var contentId = this._getContentId(source);
            var $content = $('#' + contentId, this.element);
            $content.toggleClass('active', $('#' + this._getHeaderId(source), this.element).hasClass('active'));

            var $appendTo = $content.hasClass('js-content-content') && $content || $('.js-content-content', $content);
            $appendTo.empty().append(content);
            $header.removeClass('hidden');
            $content.removeClass('hidden');
            this._reorderTabs();
        },
        _checkPrintVisibility: function() {
            const activeTab = this.element.find('.tab.active, .accordion.active');
            const activeTabHasLink = activeTab.children('a').length > 0;
            const printButton = this.popup?.$element?.find('.js-btn-print') ?? this.element.find('.js-btn-print');
            activeTabHasLink ? printButton.removeAttr('disabled') : printButton.attr('disabled', 'readonly');
        },
        _printContent: function () {
            var $documentNode = $('.js-content.active', this.element);
            var url = $documentNode.attr('data-url');
            if (!url) return;
            // Always use proxy. Calling window.print on a cross-origin window is not allowed.
            var proxifiedUrl = Mapbender.configuration.application.urls.proxy + '?' + new URLSearchParams({url: url});
            var w = window.open(proxifiedUrl);
            w.print();
        },
        _setupMapClickHandler: function () {
            $(document).on('mbmapclick', (event, data) => {
                this._triggerFeatureInfo(data.pixel[0], data.pixel[1]);
            });

            $(document).on('mbmapsourcechanged', (event, data) => {
                this._removeFeaturesBySourceId(data.source.id);
            });
        },
        _createLayerStyle: function () {
            var settingsDefault = {
                fill: this.options.fillColorDefault,
                stroke: this.options.strokeColorDefault || this.options.fillColorDefault,
                strokeWidth: this.options.strokeWidthDefault,
                fontColor: this.options.fontColorDefault || this.options.strokeColorDefault,
                fontSize: this.options.fontSizeDefault,
                pointRadius: this.options.pointRadiusDefault || (this.options.strokeWidthDefault * 3),
            };
            var settingsHover = {
                fill: this.options.fillColorHover || settingsDefault.fill,
                stroke: this.options.strokeColorHover || this.options.fillColorHover || settingsDefault.stroke,
                strokeWidth: this.options.strokeWidthHover,
                fontColor: this.options.fontColorHover || this.options.strokeColorHover || settingsDefault.fontColor,
                fontSize: this.options.fontSizeHover || settingsDefault.fontSize,
                pointRadius: this.options.pointRadiusHover || (this.options.strokeWidthHover * 3),
            };

            const self = this;
            return function (feature) {
                const hover = feature.get('hover');
                const point = feature.getGeometry().getType() === 'Point';
                return [self.processStyle_(hover ? settingsHover : settingsDefault, hover, point, feature)];
            }
        },
        processStyle_: function (settings, hover, point, feature) {
            var fillRgba = Mapbender.StyleUtil.parseCssColor(settings.fill);
            var strokeRgba = Mapbender.StyleUtil.parseCssColor(settings.stroke);
            var strokeWidth = parseInt(settings.strokeWidth);

            strokeWidth = isNaN(strokeWidth) && (hover && 3 || 1) || strokeWidth;
            const fill = new ol.style.Fill({
                color: fillRgba,
            });
            const stroke = strokeWidth && new ol.style.Stroke({
                color: strokeRgba,
                width: strokeWidth
            });
            const text = strokeWidth && new ol.style.Text({
                font: parseInt(settings.fontSize) + 'px sans-serif',
                fill: new ol.style.Fill({
                    color: settings.fontColor,
                }),
                text: feature.get("label"),
            });

            if (point) {
                return new ol.style.Style({
                    image: new ol.style.Circle({
                        fill: fill,
                        stroke: stroke,
                        radius: settings.pointRadius ? parseInt(settings.pointRadius) : (strokeWidth * 3),
                    }),
                    text: text,
                    zIndex: hover ? 1 : undefined,
                });
            }

            return new ol.style.Style({
                fill: fill,
                stroke: stroke,
                text: text,
                zIndex: hover ? 1 : undefined,
            });
        },
        _postMessage: function (message) {
            var data = message.data;
            if (data.elementId !== this.element.attr('id')) {
                return;
            }
            if (this.isActive && this.highlightLayer && data.command === 'features') {
                this._populateFeatureInfoLayer(data);
            }
            if (this.isActive && this.highlightLayer && data.command === 'hover') {
                var feature = this.highlightLayer.getSource().getFeatureById(data.id);
                if (feature) {
                    feature.set('hover', !!data.state);
                }
            }
        },
        _populateFeatureInfoLayer: function (data) {
            var features = (data.features || []).map(function (featureData) {
                var feature = Mapbender.Model.parseWktFeature(featureData.wkt, featureData.srid);
                feature.setId(featureData.id);
                feature.set('sourceId', data.sourceId);
                feature.set('label', featureData.label);
                return feature;
            });

            this._removeFeaturesBySourceId(data.sourceId);
            this.highlightLayer.getSource().addFeatures(features);
        },
        _removeFeaturesBySourceId: function (sourceId) {
            if (this.highlightLayer) {
                var source = this.highlightLayer.getSource();
                var features = source.getFeatures().filter(function (feature) {
                    return feature.get('sourceId') === sourceId;
                });
                features.forEach(function (feature) {
                    source.removeFeature(feature);
                });
            }
        },
        _createHighlightControl: function () {
            var highlightControl = new ol.interaction.Select({
                condition: ol.events.condition.pointerMove,
                layers: [this.highlightLayer],
                style: null,
                multi: true
            });
            var featureStack = [];

            highlightControl.on('select', function (e) {
                // Avoid highlighting multiple geometrically nested features
                // simultaneously. Re-highlight "outer" features when the mouse
                // leaves the "inner" feature.
                featureStack = featureStack.filter(function (feature) {
                    return -1 === e.deselected.indexOf(feature);
                });
                e.deselected.forEach(function (feature) {
                    feature.set('hover', false);
                });
                e.selected.forEach(function (feature) {
                    featureStack.forEach(function (feature) {
                        feature.set('hover', false);
                    });
                    featureStack.push(feature);
                });
                if (featureStack.length) {
                    featureStack[featureStack.length - 1].set('hover', true);
                }
            });

            this.mbMap.getModel().olMap.addInteraction(highlightControl);
            highlightControl.setActive(true);
        },
        _getInjectionScript: function (sourceId) {
            var parts = [
                '<script>',
                // Hack to prevent DOMException when loading jquery
                'var replaceState = window.history.replaceState;',
                'window.history.replaceState = function(){ try { replaceState.apply(this,arguments); } catch(e) {} };',
                // Highlighting support (generate source-scoped feature ids)
                ['var sourceId = "', sourceId, '";'].join(''),
                ['var elementId = ', JSON.stringify(this.element.attr('id')), ';'].join(''),
                this.iframeScriptContent_,
                '</script>'
            ];
            return parts.join('');
        },
        _reorderTabs: function () {
            // the model sources contain all sources in the order they are currently displayed on the map
            // this matches the order in the layer tree
            const sources = this.mbMap.getModel().getSources();
            let sourcesOrderMap = {};
            let index = 0;
            for (let source of sources) {
                sourcesOrderMap[source.id] = index++;
            }

            const $container = $('.tabContainer > .tabs, .accordionContainer', this.element);
            const $tabs = $container.children();
            // sort tabs (or accordion panels) by comparing their position using the previously created map
            const $sortedTabs = $tabs.sort(function (a, b) {
                const orderA = sourcesOrderMap[$(a).data('source-id')] ?? Number.MAX_SAFE_INTEGER;
                const orderB = sourcesOrderMap[$(b).data('source-id')] ?? Number.MAX_SAFE_INTEGER;
                return orderB - orderA;
            });
            $container.append($sortedTabs);
        }

    });

})(jQuery);

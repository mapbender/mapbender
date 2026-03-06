(function() {

    class MbFeatureInfo extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);
            this.iframeScriptContent_ = $('.-js-iframe-script-template[data-script]', this.$element).remove().attr('data-script');
            this.mobilePane = this.$element.closest('.mobilePane');
            // prevent mobile pane automatic opening via button toggle
            this.$element.data('open-mobilepane', false);

            this.template = {
                header: $('.js-header', this.$element).remove(),
                content: $('.js-content', this.$element).remove()
            };
            if (this.options.displayType === 'tabs') {
                this.headerIdPrefix_ = 'tab';
            } else {
                this.headerIdPrefix_ = 'accordion';
            }
            // Avoid shared access to prototype values on non-scalar properties
            this.showingSources = [];
            initTabContainer(this.$element);

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, function() {
                Mapbender.checkTarget('mbFeatureInfo');
            });
        }

        _setup(mbMap) {
            this.isPopup = Mapbender.ElementUtil.checkDialogMode(this.$element);
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
                window.addEventListener('message', (message) => this._postMessage(message));
                this._createHighlightControl();
            }

            $(document).bind('mbmapsourcechanged', this._reorderTabs.bind(this));
            $(document).bind('mbmapsourcesreordered', this._reorderTabs.bind(this));
            if (this.options.printResult) {
                this.$element.on('mb.shown.tab', '.tab', () => this._checkPrintVisibility());
                this.$element.on('selected', '.accordion', () => this._checkPrintVisibility());
            }

            Mapbender.elementRegistry.markReady(this);
        }

        reveal() {
            this.activate();
        }

        hide() {
            this.deactivate();
        }

        activate(callback) {
            this.callback = callback;
            this.mbMap.element.addClass('mb-feature-info-active');
            this.isActive = true;

            this.$element.trigger('mapbender.elementactivated', {
                widget: this,
                sender: this,
                active: true
            });
        }

        deactivate() {
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
            this.$element.trigger('mapbender.elementdeactivated', {
                widget: this,
                sender: this,
                active: false
            });
        }

        _triggerFeatureInfo(x, y) {
            if (!this.isActive || !Mapbender.ElementUtil.checkResponsiveVisibility(this.$element)) {
                return;
            }
            const model = this.mbMap.getModel();
            const showingPreviously = this.showingSources.slice();
            this.showingSources.splice(0); // clear
            const sources = model.getSources();
            const validSources = [];
            const promises = [];

            // Iterate in reverse to match layertree display order
            for (let s = sources.length - 1; s >= 0; --s) {
                const source = sources[s];
                if (!source.featureInfoEnabled()) continue;
                validSources.push(source);

                const options = { ...this.options, injectionScript: this._getInjectionScript(source.id) };
                const [url, fiPromise] = source.loadFeatureInfo(this.mbMap.getModel(), x, y, options, this.$element.attr('id'));
                fiPromise.then((result) => {
                    if (result) {
                        this.showingSources.push(source);
                        this.showResponseContent_(source, result);
                        this.activateByButton();
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
            for (let i = 0; i < showingPreviously.length; ++i) {
                if (validSources.indexOf(showingPreviously[i]) === -1) {
                    this._removeContent(showingPreviously[i]);
                }
            }

            if (!validSources.length) {
                this._handleZeroResponses();
            }

            this.startedNewRequest = true;

            Promise.allSettled(promises).then(() => {
                if (!this.showingSources.length) {
                    // No response content to display, no more requests pending
                    // Remain active, but hide popup
                    this._handleZeroResponses();
                }
            });
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                draggable: true,
                modal: false,
                closeOnESC: false,
                detachOnClose: false,
                content: this.$element,
                resizable: true,
                cssClass: 'featureinfoDialog',
                width: this.options.width,
                height: this.options.height,
                buttons: this._getPopupButtonOptions()
            };
        }

        activateByButton(callback, mbButton) {
            if (this.highlightLayer && this.startedNewRequest) {
                this.highlightLayer.getSource().clear();
                this.startedNewRequest = false;
            }

            if (this.mobilePane.length) {
                $(document).trigger('mobilepane.switch-to-element', { element: this.$element });
                return;
            }

            if (!this.isPopup) return; // sidepane mode

            super.activateByButton(callback, mbButton);
        }

        closeByButton() {
            if (this.options.deactivateOnClose) {
                this.deactivate();
            } else {
                super.closeByButton();
            }
        }

        _handleZeroResponses() {
            this.$element.find('.-js-placeholder').addClass('hidden');

            if (this.popup) {
                super.closeByButton();
            } else {
                this.$element.find('.-js-no-content').removeClass('hidden');
                this.$element.find('.js-content-parent').addClass('hidden');
            }
        }

        _getPopupButtonOptions() {
            const buttons = [];
            if (this.options.printResult) {
                buttons.unshift({
                    label: Mapbender.trans('mb.actions.print'),
                    cssClass: 'btn btn-sm btn-primary js-btn-print',
                    callback: () => this._printContent(),
                });
            }
            return buttons;
        }

        _removeContent(source) {
            $('[data-source-id="' + source.id + '"]', this.$element).remove();
            $('.js-content-content[data-source-id="' + source.id + '"]', this.$element).remove();
            this._removeFeaturesBySourceId(source.id);
            const $container = this.$element.find('.tabContainerAlt,.accordionContainer');
            if (!$container.find('.active').not('.hidden').length) {
                $container.find('>.tabs .tab, >.accordion').not('hidden').first().click();
            }
        }

        clearAll() {
            if (this.highlightLayer) {
                this.highlightLayer.getSource().clear();
            }
            if (this.isPopup) {
                $('>.accordionContainer', this.$element).empty();
                $('>.tabContainerAlt > .tabs', this.$element).empty();
                $('>.tabContainerAlt > :not(.tabs)', this.$element).remove();
                this.showingSources.splice(0);
            }
        }

        _getContentId(source) {
            return ['container', source.id].join('-');
        }

        _getHeaderId(source) {
            return [this.headerIdPrefix_, source.id].join('-');
        }

        addDisplayStub_(source, url) {
            const headerId = this._getHeaderId(source);
            let $header = $('#' + headerId, this.$element);
            if ($header.length === 0) {
                $header = this.template.header.clone();
                $header.attr('id', headerId);
                $header.attr('data-source-id', source.id);
                $header.addClass('hidden');
                $('.js-header-parent', this.$element).append($header);
            }
            $header.text(source.getTitle());
            $('a', $header).remove();

            const contentId = this._getContentId(source);
            let $content = $('#' + contentId, this.$element);
            if ($content.length === 0) {
                $content = this.template.content.clone();
                $content.attr('id', contentId);
                $content.attr('data-source-id', source.id);
                $content.addClass('hidden');
                $('.js-content-parent', this.$element).append($content);
            }

            if (url) {
                $header.append($(document.createElement('a'))
                    .attr('href', url)
                    .attr('target', '_blank')
                    .append($(document.createElement('i')).addClass('fa fas fa-fw fa-external-link hover-highlight-effect'))
                );
                $content.attr('data-url', url);
            }
        }

        showResponseContent_(source, content) {
            this.$element.find('.-js-no-content').addClass('hidden');
            this.$element.find('.js-content-parent').removeClass('hidden');
            this.$element.find('.-js-placeholder').addClass('hidden');

            const headerId = this._getHeaderId(source);
            const $header = $('#' + headerId, this.$element);
            if (!$('>.active', $header.closest('.tabContainerAlt,.accordionContainer')).not('.hidden').length) {
                $header.addClass('active');
                if (this.options.printResult) {
                    setTimeout(() => { this._checkPrintVisibility(); });
                }
            }
            const contentId = this._getContentId(source);
            const $content = $('#' + contentId, this.$element);
            $content.toggleClass('active', $('#' + this._getHeaderId(source), this.$element).hasClass('active'));

            const $appendTo = $content.hasClass('js-content-content') && $content || $('.js-content-content', $content);
            $appendTo.empty().append(content);
            $header.removeClass('hidden');
            $content.removeClass('hidden');
            this._reorderTabs();
        }

        _checkPrintVisibility() {
            const activeTab = this.$element.find('.tab.active, .accordion.active');
            const activeTabHasLink = activeTab.children('a').length > 0;
            const printButton = this.popup?.$element?.find('.js-btn-print') ?? this.$element.find('.js-btn-print');
            activeTabHasLink ? printButton.removeAttr('disabled') : printButton.attr('disabled', 'readonly');
        }

        _printContent() {
            const $documentNode = $('.js-content.active', this.$element);
            const url = $documentNode.attr('data-url');
            if (!url) return;
            const proxifiedUrl = Mapbender.configuration.application.urls.proxy + '?' + new URLSearchParams({ url: url });
            const w = window.open(proxifiedUrl);
            w.print();
        }

        _setupMapClickHandler() {
            $(document).on('mbmapclick', (event, data) => {
                this._triggerFeatureInfo(data.pixel[0], data.pixel[1]);
            });

            $(document).on('mbmapsourcechanged', (event, data) => {
                this._removeFeaturesBySourceId(data.source.id);
            });
        }

        _createLayerStyle() {
            const settingsDefault = {
                fill: this.options.fillColorDefault,
                stroke: this.options.strokeColorDefault || this.options.fillColorDefault,
                strokeWidth: this.options.strokeWidthDefault,
                fontColor: this.options.fontColorDefault || this.options.strokeColorDefault,
                fontSize: this.options.fontSizeDefault,
                pointRadius: this.options.pointRadiusDefault || (this.options.strokeWidthDefault * 3),
            };
            const settingsHover = {
                fill: this.options.fillColorHover || settingsDefault.fill,
                stroke: this.options.strokeColorHover || this.options.fillColorHover || settingsDefault.stroke,
                strokeWidth: this.options.strokeWidthHover,
                fontColor: this.options.fontColorHover || this.options.strokeColorHover || settingsDefault.fontColor,
                fontSize: this.options.fontSizeHover || settingsDefault.fontSize,
                pointRadius: this.options.pointRadiusHover || (this.options.strokeWidthHover * 3),
            };

            return (feature) => {
                const hover = feature.get('hover');
                const point = feature.getGeometry().getType() === 'Point';
                return [this.processStyle_(hover ? settingsHover : settingsDefault, hover, point, feature)];
            };
        }

        processStyle_(settings, hover, point, feature) {
            const fillRgba = Mapbender.StyleUtil.parseCssColor(settings.fill);
            const strokeRgba = Mapbender.StyleUtil.parseCssColor(settings.stroke);
            let strokeWidth = parseInt(settings.strokeWidth);

            strokeWidth = (isNaN(strokeWidth) && (hover && 3 || 1)) || strokeWidth;
            const fill = new ol.style.Fill({ color: fillRgba });
            const stroke = strokeWidth && new ol.style.Stroke({ color: strokeRgba, width: strokeWidth });
            const text = strokeWidth && new ol.style.Text({
                font: parseInt(settings.fontSize) + 'px sans-serif',
                fill: new ol.style.Fill({ color: settings.fontColor }),
                text: feature.get('label'),
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
        }

        _postMessage(message) {
            const data = message.data;
            if (data.elementId !== this.$element.attr('id')) {
                return;
            }
            if (this.isActive && this.highlightLayer && data.command === 'features') {
                this._populateFeatureInfoLayer(data);
            }
            if (this.isActive && this.highlightLayer && data.command === 'hover') {
                const feature = this.highlightLayer.getSource().getFeatureById(data.id);
                if (feature) {
                    feature.set('hover', !!data.state);
                }
            }
        }

        _populateFeatureInfoLayer(data) {
            const features = (data.features || []).map((featureData) => {
                const feature = Mapbender.Model.parseWktFeature(featureData.wkt, featureData.srid);
                feature.setId(featureData.id);
                feature.set('sourceId', data.sourceId);
                feature.set('label', featureData.label);
                return feature;
            });

            this._removeFeaturesBySourceId(data.sourceId);
            this.highlightLayer.getSource().addFeatures(features);
        }

        _removeFeaturesBySourceId(sourceId) {
            if (this.highlightLayer) {
                const source = this.highlightLayer.getSource();
                const features = source.getFeatures().filter((feature) => feature.get('sourceId') === sourceId);
                features.forEach((feature) => source.removeFeature(feature));
            }
        }

        _createHighlightControl() {
            const highlightControl = new ol.interaction.Select({
                condition: ol.events.condition.pointerMove,
                layers: [this.highlightLayer],
                style: null,
                multi: true
            });
            let featureStack = [];

            highlightControl.on('select', function(e) {
                featureStack = featureStack.filter(function(feature) {
                    return e.deselected.indexOf(feature) === -1;
                });
                e.deselected.forEach(function(feature) {
                    feature.set('hover', false);
                });
                e.selected.forEach(function(feature) {
                    featureStack.forEach(function(feature) { feature.set('hover', false); });
                    featureStack.push(feature);
                });
                if (featureStack.length) {
                    featureStack[featureStack.length - 1].set('hover', true);
                }
            });

            this.mbMap.getModel().olMap.addInteraction(highlightControl);
            highlightControl.setActive(true);
        }

        _getInjectionScript(sourceId) {
            const parts = [
                '<script>',
                'var replaceState = window.history.replaceState;',
                'window.history.replaceState = function(){ try { replaceState.apply(this,arguments); } catch(e) {} };',
                'var sourceId = "' + sourceId + '";',
                'var elementId = ' + JSON.stringify(this.$element.attr('id')) + ';',
                this.iframeScriptContent_,
                '</script>'
            ];
            return parts.join('');
        }

        _reorderTabs() {
            const sources = this.mbMap.getModel().getSources();
            const sourcesOrderMap = {};
            let index = 0;
            for (let source of sources) {
                sourcesOrderMap[source.id] = index++;
            }

            const $container = $('.tabContainerAlt > .tabs, .accordionContainer', this.$element);
            const $tabs = $container.children();
            const $sortedTabs = $tabs.sort(function(a, b) {
                const orderA = sourcesOrderMap[$(a).data('source-id')] ?? Number.MAX_SAFE_INTEGER;
                const orderB = sourcesOrderMap[$(b).data('source-id')] ?? Number.MAX_SAFE_INTEGER;
                return orderB - orderA;
            });
            $container.append($sortedTabs);
        }
    }

    window.Mapbender = window.Mapbender || {};
    window.Mapbender.Element = window.Mapbender.Element || {};
    // Register new class
    window.Mapbender.Element.MbFeatureInfo = MbFeatureInfo;

})();

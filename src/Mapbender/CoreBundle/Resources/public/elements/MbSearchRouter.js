(function() {
    class MbSearchRouter extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);
            this.callbackUrl = Mapbender.configuration.application.urls.element + '/' + this.$element.attr('id') + '/';
            this.useDialog_ = this.checkDialogMode();
            this.selected = null;
            this.highlightLayer = null;
            this.popup = null;
            this.mbMap = null;
            this.trHighlightClass = 'table-primary';
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, () => {
                Mapbender.checkTarget('mbSearchRouter');
            });
        }

        /**
         * Remove last search results
         */
        removeLastResults() {
            if (this.highlightLayer) {
                this.highlightLayer.clear();
            }
            this.currentFeature = null;
        }

        _setup() {
            const widget = this;
            const $element = this.$element;

            const routeSelect = $('select#search_routes_route', $element);
            routeSelect.closest('.dropdown').css('display', this.options.routes.length > 1 ? 'block' : 'none');

            $element.on('submit', '.search-forms form', function(evt) {
                evt.preventDefault();
                widget._search();
            });
            $element.on('reset', '.search-forms form', function() {
                widget.removeLastResults();
            });
            // Prepare autocompletes
            $('form input[data-autocomplete="on"]', $element).each(
                $.proxy(widget._setupAutocomplete, widget));
            $('form input[data-autocomplete^="custom:"]', $element).each(
                $.proxy(widget._setupCustomAutocomplete, widget));

            // Listen to changes of search select (switching and forms resetting)
            routeSelect.on('change', $.proxy(this._selectSearch, this));
            $element.on('click', '.search-action-buttons [data-action]', function() {
                switch ($(this).attr('data-action')) {
                    case ('reset'):
                        widget._reset();
                        break;
                    case ('search'):
                        widget._search();
                        break;
                }
            });

            this.highlightLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
            this._setupResultCallback();
            Mapbender.elementRegistry.markReady(this);
            if (this.checkAutoOpen()) {
                this.activateByButton();
            }
            this.initTableEvents_();
            this._setupCsrf();
            routeSelect.trigger('change');
        }

        async _setupCsrf() {
            const token = await Mapbender.ElementUtil.getCsrfToken(this, this.callbackUrl + "0/csrf");
            this.$element.find('input[name*="_token"]').attr('value', token);
        }

        activate() {
            this.notifyWidgetActivated();
        }

        deactivate() {
            this.notifyWidgetDeactivated();
        }

        getPopupOptions() {
            return {
                title: this.$element.attr('data-title'),
                draggable: true,
                modal: false,
                closeOnESC: false,
                content: this.$element,
                width: this.options.width ? this.options.width : 450,
                resizable: true,
                height: this.options.height ? this.options.height : 500,
                detachOnClose: false,
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.search'),
                        cssClass: 'btn btn-sm btn-primary',
                        callback: $.proxy(this._search, this)
                    },
                    {
                        label: Mapbender.trans('mb.actions.reset'),
                        cssClass: 'btn btn-sm btn-light',
                        callback: $.proxy(this._reset, this)
                    }
                ]
            };
        }

        activateByButton(callback, mbButton) {
            super.activateByButton(callback, mbButton);
            this.activate();
        }

        closeByButton() {
            super.closeByButton();
            this.deactivate();
        }

        /**
         * Set up result table when a search was selected.
         *
         * @param  jqEvent event Change event
         */
        _selectSearch(event) {
            const selected = this.selected = $(event.target).val();

            $('form', this.$element).each(function() {
                const form = $(this);
                if (form.attr('name') === selected) {
                    form.show();
                } else {
                    form.hide();
                }
                form.get(0).reset();
            });

            $('.search-results', this.$element).empty();
            const route = this.getCurrentRoute();
            this.featureStyles = this._createStyleMap(route.results.styleMap);
        }

        /**
         * Reset current search form
         */
        _reset() {
            $('select#search_routes_route', this.$element).change();
            this.currentFeature = null;
        }

        /**
         * Set up autocomplete widgets for all inputs with data-autcomplete="on"
         *
         * @param {*} idx
         * @param {Node} input
         */
        _setupAutocomplete(idx, input) {
            const self = this;
            const $input = $(input);
            $input.parent().addClass('autocompleteWrapper');
            $input.autocomplete({
                appendTo: $input.parent().get(0),
                delay: $input.data('autocomplete-delay') || 500,
                minLength: $input.data('autocomplete-minlength') || 3,
                position: {
                    of: false
                },
                classes: {
                    'ui-autocomplete': 'dropdownList'
                },
                source: function(request, response) {
                    self._autocompleteSource($input).then(function(data) {
                        response(data.results);
                    }, function() {
                        response([]);
                    });
                }
            });
        }

        /**
         * Set up autocpmplete provided by custom widget (data-autcomplete="custom:<widget>")
         *
         * @param  integer      idx   Running index
         * @param  HTMLDomNode  input Input element
         */
        _setupCustomAutocomplete(idx, input) {
            const plugin = $(input).data('autocomplete').substr(7);
            $(input)[plugin]();
        }

        /**
         * Generate autocomplete request
         *
         * @param {jQuery} $input
         */
        _autocompleteSource($input) {
            const url = this.callbackUrl + this.selected + '/autocomplete';
            const formValues = this._getFormValues($input.closest('form'));
            const data = {
                key: $input.attr('name').replace(/^[^[]*\[/, '').replace(/[\]].*$/, ''),
                value: $input.val(),
                srs: this.mbMap.model.getCurrentProjectionCode(),
                extent: this.mbMap.model.getMaxExtentArray(),
                properties: formValues
            };
            return $.getJSON({
                url: url,
                data: JSON.stringify(data),
                method: 'POST'
            });
        }

        /**
         * @param $form jQuery
         * @return {boolean}
         * @private
         */
        _validateForm($form) {
            const form = $form.get(0);
            if (form.reportValidity && !form.reportValidity()) return false;

            let valid = true;
            $form.find(':input[required]').each((index, el) => {
                if ($(el).val() === '') valid = false;
            });
            return valid;
        }

        _prepareSearchRequestData(formValues) {
            return {
                properties: formValues,
                extent: this.mbMap.model.getMaxExtentArray(),
                srs: this.mbMap.model.getCurrentProjectionCode()
            };
        }

        _createFeaturesFromResponse(response) {
            return response.features.map((data) => {
                const gjInput = {
                    type: 'Feature',
                    geometry: data.geometry,
                    properties: data.properties || {}
                };
                return this.mbMap.model.parseGeoJsonFeature(gjInput);
            });
        }

        /**
         * Start a search, but only after successful form validation
         */
        _search() {
            const $form = this.$element.find('form[name="' + this.selected + '"]');
            if (!this._validateForm($form)) return;

            const formValues = this._getFormValues($form);
            const data = this._prepareSearchRequestData(formValues);
            const url = this.callbackUrl + this.selected + '/search';

            $.getJSON({
                url: url,
                data: JSON.stringify(data),
                method: 'POST'
            })
                .fail((err) => {
                    Mapbender.error(Mapbender.trans(err.responseText));
                })
                .then((response) => {
                    const features = this._createFeaturesFromResponse(response);
                    this._searchResults(features);
                });
        }

        /**
         * Prepare search result table
         */
        _prepareResultTable(container) {
            const currentRoute = this.getCurrentRoute();
            if (currentRoute && currentRoute.results.headers) {
                container.append(this.renderTable(currentRoute));
            }
        }

        /**
         * @param {Object} routeConfig
         * @returns {HTMLElement|jQuery}
         */
        renderTable(routeConfig) {
            const self = this;
            const headers = routeConfig.results.headers;
            const $headers = $(document.createElement('tr'));
            const table = $(document.createElement('table')).addClass('table table-condensed table-striped table-hover');

            for (let header in headers) {
                let th = $('<th data-column="' + header + '" data-order=""></th>');
                th.text(headers[header]);
                let sortIcon = '<span class="sortIcon ms-1"><i class="fa fa-sort" aria-hidden="true"></i></span>';
                th.append(sortIcon);
                th.css('cursor', 'pointer');
                th.on('click', (e) => self.onTableHeadClick(e));
                $headers.append(th);
            }

            table.append($(document.createElement('thead')).append($headers));
            table.append($('<tbody></tbody>'));

            return table;
        }

        /**
         * Update result list when search model's results property was changed
         */
        _searchResults(results) {
            const currentRoute = this.getCurrentRoute();
            this.removeLastResults();
            if (currentRoute && 'table' === currentRoute.results.view) {
                const container = $('.search-results', this.$element);
                if ($('table', container).length === 0) {
                    this._prepareResultTable(container);
                }
                if (currentRoute.results.hasOwnProperty('sortBy') || currentRoute.hasOwnProperty('lastSortBy')) {
                    currentRoute.lastSortBy = (!currentRoute.hasOwnProperty('lastSortBy')) ? currentRoute.results.sortBy : currentRoute.lastSortBy;
                    if (!currentRoute.hasOwnProperty('lastSortOrder')) {
                        currentRoute.lastSortOrder = (currentRoute.results.hasOwnProperty('sortOrder')) ? currentRoute.results.sortOrder : 'asc';
                    }
                    let th = $('th[data-column="' + currentRoute.lastSortBy + '"', this.$element);
                    th.attr('data-order', currentRoute.lastSortOrder);
                    $('thead .sortIcon i', this.$element).attr('class', 'fa fa-sort');
                    th.find('.sortIcon i').attr('class', 'fa fa-sort-amount-' + currentRoute.lastSortOrder);
                    results = this.sortResults(results, currentRoute.lastSortBy, currentRoute.lastSortOrder);
                }
                this._searchResultsTable(results);
                if (results.length > 1 && currentRoute.results.hasOwnProperty('zoomToResultExtent') && currentRoute.results.zoomToResultExtent) {
                    let extent = this.highlightLayer.getNativeLayer().getSource().getExtent();
                    this.mbMap.map.olMap.getView().fit(extent, {
                        padding: [75, 75, 75, 75],
                    });
                }
            }
            this._showResultState(results);

            if (results.length === 1) {
                const options = currentRoute && currentRoute.results && currentRoute.results.callback
                    && currentRoute.results.callback.options;
                this._zoomToFeature(results[0], options);
            }
        }

        /**
         * Rebuilds result table with search result data.
         *
         * @param {Array} features
         */
        _searchResultsTable(features) {
            const currentRoute = this.getCurrentRoute();
            const headers = currentRoute.results.headers;
            const table = $('.search-results table', this.$element);
            const $tbody = $('tbody', table);

            $tbody.empty();
            this.removeLastResults();

            if (features.length > 0) $('.no-results', this.$element).hide();

            this.highlightLayer.addNativeFeatures(features);

            for (let i = 0; i < features.length; ++i) {
                const feature = features[i];
                const row = $('<tr/>');
                if (feature.get('highlighted') === true) {
                    row.addClass(this.trHighlightClass);
                }
                row.data('feature', feature);
                const props = Mapbender.mapEngine.getFeatureProperties(feature);
                Object.keys(headers).map((header) => {
                    const d = props[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                });

                $tbody.append(row);
                this._highlightFeature(feature, 'default');
            }
        }

        initTableEvents_() {
            const self = this;
            $('.search-results', this.$element)
                .on('click', 'tbody tr', function() {
                    const feature = $(this).data('feature');
                    self._highlightFeature(feature, 'select');
                    self._hideMobile();
                    self._highlightTableRow($(this));
                })
                .on('mouseenter', 'tbody tr', function() {
                    const feature = $(this).data('feature');
                    self._highlightFeature(feature, 'temporary');
                })
                .on('mouseleave', 'tbody tr', function() {
                    const feature = $(this).data('feature');
                    const styleName = feature === self.currentFeature ? 'select' : 'default';
                    self._highlightFeature(feature, styleName);
                });
        }

        _highlightFeature(feature, style) {
            if (style === 'select') {
                if (this.currentFeature && feature !== this.currentFeature) {
                    this._highlightFeature(this.currentFeature, 'default');
                }
                this.currentFeature = feature;
            }
            let featureStyle = this.featureStyles[style].clone();
            const label = this._getLabelValue(feature, style);
            featureStyle.getText().setText(label);
            feature.setStyle(featureStyle);
        }

        _showResultState(results) {
            const $element = this.$element;
            const $table = $('.search-results table', $element);
            let $counter = $('.result-counter', $element);
            let $exportcsv = $('.result-exportcsv', $element);

            const currentRoute = this.getCurrentRoute();

            if (!$counter.length) {
                $counter = $('<div/>', {'class': 'result-counter'}).prependTo($('.search-results', $element));
            }

            if (!$exportcsv.length && currentRoute.results.exportcsv === true) {
                $exportcsv = $('<button/>', {'class': 'btn btn-sm btn-default result-exportcsv fa fas fa-download left'})
                    .attr("title", Mapbender.trans('mb.core.searchrouter.exportcsv'))
                    .prependTo($('.search-results', $element));
            }

            if (results.length > 0) {
                if (currentRoute.results.count === true) {
                    $counter.text(Mapbender.trans('mb.core.searchrouter.result_counter', {
                        count: results.length
                    }));
                } else {
                    $counter.hide();
                }

                $exportcsv.show().unbind().on('click', () => this._exportCsv(results));
                $table.show();
            } else {
                $table.hide();
                $exportcsv.hide();
                $counter.text(Mapbender.trans('mb.core.searchrouter.no_results')).show();
            }
        }

        _exportCsv(features) {
            if (!this.csvExport) {
                this.csvExport = new CsvExport();
            }
            this.csvExport.export(features, this.getCurrentRoute().results.headers);
        }

        /**
         * read the feature map label from feature properties
         * @param feature
         * @param style
         * @returns {string}
         * @private
         */
        _getLabelValue(feature, style) {
            const currentRoute = this.getCurrentRoute();
            const labelWithRegex = currentRoute?.results?.styleMap?.[style]?.label;
            if (!labelWithRegex) return '';

            return this._labelReplaceRegex(labelWithRegex, feature);
        }

        _labelReplaceRegex(labelWithRegex, feature) {
            let regex = /\${([^}]+)}/g;
            let match = [];
            let label = labelWithRegex;

            while ((match = regex.exec(labelWithRegex)) !== null) {
                let featureValue = (feature.get(match[1])) ? feature.get(match[1]).toString() : '';
                label = label.replace(match[0], featureValue);
            }
            return label;
        }

        _createTextStyle(options) {
            const fontweight = options.fontWeight  || 'normal';
            const fontsize = options.fontSize  || '18px';
            const fontfamily = options.fontFamily  || 'arial';

            const textfill = new ol.style.Fill({
                color: Mapbender.StyleUtil.svgToCssColorRule(options, 'fontColor', '1')
            });
            const textstroke = new ol.style.Stroke({
                color: Mapbender.StyleUtil.svgToCssColorRule(options, 'labelOutlineColor', '1'),
                width: options.labelOutlineWidth || 2
            });

            return new ol.style.Text({
                font: fontweight + ' ' + fontsize + ' ' + fontfamily,
                offsetX: options.fontOffsetX || 2,
                offsetY: options.fontOffsetY || 2,
                placement: options.fontPlacement || 'point',
                text: options.label || '',
                fill: textfill,
                stroke: textstroke,
            });
        }

        _createSingleStyle(options) {
            const fill = new ol.style.Fill({
                color: Mapbender.StyleUtil.svgToCssColorRule(options, 'fillColor', 'fillOpacity')
            });
            const stroke = new ol.style.Stroke({
                color: Mapbender.StyleUtil.svgToCssColorRule(options, 'strokeColor', 'strokeOpacity'),
                width: options.strokeWidth || 2
            });

            return new ol.style.Style({
                image: new ol.style.Circle({
                    fill: fill,
                    stroke: stroke,
                    radius: options.pointRadius || 5
                }),
                fill: fill,
                stroke: stroke,
                text: this._createTextStyle(options),
            });
        }

        _createStyleMap (styles) {
            return {
                default: this._createSingleStyle(styles.default),
                select: this._createSingleStyle(styles.select),
                temporary: this._createSingleStyle(styles.temporary)
            }
        }

        /**
         * Get current route configuration
         *
         * @returns object route configuration
         */
        getCurrentRoute() {
            return this.selected && this.options.routes[this.selected] || null;
        }

        /**
         * Set up result callback (zoom on click for example)
         */
        _setupResultCallback() {
            const uniqueEventNames = [];
            for (let i = 0; i < this.options.routes.length; ++i) {
                const routeConfig = this.options.routes[i];
                const callbackConf = routeConfig.results && routeConfig.results.callback;
                const routeEventName = callbackConf && callbackConf.event;
                if (routeEventName && uniqueEventNames.indexOf(routeEventName) === -1) {
                    uniqueEventNames.push(routeEventName);
                }
            }
            if (uniqueEventNames.length) {
                const $anchor = $('.search-results', this.$element);
                $anchor.on(uniqueEventNames.join(' '), 'tbody tr', $.proxy(this._resultCallback, this));
            }
        }

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         */
        _resultCallback(event) {
            const currentRoute = this.getCurrentRoute();
            const callbackConf = currentRoute && currentRoute.results && currentRoute.results.callback;
            if (!callbackConf || event.type !== callbackConf.event) {
                return;
            }
            const row = $(event.currentTarget);
            const feature = row.data('feature');
            this._zoomToFeature(feature, callbackConf.options);
        }

        _zoomToFeature(feature, options) {
            let zoomToFeatureOptions;
            if (options) {
                zoomToFeatureOptions = {
                    maxScale: parseInt(options.maxScale) || null,
                    minScale: parseInt(options.minScale) || null,
                    buffer: parseInt(options.buffer) || null
                };
            }
            this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        }

        _onSrsChange(event, data) {
            if (this.highlightLayer) {
                this.highlightLayer.retransform(data.from, data.to);
            }
        }

        _getFormValues(form) {
            const values = {};
            $(':input', form).each(function(index, input) {
                const $input = $(input);
                const name = $input.attr('name').replace(/^[^[]*\[/, '').replace(/[\]].*$/, '');
                values[name] = $input.val();
            });
            return values;
        }

        _hideMobile() {
            $('.mobileClose', $(this.$element).closest('.mobilePane')).click();
        }

        onTableHeadClick(e) {
            let th = $(e.currentTarget);
            let currentRoute = this.getCurrentRoute();
            const features = this.highlightLayer.getNativeLayer().getSource().getFeatures();
            currentRoute.lastSortBy = th.attr('data-column');
            currentRoute.lastSortOrder = (th.attr('data-order') === 'asc') ? 'desc' : 'asc';
            th.attr('data-order', currentRoute.lastSortOrder);
            $('thead .sortIcon i', this.$element).attr('class', 'fa fa-sort');
            th.find('.sortIcon i').attr('class', 'fa fa-sort-amount-' + currentRoute.lastSortOrder);
            const results = this.sortResults(features, currentRoute.lastSortBy, currentRoute.lastSortOrder);
            this._searchResultsTable(results);
        }

        sortResults(results, sortBy, sortOrder = 'asc') {
            return results.sort((a, b) => {
                a = (a.get(sortBy)) ? a.get(sortBy) : '';
                b = (b.get(sortBy)) ? b.get(sortBy) : '';
                let result = a.toString().localeCompare(b.toString(), undefined, {
                    numeric: true,
                    sensivity: 'base'
                });
                if (result === 1) {
                    return (sortOrder === 'asc') ? 1 : -1;
                }
                if (result === -1) {
                    return (sortOrder === 'asc') ? -1 : 1;
                }
                return 0;
            });
        }

        _highlightTableRow(tr) {
            $('tbody tr', this.$element).removeClass(this.trHighlightClass);
            tr.addClass(this.trHighlightClass);
            this.highlightLayer.getNativeLayer().getSource().getFeatures().map(feature => feature.set('highlighted', false));
            tr.data('feature').set('highlighted', true);
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbSearchRouter = MbSearchRouter;
})();

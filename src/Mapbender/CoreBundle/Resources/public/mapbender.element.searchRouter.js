(function ($) {

    $.widget('mapbender.mbSearchRouter', $.mapbender.mbDialogElement, {
        options: {},
        callbackUrl: null,
        selected: null,
        highlightLayer: null,
        popup: null,
        mbMap: null,
        useDialog_: null,
        trHighlightClass: 'table-primary',

        _create: function () {
            var self = this;
            this.callbackUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.useDialog_ = this.checkDialogMode();
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function () {
                Mapbender.checkTarget('mbSearchRouter');
            });
        },


        /**
         * Remove last search results
         */
        removeLastResults: function () {
            if (this.highlightLayer) {
                this.highlightLayer.clear();
            }
            this.currentFeature = null;
        },
        _setup: function () {
            var widget = this;
            var element = widget.element;

            var routeSelect = $('select#search_routes_route', element);
            routeSelect.closest('.dropdown').css('display', this.options.routes.length > 1 ? 'block' : 'none');

            element.on('submit', '.search-forms form', function (evt) {
                evt.preventDefault();
                widget._search();
            });
            element.on('reset', '.search-forms form', function () {
                widget.removeLastResults();
            });
            // Prepare autocompletes
            $('form input[data-autocomplete="on"]', element).each(
                $.proxy(widget._setupAutocomplete, widget));
            $('form input[data-autocomplete^="custom:"]', element).each(
                $.proxy(widget._setupCustomAutocomplete, widget));

            // Listen to changes of search select (switching and forms resetting)
            routeSelect.on('change', $.proxy(this._selectSearch, this));
            element.on('click', '.search-action-buttons [data-action]', function () {
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
            this._trigger('ready');
            if (this.checkAutoOpen()) {
                this.open();
            }
            this.initTableEvents_();
            this._setupCsrf();
            routeSelect.trigger('change');
        },

        _setupCsrf: async function (forceRefresh = false) {
            try {
                const token = await Mapbender.ElementUtil.getCsrfToken(this, this.callbackUrl + "0/csrf", forceRefresh);
                this.element.find('input[name*="_token"]').attr('value', token);
            } catch (e) {
                if (!forceRefresh) Mapbender.error(Mapbender.trans(e.message));
            }
        },

        defaultAction: function (callback) {
            this.open(callback);
        },
        /**
         * Open popup dialog, when triggered by button; not in sidepane / mobile container
         */
        open: function (callback) {
            this.callback = callback ? callback : null;
            if (this.useDialog_) {
                if (!this.popup || !this.popup.$element) {
                    this.popup = new Mapbender.Popup({
                        title: this.element.attr('data-title'),
                        draggable: true,
                        modal: false,
                        closeOnESC: false,
                        content: this.element,
                        width: this.options.width ? this.options.width : 450,
                        resizable: true,
                        height: this.options.height ? this.options.height : 500,
                        detachOnClose: false,
                        buttons: [
                            {
                                label: Mapbender.trans("mb.actions.search"),
                                cssClass: 'btn btn-sm btn-primary',
                                callback: $.proxy(this._search, this)
                            },
                            {
                                label: Mapbender.trans('mb.actions.reset'),
                                cssClass: 'btn btn-sm btn-light',
                                callback: $.proxy(this._reset, this)
                            },
                            {
                                label: Mapbender.trans('mb.actions.close'),
                                cssClass: 'btn btn-sm btn-light popupClose'
                            }
                        ]
                    });
                    this.popup.$element.on('close', $.proxy(this.close, this));
                } else {
                    this.popup.$element.show();
                    this.popup.focus();
                }
            }
            this.notifyWidgetActivated();
        },

        /**
         * Closes popup dialog.
         */
        close: function () {
            if (this.popup && this.popup.$element) {
                this.popup.$element.hide();
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
            this.notifyWidgetDeactivated();
        },

        /**
         * Set up result table when a search was selected.
         *
         * @param  jqEvent event Change event
         */
        _selectSearch: function (event) {
            var selected = this.selected = $(event.target).val();

            $('form', this.element).each(function () {
                var form = $(this);
                if (form.attr('name') === selected) {
                    form.show();
                } else {
                    form.hide();
                }
                form.get(0).reset();
            });

            $('.search-results', this.element).empty();
            var route = this.getCurrentRoute();
            this.featureStyles = this._createStyleMap(route.results.styleMap);
        },

        /**
         * Reset current search form
         */
        _reset: function () {
            $('select#search_routes_route', this.element).change();
            this.currentFeature = null;
        },

        /**
         * Set up autocomplete widgets for all inputs with data-autcomplete="on"
         *
         * @param {*} idx
         * @param {Node} input
         */
        _setupAutocomplete: function (idx, input) {
            var self = this;
            var $input = $(input);
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
                source: function (request, response) {
                    self._autocompleteSource($input).then(function (data) {
                        response(data.results);
                    }, function () {
                        response([]);
                    });
                }
            });
        },

        /**
         * Set up autocpmplete provided by custom widget (data-autcomplete="custom:<widget>")
         *
         * @param  {integer}      idx   Running index
         * @param  {HTMLElement}  input Input element
         */
        _setupCustomAutocomplete: function (idx, input) {
            var plugin = $(input).data('autocomplete').substring(7);
            $(input)[plugin]();
        },

        /**
         * Generate autocomplete request
         *
         * @param {jQuery} $input
         */
        _autocompleteSource: function ($input) {
            var url = this.callbackUrl + this.selected + '/autocomplete';
            var formValues = this._getFormValues($input.closest('form'));
            var data = {
                key: $input.attr('name').replace(/^[^[]*\[/, '').replace(/[\]].*$/, ''),
                value: $input.val(),
                srs: this.mbMap.model.getCurrentProjectionCode(),
                extent: this.mbMap.model.getMaxExtentArray(),
                properties: formValues
            };
            return $.getJSON({
                url: url,
                data: JSON.stringify(data),
                method: 'POST',
            }).fail((err) => Mapbender.handleAjaxError(err, async () => {
                try {
                    await this._setupCsrf(true);
                } catch {
                }
                this._autocompleteSource($input);
            }));
        },

        /**
         * @param $form jQuery
         * @return {boolean}
         * @private
         */
        _validateForm: function ($form) {
            const form = $form.get(0);
            if (form.reportValidity && !form.reportValidity()) return false;

            let valid = true;
            $form.find(':input[required]').each((index, el) => {
                if ($(el).val() === '') valid = false;
            });
            return valid;
        },

        _prepareSearchRequestData: function (formValues) {
            return {
                properties: formValues,
                extent: this.mbMap.model.getMaxExtentArray(),
                srs: this.mbMap.model.getCurrentProjectionCode()
            };
        },

        _createFeaturesFromResponse: function (response) {
            return response.features.map((data) => {
                const gjInput = {
                    type: 'Feature',
                    geometry: data.geometry,
                    properties: data.properties || {}
                };
                return this.mbMap.model.parseGeoJsonFeature(gjInput);
            });
        },

        /**
         * Start a search, but only after successful form validation
         */
        _search: function () {
            const $form = this.element.find('form[name="' + this.selected + '"]');
            if (!this._validateForm($form)) return;

            const formValues = this._getFormValues($form);
            const data = this._prepareSearchRequestData(formValues);
            const url = this.callbackUrl + this.selected + '/search';

            $.getJSON({
                url: url,
                data: JSON.stringify(data),
                method: 'POST'
            })
                .fail((err) => Mapbender.handleAjaxError(err, async () => {
                    try {
                        await this._setupCsrf(true);
                    } catch {
                    }
                    this._search();
                }))
                .then((response) => {
                    const features = this._createFeaturesFromResponse(response);
                    this._searchResults(features);
                });
        },

        /**
         * Prepare search result table
         */
        _prepareResultTable: function (container) {
            var currentRoute = this.getCurrentRoute();
            if (currentRoute && currentRoute.results.headers) {
                container.append(this.renderTable(currentRoute));
            }
        },
        /**
         * @param {Object} routeConfig
         * @returns {HTMLElement|jQuery}
         */
        renderTable: function (routeConfig) {
            const self = this;
            var headers = routeConfig.results.headers;
            var $headers = $(document.createElement('tr'));
            var table = $(document.createElement('table')).addClass('table table-condensed table-striped table-hover');

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
        },

        /**
         * Update result list when search model's results property was changed
         */
        _searchResults: function (results) {
            var currentRoute = this.getCurrentRoute();
            this.removeLastResults();
            if (currentRoute && 'table' === currentRoute.results.view) {
                var container = $('.search-results', this.element);
                if ($('table', container).length === 0) {
                    this._prepareResultTable(container);
                }
                if (currentRoute.results.hasOwnProperty('sortBy') || currentRoute.hasOwnProperty('lastSortBy')) {
                    currentRoute.lastSortBy = (!currentRoute.hasOwnProperty('lastSortBy')) ? currentRoute.results.sortBy : currentRoute.lastSortBy;
                    if (!currentRoute.hasOwnProperty('lastSortOrder')) {
                        currentRoute.lastSortOrder = (currentRoute.results.hasOwnProperty('sortOrder')) ? currentRoute.results.sortOrder : 'asc';
                    }
                    let th = $('th[data-column="' + currentRoute.lastSortBy + '"', this.element);
                    th.attr('data-order', currentRoute.lastSortOrder);
                    $('thead .sortIcon i', this.element).attr('class', 'fa fa-sort');
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
        },

        /**
         * Rebuilds result table with search result data.
         *
         * @param {Array} features
         */
        _searchResultsTable: function (features) {
            var currentRoute = this.getCurrentRoute();
            var headers = currentRoute.results.headers,
                table = $('.search-results table', this.element),
                $tbody = $('tbody', table)
            ;

            $tbody.empty();
            this.removeLastResults();

            if (features.length > 0) $('.no-results', this.element).hide();

            this.highlightLayer.addNativeFeatures(features);

            for (var i = 0; i < features.length; ++i) {
                var feature = features[i];
                var row = $('<tr/>');
                if (feature.get('highlighted') === true) {
                    row.addClass(this.trHighlightClass);
                }
                row.data('feature', feature);
                var props = Mapbender.mapEngine.getFeatureProperties(feature);
                Object.keys(headers).map(function (header) {
                    var d = props[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                });

                $tbody.append(row);
                this._highlightFeature(feature, 'default');
            }
        },
        initTableEvents_: function () {
            var self = this;
            $('.search-results', this.element)
                .on('click', 'tbody tr', function () {
                    var feature = $(this).data('feature');
                    self._highlightFeature(feature, 'select');
                    self._hideMobile();
                    self._highlightTableRow($(this));
                })
                .on('mouseenter', 'tbody tr', function () {
                    var feature = $(this).data('feature');
                    self._highlightFeature(feature, 'temporary');
                })
                .on('mouseleave', 'tbody tr', function () {
                    var feature = $(this).data('feature');
                    var styleName = feature === self.currentFeature ? 'select' : 'default';
                    self._highlightFeature(feature, styleName);
                })
            ;
        },
        _highlightFeature: function (feature, style) {
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
        },
        _showResultState: function (results) {
            var element = this.element;
            const $table = $('.search-results table', element);
            let $counter = $('.result-counter', element);
            let $exportcsv = $('.result-exportcsv', element);

            var currentRoute = this.getCurrentRoute();

            if (!$counter.length) {
                $counter = $('<div/>', {'class': 'result-counter'}).prependTo($('.search-results', element));
            }

            if (!$exportcsv.length && currentRoute.results.exportcsv === true) {
                $exportcsv = $('<button/>', {'class': 'btn btn-sm btn-default result-exportcsv fa fas fa-download left'})
                    .attr("title", Mapbender.trans('mb.core.searchrouter.exportcsv'))
                    .prependTo($('.search-results', element));
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
        },
        _exportCsv: function (features) {
            if (!this.csvExport) {
                this.csvExport = new CsvExport();
            }
            this.csvExport.export(features, this.getCurrentRoute().results.headers);
        },

        /**
         * read the feature map label from feature properties
         * @param feature
         * @param style
         * @returns {string}
         * @private
         */
        _getLabelValue: function (feature, style) {
            const currentRoute = this.getCurrentRoute();
            const labelWithRegex = currentRoute?.results?.styleMap?.[style]?.label;
            if (!labelWithRegex) return '';

            return this._labelReplaceRegex(labelWithRegex, feature);
        },

        _labelReplaceRegex: function (labelWithRegex, feature) {
            let regex = /\${([^}]+)}/g;
            let match = [];
            let label = labelWithRegex;

            while ((match = regex.exec(labelWithRegex)) !== null) {
                let featureValue = (feature.get(match[1])) ? feature.get(match[1]).toString() : '';
                label = label.replace(match[0], featureValue);
            }
            return label;
        },

        _createTextStyle: function (options) {
            const fontweight = options.fontWeight || 'normal';
            const fontsize = options.fontSize || '18px';
            const fontfamily = options.fontFamily || 'arial';

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
        },

        _createSingleStyle: function (options) {
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
        },
        _createStyleMap: function (styles) {
            return {
                default: this._createSingleStyle(styles.default),
                select: this._createSingleStyle(styles.select),
                temporary: this._createSingleStyle(styles.temporary)
            }
        },

        /**
         * Get current route configuration
         *
         * @returns object route configuration
         */
        getCurrentRoute: function () {
            return this.selected && this.options.routes[this.selected] || null;
        },

        /**
         * Set up result callback (zoom on click for example)
         */
        _setupResultCallback: function () {
            var uniqueEventNames = [];
            for (var i = 0; i < this.options.routes.length; ++i) {
                var routeConfig = this.options.routes[i];
                var callbackConf = routeConfig.results && routeConfig.results.callback;
                var routeEventName = callbackConf && callbackConf.event;
                if (routeEventName && uniqueEventNames.indexOf(routeEventName) === -1) {
                    uniqueEventNames.push(routeEventName);
                }
            }
            if (uniqueEventNames.length) {
                var $anchor = $('.search-results', this.element);
                $anchor.on(uniqueEventNames.join(' '), 'tbody tr', $.proxy(this._resultCallback, this));
            }
        },

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         */
        _resultCallback: function (event) {
            var currentRoute = this.getCurrentRoute();
            var callbackConf = currentRoute && currentRoute.results && currentRoute.results.callback;
            if (!callbackConf || event.type !== callbackConf.event) {
                return;
            }
            const row = $(event.currentTarget);
            const feature = row.data('feature');
            this._zoomToFeature(feature, callbackConf.options)
        },
        _zoomToFeature: function (feature, options) {
            let zoomToFeatureOptions;
            if (options) {
                zoomToFeatureOptions = {
                    maxScale: parseInt(options.maxScale) || null,
                    minScale: parseInt(options.minScale) || null,
                    buffer: parseInt(options.buffer) || null
                };
            }
            this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        },
        _onSrsChange: function (event, data) {
            if (this.highlightLayer) {
                this.highlightLayer.retransform(data.from, data.to);
            }
        },
        _getFormValues: function (form) {
            var values = {};
            $(':input', form).each(function (index, input) {
                var $input = $(input);
                var name = $input.attr('name').replace(/^[^[]*\[/, '').replace(/[\]].*$/, '');
                values[name] = $input.val();
            });
            return values;
        },

        _hideMobile: function () {
            $('.mobileClose', $(this.element).closest('.mobilePane')).click();
        },

        onTableHeadClick: function (e) {
            let th = $(e.currentTarget);
            let currentRoute = this.getCurrentRoute();
            const features = this.highlightLayer.getNativeLayer().getSource().getFeatures();
            currentRoute.lastSortBy = th.attr('data-column');
            currentRoute.lastSortOrder = (th.attr('data-order') === 'asc') ? 'desc' : 'asc';
            th.attr('data-order', currentRoute.lastSortOrder);
            $('thead .sortIcon i', this.element).attr('class', 'fa fa-sort');
            th.find('.sortIcon i').attr('class', 'fa fa-sort-amount-' + currentRoute.lastSortOrder);
            const results = this.sortResults(features, currentRoute.lastSortBy, currentRoute.lastSortOrder);
            this._searchResultsTable(results);
        },

        sortResults: function (results, sortBy, sortOrder = 'asc') {
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
        },

        _highlightTableRow: function (tr) {
            $('tbody tr', this.element).removeClass(this.trHighlightClass);
            tr.addClass(this.trHighlightClass);
            this.highlightLayer.getNativeLayer().getSource().getFeatures().map(feature => feature.set('highlighted', false));
            tr.data('feature').set('highlighted', true);
        },

        __dummy__: null
    });
})(jQuery);

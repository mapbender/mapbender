(function($){

    $.widget('mapbender.mbSearchRouter', {
        options: {
            asDialog: true,     // Display as jQuery UI dialog
            timeoutFactor: 2    // use delay * timeoutFactor before showing
        },
        callbackUrl: null,
        selected: null,
        highlightLayer: null,
        lastSearch: new Date(),
        searchModel: null,
        autocompleteModel: null,
        popup: null,
        mbMap: null,

        /**
         * Widget creator
         */
        _create: function(){
            var widget = this;
            var options = widget.options;

            if(!Mapbender.checkTarget("mbSearchRouter", options.target)){
                return;
            }
            Mapbender.elementRegistry.onElementReady(options.target, $.proxy(widget._setup, widget));
            widget.callbackUrl = Mapbender.configuration.application.urls.element + '/' + widget.element.attr('id') + '/';
        },

        /**
         * Remove last search results
         */
        removeLastResults: function(){
            var widget = this;
            widget.searchModel.reset();
            widget._getLayer().removeAllFeatures();
            this.currentFeature = null;
        },

        _setup:         function(){
            var widget = this;
            var element = widget.element;
            var options = widget.options;
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');

            var searchModelAttributes = {
                srs: this.mbMap.getModel().getCurrentProj().projCode
            };
            this.searchModel = new Mapbender.SearchModel(searchModelAttributes, null, this);
            var routeSelect = $('select#search_routes_route', element);
            var routeCount = 0;

            // bind form reset to reset search model
            element.delegate('.search-forms form', 'reset', function(){
                widget.removeLastResults();
            });
            // bind form submit to send search model
            element.delegate('.search-forms form', 'submit', function(evt){
                widget.removeLastResults();
                widget.searchModel.submit(evt);
            });

            // bind result to result list and map view
            this.searchModel.on('change:results', widget._searchResults, widget);
            this.searchModel.on('request', widget._setActive, widget);
            this.searchModel.on('error sync', widget._setInactive, widget);
            this.searchModel.on('error sync', widget._showResultState, widget);

            // Prepare autocompletes
            $('form input[data-autocomplete="on"]', element).each(
                $.proxy(widget._setupAutocomplete, widget));
            $('form input[data-autocomplete^="custom:"]', element).each(
                $.proxy(widget._setupCustomAutocomplete, widget));

            // Prepare search button (trigger form submit)
            $('a[role="search_router_search"]', element)
                .button()
                .click(function(){
                    widget.getCurrentForm().submit();
                });

            // Prevent map getting cursors keys
            element.bind('keydown', function(event){
                event.stopPropagation();
            });

            // Listen to changes of search select (switching and forms resetting)

            routeSelect.change($.proxy(widget._selectSearch, widget));
            Mapbender.elementRegistry.onElementReady(options.target, function(){
                routeSelect.change();
                widget._trigger('ready');
            });
            // But if there's only one search, we actually don't need the select
            for(var route in options.routes){
                if(options.routes.hasOwnProperty(route)){
                    routeCount++;
                }
            }
            if(routeCount === 1){
                $('#search_routes_route_control_group').hide()
                    .next('hr').hide();
            }

            if(!options.asDialog) {
                element.on('click', '.search-action-buttons a', function(event) {
                    event.preventDefault();
                    var target = $(event.target).attr('href');
                    var targetBase = '#' + widget.element.attr('id') + '/button/';
                    switch(target) {
                        case (targetBase + 'reset'):
                            widget._reset();
                            break;
                        case (targetBase + 'ok'):
                            widget._search();
                            break;
                    }
                });
            }

            $(document).on('mbmapsrschanged', this._onSrsChange.bind(this));
            this._setupResultCallback();
            widget._trigger('ready');

            if(widget.options.autoOpen) {
                widget.open();
            }
        },

        defaultAction: function(callback){
            this.open(callback);
        },

        /**
         * Open method stub. Calls dialog's open method if widget is configured as
         * an dialog (asDialog: true), otherwise just goes on and does nothing.
         */
        open: function(callback){
            this.callback = callback ? callback : null;
            if(true === this.options.asDialog) {
                if(!this.popup || !this.popup.$element){
                    this.popup = new Mapbender.Popup2({
                        title: this.element.attr('title'),
                        draggable: true,
                        modal: false,
                        closeOnESC: false,
                        content: this.element,
                        width: this.options.width ? this.options.width : 450,
                        resizable: true,
                        height: this.options.height ? this.options.height : 500,
                        buttons: {
                            'cancel': {
                                label: Mapbender.trans('mb.core.searchrouter.popup.btn.cancel'),
                                cssClass: 'button buttonCancel critical right',
                                callback: $.proxy(this.close, this)
                            },
                            'reset': {
                                label: Mapbender.trans('mb.core.searchrouter.popup.btn.reset'),
                                cssClass: 'button right',
                                callback: $.proxy(this._reset, this)
                            },
                            'ok': {
                                label: Mapbender.trans("mb.core.searchrouter.popup.btn.ok"),
                                cssClass: 'button right',
                                callback: $.proxy(this._search, this)
                            }
                        }
                    });
                    this.popup.$element.on('close', $.proxy(this.close, this));
                }else{

                }
                this.element.show();
            }
        },

        /**
         * Close method stub. Calls dialog's close method if widget is configured
         * as an dialog (asDialog: true), otherwise just goes on and does nothing.
         */
        close: function(){
            if(true === this.options.asDialog){
                if(this.popup){
                    if(this.popup.$element){
                        this.element.hide().appendTo($('body'));
                        this.popup.destroy();
                    }
                    this.popup = null;
                }
            }
            this.callback ? this.callback.call() : this.callback = null;
        },

        /**
         * Set up result table when a search was selected.
         *
         * @param  jqEvent event Change event
         */
        _selectSearch: function(event){
            var selected = this.selected = $(event.target).val();

            $('form', this.element).each(function(){
                var form = $(this);
                if(form.attr('name') === selected) {
                    form.show();
                }else{
                    form.hide();
                }
                form.get(0).reset();
            });

            $('.search-results', this.element).empty();
        },

        /**
         * Reset current search form
         */
        _reset: function() {
            $('select#search_routes_route', this.element).change();
            this.currentFeature = null;
        },

        /**
         * Set up autocomplete widgets for all inputs with data-autcomplete="on"
         *
         * @param  integer      idx   Running index
         * @param  HTMLDomNode  input Input element
         */
        _setupAutocomplete: function(idx, input){
            var widget = this;
            input = $(input);
            var ac = input.autocomplete({
                delay: input.data('autocomplete-delay') || 500,
                minLength: input.data('autocomplete-minlength') || 3,
                search: $.proxy(widget._autocompleteSearch, widget),
                open: function( event, ui, t) {
                    $(event.target).data("uiAutocomplete").menu.element.outerWidth(input.outerWidth());
                },
                source: function(request, response){
                    widget._autocompleteSource(input, request, response);
                },
                select: widget._autocompleteSelect
            }).keydown(widget._autocompleteKeydown);
        },

        /**
         * Set up autocpmplete provided by custom widget (data-autcomplete="custom:<widget>")
         *
         * @param  integer      idx   Running index
         * @param  HTMLDomNode  input Input element
         */
        _setupCustomAutocomplete: function(idx, input){
            var plugin = $(input).data('autocomplete').substr(7);
            $(input)[plugin]();
        },

        /**
         * Autocomplete source handler, does all Ajax magic.
         *
         * @param  HTMLDomNode target   Input element
         * @param  Object      request  Request object with term attribute
         * @param  function    response Autocomplete callback
         */
        _autocompleteSource: function(target, request, response){
            if(!target.data('autocompleteModel')){
                var model = new Mapbender.AutocompleteModel(null, {
                    router: this
                });
                target.data('autocompleteModel', model);

                model.on('request', this._setActive, this);
                model.on('sync', function(){
                    model.response(model.get('results'));
                });
                model.on('error', response([]));
            }

            target.data('autocompleteModel').response = response;
            target.data('autocompleteModel').submit(target, request);
        },

        /**
         * Store autocomplete key if suggestion was selected.
         *
         * @param  jQuery.Event event Selection event
         * @param  Object       ul    Selected item
         */
        _autocompleteSelect: function(event, ui){
            if(typeof ui.item.key !== 'undefined'){
                $(event.target).attr('data-autocomplete-key', ui.item.key);
            }
        },

        /**
         * Remove stored autocomplete key when key was pressed.
         *
         * @param  jQuery.Event event Keydown event
         */
        _autocompleteKeydown: function(event){
            $(event.target).removeAttr('data-autocomplete-key');
        },

        /**
         * Autocomplete search handler.
         *
         * Checks if enough time has been passed since the last search. Basically
         * this prevents an autocomplete poping up when a search is triggered by
         * keyboard.
         *
         * @param  jQuery.Event event search event
         * @param  Object       ui    n/a
         */
        _autocompleteSearch: function(event, ui,t){
            var input = $(event.target);
            var autoCompleteMenu = $(input.data("uiAutocomplete").menu.element);
            var delay = input.autocomplete('option', 'delay'),
                diff = (new Date()) - this.lastSearch;

            autoCompleteMenu.addClass("search-router");

            if(diff <= delay * this.options.timeoutFactor){
                event.preventDefault();
            }
        },

        /**
         * Start a search, but only after successful form validation
         */
        _search: function() {
            var form = $('form[name="' + this.selected + '"]', this.element);
            var valid = true;
            $.each($(':input[required]', form), function() {
                if('' === $(this).val()) {
                    valid = false;
                }
            });

            if(valid) {
                form.submit();
            }
        },

        /**
         * Prepare search result table
         */
        _prepareResultTable: function(container){
            var currentRoute = this.getCurrentRoute();
            if (!currentRoute || typeof currentRoute.results.headers === 'undefined'){
                return;
            }

            var headers = currentRoute.results.headers;

            var table = $('<table></table>'),
                thead = $('<thead><tr></tr></thead>').appendTo(table);

            for(var header in headers){
                thead.append($('<th>' + headers[header] + '</th>'));
            }

            table.append($('<tbody></tbody>'));

            container.append(table);
        },

        /**
         * Update result list when search model's results property was changed
         */
        _searchResults: function(model, results, options){
            var currentRoute = this.getCurrentRoute();
            if (currentRoute && 'table' === currentRoute.results.view) {
                var container = $('.search-results', this.element);
                if($('table', container).length === 0) {
                    this._prepareResultTable(container);
                }
                this._searchResultsTable(model, results, options);
            }
        },

        /**
         * Rebuilds result table with search result data.
         *
         * @param {SearchModel} model
         * @param {FeatureCollection} results
         * @param {Object} options Backbone options (not used?)
         */
        _searchResultsTable: function(model, results, options){
            var currentRoute = this.getCurrentRoute();
            var headers = currentRoute.results.headers,
                table = $('.search-results table', this.element),
                tbody = $('<tbody></tbody>'),
                layer = this._getLayer(true),
                self = this;

            $('tbody', table).remove();
            layer.removeAllFeatures();
            var features = [];

            if(results.length > 0) $('.no-results', this.element).hide();

            results.each(function(feature, idx) {
                var row = $('<tr/>');
                row.addClass(idx % 2 ? "even" : "odd");
                row.data('feature', feature);

                for (var header in headers) {
                    var d = feature.get('properties')[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                }

                tbody.append(row);

                features.push(feature.getFeature());
            });

            table.append(tbody);
            layer.addFeatures(features);

            $('.search-results tbody tr')
                .on('click', function () {
                    var feature = $(this).data('feature').getFeature();
                    self._highlightFeature(feature, 'select');
                    self._hideMobile();
                })
                .on('mouseenter', function () {
                    var feature = $(this).data('feature').getFeature();

                    if(feature.renderIntent !== 'select') {
                        self._highlightFeature(feature, 'temporary');
                    }
                })
                .on('mouseleave', function () {
                    var feature = $(this).data('feature').getFeature();

                    if(feature.renderIntent !== 'select') {
                        self._highlightFeature(feature, 'default');
                    }
                })
            ;
        },

        _highlightFeature: function (feature, style) {
            if (style === 'select') {
                if (this.currentFeature) {
                    this._highlightFeature(this.currentFeature, 'default');
                }
                this.currentFeature = feature;
            }
            if (feature.layer) {
                feature.layer.drawFeature(feature, style);
            }
        },

        _showResultState: function() {
            var widget = this;
            var element = widget.element;
            var table = $('.search-results table', element);
            var counter = $('.result-counter', element);

            if(0 === counter.length) {
                counter = $('<div/>', {'class': 'result-counter'})
                  .prependTo($('.search-results', element));
            }

            var results = widget.searchModel.get('results');

            if(results.length > 0) {
                counter.text(Mapbender.trans('mb.core.searchrouter.result_counter', {
                    count: results.length
                }));
                table.show();
            } else {
                table.hide();
                counter.text(Mapbender.trans('mb.core.searchrouter.no_results'));
            }
        },

        /**
         * Add active class to widget for styling when Ajax is running
         */
        _setActive: function(){
            var outer = this.options.asDialog ? this.element.parent() : this.element;
            outer.addClass('search-active');
        },

        /**
         * Remove active class from widget
         */
        _setInactive: function(){
            var outer = this.options.asDialog ? this.element.parent() : this.element;
            outer.removeClass('search-active');
        },

        _createStyleMap: function(styles, options) {
            var o = _.defaults({}, options, {
                extendDefault: true,
                defaultBase: OpenLayers.Feature.Vector.style['default']
            });
            var s = styles || OpenLayers.Feature.Vector.style;

            _.defaults(s['default'], o.defaultBase);

            return new OpenLayers.StyleMap(s, {
                extendDefault: o.extendDefault
            });
        },

        /**
         * Get current route configuration
         *
         * @returns object route configuration
         */
        getCurrentRoute: function() {
            return this.selected && this.options.routes[this.selected] || null;
        },

        /**
         * Get highlight layer. Will construct one if neccessary.
         *
         * @return OpenLayers.Layer.Vector Highlight layer
         */
        _getLayer: function(forceRebuild) {
            var widget = this;
            var map = this.mbMap.map.olMap;
            var layer = widget.highlightLayer;

            if(!forceRebuild && layer) {
                return layer;
            }

            if(forceRebuild && layer) {
                map.removeLayer(layer);
                widget.highlightLayer = null;
            }

            var route = widget.getCurrentRoute();
            var styleMap = widget._createStyleMap(route.results.styleMap);
            layer = widget.highlightLayer = new OpenLayers.Layer.Vector('Search Highlight', {
                styleMap: styleMap
            });
            map.addLayer(layer);

            return layer;
        },

        /**
         * Set up result callback (zoom on click for example)
         */
        _setupResultCallback: function(){
            var routeNames = Object.keys(this.options.routes);
            var uniqueEventNames = [];
            for (var i = 0; i < routeNames.length; ++i) {
                var routeConfig = this.options.routes[routeNames[i]];
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
        _resultCallback: function(event) {
            var currentRoute = this.getCurrentRoute();
            var callbackConf = currentRoute && currentRoute.results && currentRoute.results.callback;
            if (!callbackConf || event.type !== callbackConf.event) {
                return;
            }
            var row = $(event.currentTarget),
                feature = row.data('feature').getFeature()
            ;
            var zoomToFeatureOptions;
            if (callbackConf.options) {
                zoomToFeatureOptions = {
                    maxScale: parseInt(callbackConf.options.maxScale) || null,
                    minScale: parseInt(callbackConf.options.minScale) || null,
                    buffer: parseInt(callbackConf.options.buffer) || null
                };
            }
            this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
        },
        _onSrsChange: function(event, data) {
            if (this.highlightLayer) {
                (this.highlightLayer.features || []).map(function(feature) {
                    if (feature.geometry && feature.geometry.transform) {
                        feature.geometry.transform(data.from, data.to);
                    }
                });
                this.highlightLayer.redraw();
            }
            if (this.searchModel && this.mbMap) {
                this.searchModel.set({
                    srs: data.to.projCode
                });
            }
        },

        _hideMobile: function() {
            $('.mobileClose', $(this.element).closest('.mobilePane')).click();
        },

        /**
         * Get current form
         *
         * @returns {*|HTMLElement}
         */
        getCurrentForm: function() {
            var widget = this;
            return $('form[name="' + widget.selected + '"]', widget.element);
        }
    });
})(jQuery);

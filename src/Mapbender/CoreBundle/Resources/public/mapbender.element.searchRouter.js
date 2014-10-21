(function($){

    $.widget('mapbender.mbSearchRouter', {
        options: {
            asDialog: true,     // Display as jQuery UI dialog
            timeoutFactor: 2    // use delay * timeoutFactor before showing
                                // autocomplete again after a search has been
                                // started
        },

        callbackUrl: null,
        selected: null,
        highlightLayer: null,
        lastSearch: new Date(),
        resultCallbackEvent: null,
        resultCallbackProxy: null,
        searchModel: null,
        autocompleteModel: null,
        popup: null,

        /**
         * Widget creator
         */
        _create: function(){
            if(!Mapbender.checkTarget("mbSearchRouter", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function(){
            var self = this;

            // Prepare callback URL and Search Model
            this.callbackUrl = Mapbender.configuration.application.urls.element +
                '/' + this.element.attr('id') + '/';
            this.searchModel = new Mapbender.SearchModel(null, null, this);

            // bind form reset to reset search model
            this.element.delegate('.search-forms form', 'reset', function(){
                self.searchModel.reset();
                self._getLayer().removeAllFeatures();
            });
            // bind form submit to send search model
            this.element.delegate('.search-forms form', 'submit', function(evt){
                self.searchModel.submit(evt);
            });
            // bind result to result list and map view
            this.searchModel.on('change:results', this._searchResults, this);

            this.searchModel.on('request', this._setActive, this);
            this.searchModel.on('error sync', this._setInactive, this);
            this.searchModel.on('error sync', this._showResultState, this);

            this.resultCallbackProxy = $.proxy(this._resultCallback, this);

            // Prepare autocompletes
            $('form input[data-autocomplete="on"]', this.element).each(
                $.proxy(this._setupAutocomplete, this));
            $('form input[data-autocomplete^="custom:"]', this.element).each(
                $.proxy(this._setupCustomAutocomplete, this));

            // Prepare search button (trigger form submit)
            $('a[role="search_router_search"]')
                .button()
                .click(function(){
                $('form[name="' + self.selected + '"]', self.element).submit();
            });

            // Prevent map getting cursors keys
            this.element.bind('keydown', function(event){
                event.stopPropagation();
            });

            // Listen to changes of search select (switching and forms resetting)
            var routeSelect = $('select#search_routes_route', this.element);
            routeSelect.change($.proxy(this._selectSearch, this));
            Mapbender.elementRegistry.onElementReady(this.options.target, function(){
                routeSelect.change();
                self._trigger('ready');
            });
            // But if there's only one search, we actually don't need the select
            var routeCount = 0;
            for(route in this.options.routes){
                if(this.options.routes.hasOwnProperty(route)){
                    routeCount++;
                }
            }
            if(routeCount === 1){
                $('#search_routes_route_control_group').hide()
                    .next('hr').hide();
            }

            if(!this.options.asDialog) {
                this.element.on('click', '.search-action-buttons a', function(event) {
                    event.preventDefault();
                    var target = $(event.target).attr('href');
                    var targetBase = '#' + self.element.attr('id') + '/button/';
                    switch(target) {
                        case (targetBase + 'reset'):
                            self._reset();
                            break;
                        case (targetBase + 'ok'):
                            self._search();
                            break;
                    }
                });
            }

            this._trigger('ready');
            this._ready();

            if(this.options.autoOpen) {
                this.open();
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
                        closeButton: true,
                        closeOnESC: false,
                        content: this.element,
                        width: 450,
                        resizable: true,
                        height: 500,
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
                if(form.attr('name') == selected){
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
        },

        /**
         * Set up autocomplete widgets for all inputs with data-autcomplete="on"
         *
         * @param  integer      idx   Running index
         * @param  HTMLDomNode  input Input element
         */
        _setupAutocomplete: function(idx, input){
            var self = this;
            input = $(input);
            input.autocomplete({
                delay: input.data('autocomplete-delay') || 500,
                minLength: input.data('autocomplete-minlength') || 3,
                search: $.proxy(this._autocompleteSearch, this),
                open: function( event, ui ) {
                    $(".ui-autocomplete").outerWidth(input.outerWidth());
                },
                source: function(request, response){
                    self._autocompleteSource(input, request, response);
                },
                select: this._autocompleteSelect
            }).keydown(this._autocompleteKeydown);
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
        _autocompleteSearch: function(event, ui){
            var delay = $(event.target).autocomplete('option', 'delay'),
                diff = (new Date()) - this.lastSearch;

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
            if(typeof this.options.routes[this.selected].results.headers === 'undefined'){
                return;
            }

            var headers = this.options.routes[this.selected].results.headers;

            var table = $('<table></table>'),
                thead = $('<thead><tr></tr></thead>').appendTo(table);

            for(var header in headers){
                thead.append($('<th>' + headers[header] + '</th>'));
            }

            table.append($('<tbody></tbody>'));

            container.append(table);

            this._setupResultCallback();
        },

        /**
         * Update result list when search model's results property was changed
         */
        _searchResults: function(model, results, options){
            if('table' === this.options.routes[this.selected].results.view) {
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
         * @param SearchModel       model   Search model
         * @param FeatureCollection results Search result feature collection
         * @param object            options Backbone options
         */
        _searchResultsTable: function(model, results, options){
            var headers = this.options.routes[this.selected].results.headers,
                table = $('.search-results table', this.element),
                tbody = $('<tbody></tbody>'),
                layer = this._getLayer(true);

            $('tbody', table).remove();
            layer.removeAllFeatures();
            features = [];

            if(results.length > 0) $('.no-results', this.element).hide();

            results.each(function(feature, idx){
                var row = $('<tr></tr>');
                row.data('feature', feature);
                for(var header in headers){
                    d = feature.get('properties')[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                }
                tbody.append(row);

                features.push(feature.getFeature());
            });

            table.append(tbody);
            layer.addFeatures(features);
        },

        _showResultState: function() {
            var table = $('.search-results table', this.element);
            var counter = $('.result-counter', this.element);

            if(0 === counter.length) {
                counter = $('<div></div>', {'class': 'result-counter'}).prependTo($('.search-results', this.element));
            }

            var results = this.searchModel.get('results');

            if(results.length > 0) {
                counter.text(Mapbender.trans('mb.core.searchrouter.result_counter', {
                    count: results.length}));
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
         * Get highlight layer. Will construct one if neccessary.
         * @TODO: Backbonify (view)
         *
         * @return OpenLayers.Layer.Vector Highlight layer
         */
        _getLayer: function(forceRebuild){
            if(this.highlightLayer === null || forceRebuild){
                this.highlightLayer = new OpenLayers.Layer.Vector('Search Highlight', {
                    styleMap: this._createStyleMap(this.options.routes[this.selected].results.styleMap)
                });
            }

            if(this.highlightLayer.map === null){
                var map = $('#' + this.options.target).data('mapbenderMbMap').map.olMap;
                map.addLayer(this.highlightLayer);
            }

            return this.highlightLayer;
        },

        /**
         * Set up result callback (zoom on click for example)
         */
        _setupResultCallback: function(){
            var anchor = $('.search-results', this.element);
            if(this.resultCallbackEvent !== null){
                anchor.undelegate('tbody tr', this.resultCallbackEvent,
                    this.resultCallbackProxy);
                this.resultCallbackEvent = null;
            }

            var event = this.options.routes[this.selected].results.callback.event;
            if(typeof event === 'string'){
                anchor.delegate('tbody tr', event, this.resultCallbackProxy);
                this.resultCallbackEvent = event;
            }
        },

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         */
        _resultCallback: function(event){
            var row = $(event.currentTarget),
                feature = row.data('feature').getFeature(),
                featureExtent = feature.geometry.getBounds(),
                map = feature.layer.map,
                callbackConf = this.options.routes[this.selected].results.callback;

            // buffer, if needed
            if(callbackConf.options && callbackConf.options.buffer){
                var radius = callbackConf.options.buffer;
                featureExtent.top += radius;
                featureExtent.right += radius;
                featureExtent.bottom -= radius;
                featureExtent.left -= radius;
            }

            // get zoom for buffered extent
            var zoom = map.getZoomForExtent(featureExtent);

            // restrict zoom if needed
            if(callbackConf.options &&
                (callbackConf.options.maxScale || callbackConf.options.minScale)){

                var res = map.getResolutionForZoom(zoom);
                var units = map.baseLayer.units;
                var scale = OpenLayers.Util.getScaleFromResolution(res, units);


                if(callbackConf.options.maxScale){
                    var maxRes = OpenLayers.Util.getResolutionFromScale(
                        callbackConf.options.maxScale, map.baseLayer.units);
                    if(Math.round(res) < maxRes){
                        zoom = map.getZoomForResolution(maxRes);
                    }
                }

                if(callbackConf.options.minScale){
                    var minRes = OpenLayers.Util.getResolutionFromScale(
                        callbackConf.options.minScale, map.baseLayer.units);
                    if(Math.round(res) > minRes){
                        zoom = map.getZoomForResolution(minRes);
                    }
                }
            }

            // finally, zoom
            map.setCenter(featureExtent.getCenterLonLat(), zoom);

            // And highlight new feature
            var layer = feature.layer;
            $.each(layer.selectedFeatures, function(idx, feature) {
                layer.drawFeature(feature, 'default');
            });
            layer.drawFeature(feature, 'select');
            layer.selectedFeatures.push(feature);
        },

        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },

        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });
})(jQuery);

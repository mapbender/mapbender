/*
 * !!! Add to documentation after pull request:
 * Configuration: each route, set for an icon either an abs. url (http://XXX) or  a rel. url ('bundles/mapbendercore/image/pin_red.png')
 */

(function($){

    $.widget('mapbender.mbSearchRouter', {
        options: {
            asDialog: true,         // Display as jQuery UI dialog
            timeoutFactor: 2,       // use delay * timeoutFactor before showing
            cleanOnClose: false,    // remove all search results, reset a serach form by searchProuter close
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
        highlighter: {},
        currentStyle: null,
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
        },

        _setup: function(){
            var widget = this;
            widget.callbackUrl = Mapbender.configuration.application.urls.element + '/' + widget.element.attr('id') + '/';
            var element = widget.element;
            var options = widget.options;
            var srsList = [];
            var help = {};
            for (var key in widget.options.routes) {
                // check if srs loaded
                if (widget.options.routes[key].class_options.srs) {
                    var srs = widget.options.routes[key].class_options.srs;
                    var proj = Mapbender.Model.getProj(srs);
                    // notice if a projection is not loaded
                    if (!proj && !help[srs]) {
                        help[srs] = srs;
                        srsList.push(srs);
                    }
                }
                var res = widget.options.routes[key].results;
                if(res && res.styleMap){
                    for(var key in res.styleMap){
                        var sm = res.styleMap[key];
                        if(sm['externalGraphic'] && sm['externalGraphic'].indexOf('http') !== 0){
                            sm['externalGraphic'] = Mapbender.configuration.application.urls.asset + sm['externalGraphic'];
                        }
                    }
                }
            }
            /* load projections */
            if (srsList.length) {
                $('#' + options.target).data('mapbenderMbMap').loadSrs(srsList);
            }
            var searchModel = widget.searchModel = new Mapbender.SearchModel(null, null, widget);
            var routeSelect = $('select#search_routes_route', element);
            var routeCount = 0;
            widget.mbMap = $('#' + options.target).data('mapbenderMbMap');
            var map = widget.map = widget.mbMap.map.olMap;

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
            searchModel.on('change:results', widget._searchResults, widget);
            searchModel.on('request', widget._setActive, widget);
            searchModel.on('error sync', widget._setInactive, widget);
            searchModel.on('error sync', widget._showResultState, widget);

            widget.resultCallbackProxy = $.proxy(widget._resultCallback, widget);

            // Prepare autocompletes
            $('form input[data-autocomplete="on"]', element).each(
                $.proxy(widget._setupAutocomplete, widget));
            $('form input[data-autocomplete^="custom:"]', element).each(
                $.proxy(widget._setupCustomAutocomplete, widget));

            // Prepare search button (trigger form submit)
            $('a[role="search_router_search"]', element)
                .button()
                .click(function(){
                    $('form[name="' + widget.selected + '"]', element).submit();
                });

            // Prevent map getting cursors keys
            element.bind('keydown', function(event){
                event.stopPropagation();
            });

            // Listen to changes of search select (switching and forms resetting)
            routeSelect.change($.proxy(widget._selectSearch, widget));
            routeSelect.change();
            // But if there's only one search, we actually don't need the select
            for(var route in options.routes){
                if(options.routes.hasOwnProperty(route)){
                    routeCount++;
                }
            }
            if(routeCount === 1){
                $('#search_routes_route_control_group').hide().next('hr').hide();
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

            $(document).bind('mbmapsrschanged', $.proxy(this._srschanged, this));

            map.events.register("zoomend", this, function() {
                widget.redraw();
            });

            widget._trigger('ready');
            widget._ready();

            if(widget.options.autoOpen) {
                widget.open();
            }
        },

        /**
         * Remove last search results
         */
        removeLastResults: function(){
            var widget = this;
            widget.searchModel.reset();
//            widget._getLayer().removeAllFeatures();
        },

        /**
         * Redraw current result layer selected feature
         */
        redraw: function() {
            var widget = this;
            var feature = widget.currentFeature ? widget.currentFeature : null;
            if( widget.currentFeature) {
                feature.layer.drawFeature(feature, 'select');
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
            if(this.options.cleanOnClose) {
                this._reset();
            }
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
            this.currentStyle = this._createStyleMap(this.options.routes[this.selected].results.styleMap);
        },

        /**
         * Reset current search form
         */
        _reset: function() {
            $('select#search_routes_route', this.element).change();
            this.currentFeature = null;
            this._removeResults();
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
                tbody = $('<tbody></tbody>');

            $('tbody', table).remove();

            if(results.length > 0) $('.no-results', this.element).hide();
            
            var first_srs = null;
            results.each(function(feature, idx){
                if(!first_srs){
                    first_srs = 'EPSG:' + feature.attributes.srid;
                }
                var row = $('<tr/>');
                row.addClass(idx % 2 ? "even" : "odd");
                row.data('feature', feature);
                for(var header in headers){
                    var d = feature.get('properties')[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                }
                tbody.append(row);
            });
            if (!Mapbender.Model.getProj(first_srs)) {
                this.mbMap.loadSrs([first_srs]);
            }
            table.append(tbody);
            
            $('.search-results tr', this.element).on('mouseover', $.proxy(this._mouseOver, this));
            $('.search-results tr', this.element).on('mouseout', $.proxy(this._mouseOut, this));
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
//
//        /**
//         * Get highlight layer. Will construct one if neccessary.
//         * @TODO: Backbonify (view)
//         *
//         * @return OpenLayers.Layer.Vector Highlight layer
//         */
//        _getLayer: function(forceRebuild){
//            if(this.highlightLayer === null || forceRebuild){
//                this.highlightLayer = new OpenLayers.Layer.Vector('Search Highlight', {
//                    styleMap: this._createStyleMap(this.options.routes[this.selected].results.styleMap)
//                });
//            }
//
//            if(this.highlightLayer.map === null){
//                var map = $('#' + this.options.target).data('mapbenderMbMap').map.olMap;
//                map.addLayer(this.highlightLayer);
//            }
//
//            return this.highlightLayer;
//        },

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
            var feature = $(event.currentTarget).data('feature').getFeature().clone();
            var srs = Mapbender.Model.getProj('EPSG:' + feature.attributes.srid);
            var results = this.options.routes[this.selected].results;
            var c_options = results.callback && results.callback.options ? results.callback.options : null;
            feature.style = this.currentStyle.styles.select.defaultStyle;
            var highlighter = this._getHighLighter('center');
            var zoomOptions = {
                buffer: c_options && c_options.buffer ? c_options.buffer : null,
                minScale: c_options && c_options.minScale ? c_options.minScale : null,
                maxScale: c_options && c_options.maxScale ? c_options.maxScale : null
            };
            if(event.ctrlKey){
                highlighter.add(feature, srs).show().zoom(zoomOptions);
            } else {
                highlighter.remove().add(feature, srs).show().zoom(zoomOptions);
            }
        },
        
        _srschanged: function(event, srs){
            srs = { projection: this.mbMap.map.olMap.getProjectionObject() };
            for(var key in this.highlighter){
                this.highlighter[key].hide().transform(srs).show();
            }
        },
        
        _getHighLighter: function(key) {
            if (!this.highlighter[key]) {
                this.highlighter[key] = new Mapbender.SimpleHighlighting(this.mbMap, 1.0, key === 'mouseover' ? this.options.hover_style : this.options.hits_style);
            }
            return this.highlighter[key];
        },

        _mouseOver: function(event){
            var feature = $(event.currentTarget).data('feature').getFeature().clone();
            var srs = Mapbender.Model.getProj('EPSG:' + feature.attributes.srid);
            feature.style = this.currentStyle.styles.default.defaultStyle;
            var highlighter = this._getHighLighter('mouse');
            highlighter.add(feature, srs).show();
        },
        _mouseOut: function(e){
            this._getHighLighter('mouse').remove();
        },
        
        _removeResults: function(){
            for(var key in this.highlighter){
                this.highlighter[key].hide().remove();
            }
            $('.search-results tr', this.element).off('mouseover', $.proxy(this._mouseOver, this));
            $('.search-results tr', this.element).off('mouseout', $.proxy(this._mouseOut, this));
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

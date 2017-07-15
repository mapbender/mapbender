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
        resultCallbackEvents: null,
        detailResultCallbackEvent: null,
        detailResultCallbackEvents: null,
        resultCallbackProxy: null,
        searchModel: null,
        autocompleteModel: null,
        popup: null,

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
        },

        _setup:         function(){
            var widget = this;
            var element = widget.element;
            var options = widget.options;
            var searchModel = widget.searchModel = new Mapbender.SearchModel(null, null, widget);
            var routeSelect = $('select#search_routes_route', element);
            var routeCount = 0;
            var map = widget.map = $('#' + options.target).data('mapbenderMbMap').map.olMap;

            // bind form reset to reset search model
            element.delegate('.search-forms form', 'reset', function(){
                widget.resetFormStyle();
                widget.removeLastResults();
                widget._removeDetailResultCallbacks();
            });
            // bind form submit to send search model
            element.delegate('.search-forms form', 'submit', function(evt){
                widget.removeLastResults();
                widget.searchModel.submit(evt);
            });

            // bind result to result list and map view
            // @Todo: use this also for details request
            searchModel.on('change:results', widget._searchResults, widget);
            searchModel.on('request', widget._setActive, widget);
            searchModel.on('error sync', widget._setInactive, widget);
            searchModel.on('error sync', widget._showResultState, widget);
            widget.resultCallbackProxy = $.proxy(widget._resultCallback, widget);

            /*
              Prepare Navigation Shortcuts (Display A-Z and 0-9 - clickable)
              onClick the clicked character will be set into input field and the search is triggered
            */
            $('form input[data-navigationshortcuts="on"]', element).each(
                $.proxy(widget._setupNavigationShortCuts, widget));

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
                // just to make sure...
                $('#search_routes_route').parents('.dropdown').hide();
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
            this.currentFeature = null;
        },

        /**
         * Set up NavigationShortCuts for all input with type text and data-navigationshortcuts="on"
         *
         * @param  integer      idx   Running index
         * @param  HTMLDomNode  input Input element
         */
        _setupNavigationShortCuts: function(idx, input){
            var widget = this;
            input = $(input);
            if (input.prop('type')!='text') {
                return;
            }
            // @TODO all the css definition must be put into .css files of course...
            var shortcutnav = $('<div></div>').css('width','230px').css('height','60px').css('margin','5px auto 40px');
            var navelement;
            $.each([
                'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P',
                'Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5',
                '6','7','8','9'], function(k,v){
                // @TODO all the css definition must be put into .css files of course...
                navelement = $('<span>'+v+'</span>')
                  .css('width','15px')
                  .css('margin','0px 5px')
                  .css('float','left')
                  .css('text-decoration','underline')
                  .css('text-align','center')
                  .css('cursor','pointer');
                navelement.on('click',function() {
                    input.val(v);
                    widget._search();
                });
                shortcutnav.append(navelement);
            });
            input.after(shortcutnav);

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

        resetFormStyle: function() {
            // @TODO removeClass('error')
            // @TODO all the css definition must be put into .css files of course...
            $(':input[required]').css('border', '');
            $(':input[required]').parents('.dropdown').css('border', '');
        },

        /**
         * Start a search, but only after successful form validation
         */
        _search: function() {
            var form = $('form[name="' + this.selected + '"]', this.element);
            var valid = true;
            this.resetFormStyle();
            $.each($(':input[required]', form), function() {
                if('' === $(this).val()) {
                    // @TODO addClass('error')
                    // @TODO all the css definition must be put into .css files of course...
                    // at least now the user might have a little clue that he didn't fill out all required fields
                    $(this).parents('.dropdown').css("border", "1px solid red");
                    $(this).css("border", "1px solid red");
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
            if(typeof this.options.routes[this.selected].results.display_headers === 'undefined'){
                this.options.routes[this.selected].results.display_headers = 'yes';
            }
            var headers = this.options.routes[this.selected].results.headers;
            var table = $('<table></table>'),
                thead = $('<thead><tr></tr></thead>').appendTo(table);
            if (this.options.routes[this.selected].results.display_headers !== 'no') {
                for (var header in headers) {
                    thead.append($('<th>' + headers[header] + '</th>'));
                }
            } else {
                for (var header in headers) {
                    // @TODO all the css definition must be put into .css files of course...
                    thead.append($('<th>&nbsp;</th>').css('display','none'));
                }
            }
            table.append($('<tbody></tbody>'));
            container.append(table);
        },

        /**
         * Update result list when search model's results property was changed
         */
        _searchResults: function(model, results, options){
            switch (this.options.routes[this.selected].results.view) {
                case('table') :
                        var container = $('.search-results', this.element);
                        if($('table', container).length === 0) {
                            this._prepareResultTable(container);
                        }
                        this._searchResultsTable(model, results, options);
                    break;
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
            var widget = this,
                headers = widget.options.routes[widget.selected].results.headers,
                table = $('.search-results table', widget.element),
                tbody = $('<tbody></tbody>'),
                layer = widget._getLayer(true);
            $('tbody', table).remove();
            layer.removeAllFeatures();
            features = [];
            if(results.length > 0) $('.no-results', widget.element).hide();
            results.each(function(feature, idx){
                var row = $('<tr/>');
                row.addClass(idx % 2 ? "even" : "odd");
                row.data('feature', feature);
                for(var header in headers){
                    var d = feature.get('properties')[header];
                    row.append($('<td>' + (d || '') + '</td>'));
                }
                tbody.append(row);

                features.push(feature.getFeature());
            });
            table.append(tbody);
            layer.addFeatures(features);
            widget.resultCallbackProxy = $.proxy(widget._resultCallback, widget);
            widget._setupResultCallbacks();
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
            var widget = this;
            var options = widget.options;
            return options.routes[widget.selected];
        },

        /**
         * Get highlight layer. Will construct one if neccessary.
         * @TODO: Backbonify (view)
         *
         * @return OpenLayers.Layer.Vector Highlight layer
         */
        _getLayer: function(forceRebuild) {
            var widget = this;
            var options = widget.options;
            var map = $('#' + options.target).data('mapbenderMbMap').map.olMap;
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
            var widget = this;
            var options = widget.options;
            var anchor = $('.search-results', widget.element);
            if(typeof options.routes[this.selected].results.callback.event === 'undefined'){
                return;
            }
            var event = widget.options.routes[widget.selected].results.callback.event;
            if(typeof event === 'string'){
                anchor.delegate('tbody tr', event, widget.resultCallbackProxy);
                widget.resultCallbackEvent = event;
            }
        },

        /**
         * @TODO: finish: Set up multiple result callbacks (zoom on click + on mouseover for example)
         */
        _setupResultCallbacks: function(){
            var widget = this;
            var options = widget.options;
            var anchor = $('.search-results', widget.element);
            widget._removeDetailResultCallbacks();
            widget._removeResultCallbacks();
            // if we don't have multiple callback.events we just setup the default callback.event
            // (this has not been finished yet : do not use: options.routes[this.selected].results.callback.events)
            if(typeof options.routes[this.selected].results.callback.events === 'undefined'){
                widget._setupResultCallback();
                return;
            }
            var events = widget.options.routes[widget.selected].results.callback.events;
            $.each(events, function(k,v){
                if(typeof v.event === 'string'){
                    anchor.delegate('tbody tr', v.event, widget.resultCallbackProxy);
                    widget.resultCallbackEvents.push(v.event);
                }
            });
            if (widget.resultCallbackEvents.length === 0) {
                console.log('the callback.events are not configured properly - you are welcome');
            }
        },

        _removeResultCallbacks: function() {
            var widget = this;
            var anchor = $('.search-results', widget.element);
            if(widget.resultCallbackEvent !== null){
                anchor.undelegate('tbody tr', widget.resultCallbackEvent, widget.resultCallbackProxy);
                widget.resultCallbackEvent = null;
            }
            if(widget.resultCallbackEvents !== null){
                $.each(resultCallbackEvents, function(k,v){
                    if(typeof v.event === 'string'){
                        anchor.undelegate('tbody tr', v.event, widget.resultCallbackProxy);
                    }
                });
                widget.resultCallbackEvents = null;
            }
        },

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         */
        _resultCallback: function(event){
            var widget = this;
            var options = widget.options;
            widget._basicResultCallback(event);
            if(typeof options.routes[this.selected].results.detail_request !== 'undefined'){
                widget._detailRequestResultCallback(event);
            }
        },

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         */
        _basicResultCallback: function(event){
            var widget = this;
            var row = $(event.currentTarget),
              feature = $.extend({}, row.data('feature').getFeature()),
              map = feature.layer.map,
              callbackConf = widget._getCallbackConf(),
              srs = Mapbender.Model.getProj(widget.searchModel.get("srs"));
            var mapProj = Mapbender.Model.getCurrentProj();
            if(srs.projCode !== mapProj.projCode) {
                feature.geometry = feature.geometry.transform(srs, mapProj);
            }
            var featureExtent = $.extend({},feature.geometry.getBounds());
            // @TODO make multiple event definitions possible
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
            // @TODO make multiple event definitions possible
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
            widget.currentFeature = feature;
            widget.redraw();
            layer.selectedFeatures.push(feature);
        },

        _getCallbackConf: function(event){
            var widget = this;
            var options = widget.options;
            if(typeof options.routes[this.selected].results.callback.events === 'undefined'){
                return widget.getCurrentRoute().results.callback;
            }
            // @TODO make multiple event definitions possible
        },

        /**
         * Detail Request Result callback - who called this methods "callbacks" btw? however copy & paste rules...
         *
         * @param  jQuery.Event event Mouse event
         */
        _detailRequestResultCallback: function(event){
            var widget = this;
            options = widget.options;
            var row = $(event.currentTarget),
              feature = $.extend({}, row.data('feature').getFeature()),
              map = feature.layer.map;
            var form = $('form[name="' + widget.selected + '"]', widget.element);
            var properties = {};
            var detailrequest = options.routes[this.selected].results.detail_request;

            // we add the key/value pair of the id defined in results.detail_request.id
            // this is stored in data of the row (we use the featureobject as representation for the row)
            var detail_request_id_value = feature.attributes[detailrequest.id];
            properties[detailrequest.id] = detail_request_id_value;

            var data = {
                data : {
                    "properties": properties,
                    "autocomplete_keys":{},
                    "srs":map.getProjection(),
                    "extent":map.getExtent().toArray()
                }
            };

            // zoom to feature before we set up the detailrequest
            widget._basicResultCallback(event);
            $.ajax({
                method: "POST",
                url: widget.callbackUrl+widget.selected+"/details",
                data: data
            })
              .done( function( response ) {
                  // for now we just hide the result counter
                  var element = widget.element;
                  $('.result-counter', element).hide();
                  var parsedResponse = {
                      results: new Mapbender.FeatureCollection(response.features)
                  }
                  // @TODO all the css definition must be put into .css files of course...
                  var details = $('<td></td>').attr('id','details').css('width','100%');
                  var features = [];
                  $.each(parsedResponse.results.models,function(k,result){
                      // @TODO all the css definition must be put into .css files of course...
                      var detail = $('<span></span>')
                        .css('width', '15px')
                        .css('margin', '0px 5px')
                        .css('float', 'left')
                        .css('text-decoration', 'underline')
                        .css('text-align', 'center')
                        .css('cursor', 'pointer');
                      if(result.getFeature()) {
                          var feature = result.getFeature();
                          detail.data('feature', result);
                          detail.text(feature.attributes[detailrequest.class_options.attributes[0]]);
                          if (detail.text() != 'null') {
                              details.append(detail);
                              features.push(feature);
                          }
                      }
                  });
                  var layer = widget._getLayer(true);
                  var anchor = $('.search-results', widget.element);
                  if(features.length == 0) {
                      // @TODO : change this to get message from config
                      var detail = $('<span></span>').text('Keine Hausnummern gefunden');
                      details.append(detail);
                      features.push(feature);
                      layer.removeAllFeatures();
                      widget._removeResultCallbacks();
                      // save clicked element
                      var currentTarget = $(event.currentTarget).clone();
                      currentTarget.css('cursor','arrow');
                      // remove all results from first request
                      anchor.find('tr').remove();
                      // but keep clicked element
                      anchor.find('tbody').append(currentTarget);
                      // @TODO all the css definition must be put into .css files of course...
                      var detailsresult = $('<tr></tr>').attr('id','detailsresult').css('font-weight','bold').css('height','30px').html(details);
                      // add a row with details request's answer
                      currentTarget.after(detailsresult);
                  } else {
                      layer.removeAllFeatures();
                      widget._removeResultCallbacks();
                      // save clicked element
                      var currentTarget = $(event.currentTarget).clone();
                      currentTarget.css('cursor','arrow');
                      // remove all results from first request
                      anchor.find('tr').remove();
                      // but keep clicked element
                      anchor.find('tbody').append(currentTarget);
                      // @TODO all the css definition must be put into .css files of course...
                      var detailsresult = $('<tr></tr>').attr('id','detailsresult').css('font-weight','bold').css('height','30px').html(details);
                      // add a row with details request's answer
                      currentTarget.after(detailsresult);
                      widget._setupDetailResultCallbacks();
                      // add features to current layer
                      layer.addFeatures(features);
                  }
              }).error(function(response) {
                console.log(response);
                alert('there has been an error recieving data from the server - you may retry in a few seconds');
            });
        },

        /**
         * Set up detailResult callback (zoom on click for example)
         */
        _setupDetailResultCallback: function(){
            var widget = this;
            var options = widget.options;
            var anchor = $('#detailsresult', widget.element);
            widget.resultCallbackProxy = $.proxy(widget._detailResultCallback, widget);
            if(typeof options.routes[this.selected].results.detail_request.results.callback.event === 'undefined'){
                return;
            }
            widget._removeDetailResultCallbacks();
            var event = widget.options.routes[widget.selected].results.detail_request.results.callback.event;
            if(typeof event === 'string'){
                anchor.delegate('span', event, widget.resultCallbackProxy);
                widget.detailResultCallbackEvent = event;
            }
        },

        /**
         * @TODO: finish: Set up multiple result callbacks (zoom on click + on mouseover for example)
         */
        _setupDetailResultCallbacks: function(){
            var widget = this;
            var options = widget.options;
            var anchor = $('#detailsresult', widget.element);
            // if we don't have multiple callback.events we just setup the default callback.event
            // (this has not been finished yet : do not use: options.routes[this.selected].results.callback.events)
            if(typeof options.routes[this.selected].results.detail_request.results.callback.events === 'undefined'){
                widget._setupDetailResultCallback();
                return;
            }
            widget.resultCallbackProxy = $.proxy(widget._detailResultCallback, widget);
            widget._removeDetailResultCallbacks();
            var events = widget.options.routes[widget.selected].results.detail_request.results.callback.events;
            $.each(events, function(k,v){
                if(typeof v.event === 'string'){
                    anchor.delegate('span', v.event, widget.resultCallbackProxy);
                    widget.detailResultCallbackEvents.push(v.event);
                }
            });
            if (widget.detailResultCallbackEvents.length === 0) {
                console.log('the callback.events are not configured properly - you are welcome');
            }
        },

        _removeDetailResultCallbacks: function() {
            var widget = this;
            var anchor = $('#detailsresult', widget.element);
            if(widget.detailResultCallbackEvent !== null){
                anchor.undelegate('span', widget.detailResultCallbackEvent, widget.resultCallbackProxy);
                widget.detailResultCallbackEvent = null;
            }
            if(widget.detailResultCallbackEvents !== null){
                $.each(detailResultCallbackEvents, function(k,v){
                    if(typeof v.event === 'string'){
                        anchor.undelegate('span', v.event, widget.resultCallbackProxy);
                    }
                });
                widget.detailResultCallbackEvents = null;
            }
        },

        /**
         * Result callback
         *
         * @param  jQuery.Event event Mouse event
         *
         * @TODO : this method must be edited alot... especially the zoom must come from configuration
         */
        _detailResultCallback: function(event){
            var widget = this;
            var row = $(event.currentTarget),
              feature = $.extend({}, row.data('feature').getFeature()),
              map = feature.layer.map,
              callbackConf = widget._getDetailCallbackConf(),
              srs = Mapbender.Model.getProj(widget.searchModel.get("srs"));
            var mapProj = Mapbender.Model.getCurrentProj();
            if(srs.projCode !== mapProj.projCode) {
                feature.geometry = feature.geometry.transform(srs, mapProj);
            }
            var featureExtent = $.extend({},feature.geometry.getBounds());
            // @TODO make multiple event definitions possible
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
            // @TODO make multiple event definitions possible
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
            widget.currentFeature = feature;
            widget.redraw();
            layer.selectedFeatures.push(feature);
        },

        _getDetailCallbackConf: function(event){
            var widget = this;
            var options = widget.options;
            if(typeof options.routes[this.selected].results.detail_request.results.callback.events === 'undefined'){
                return options.routes[this.selected].results.detail_request.results.callback;
            }
            // @TODO make multiple event definitions possible
        },

        ready: function(callback){
            var widget = this;
            if(widget.readyState === true){
                callback();
            }else{
                widget.readyCallbacks.push(callback);
            }
        },

        /**
         * Execute callbacks on element ready
         */
        _ready: function() {
            var widget = this;
            for (var callback in widget.readyCallbacks) {
                callback();
                delete(widget.readyCallbacks[callback]);
            }
            widget.readyState = true;
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

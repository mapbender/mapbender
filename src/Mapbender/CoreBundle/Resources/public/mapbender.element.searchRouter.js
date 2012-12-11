(function($) {

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

    /**
     * Widget creator
     */
    _create: function() {
        if(true === this.options.asDialog) {
            this.element.dialog(this.options);
        }

        // Prepare callback URL
        this.callbackUrl = Mapbender.configuration.application.urls.element +
            '/' + this.element.attr('id') + '/';

        // Listen to changes of search select
        var routeSelect = $('select#search_routes_route', this.element);
        routeSelect.change($.proxy(this._selectSearch, this));

        // Prepare autocompletes
        $('form input[data-autocomplete="on"]', this.element).each(
            $.proxy(this._setupAutocomplete, this));
        $('form input[data-autocomplete^="custom:"]').each(
            $.proxy(this._setupCustomAutocomplete, this));
        
        this.resultCallbackProxy = $.proxy(this._resultCallback, this);
        $('#' + this.options.target.map).bind('mbmapready', function() {
            routeSelect.change();
        });

        // Prepare search button
        $('a[role="search_router_search"]').button()
            .click($.proxy(this._search, this));

        // Form submit
        this.element.delegate('form', 'submit', $.proxy(this._search, this));
            
        this.element.bind('keydown', function(event) {
            event.stopPropagation();
        });
        
        var routeCount = 0;
        for(route in this.options.routes) {
            if(this.options.routes.hasOwnProperty(route)) {
                routeCount++;
            }
        }
        if(routeCount === 1) {
            $('#search_routes_route_control_group').hide()
                .next('hr').hide();
        }
    },

    /**
     * Set up autocomplete widgets for all inputs with data-autcomplete="on"
     *
     * @param  integer      idx   Running index
     * @param  HTMLDomNode  input Input element
     */
    _setupAutocomplete: function(idx, input) {
        var self = this;
        input = $(input);
        input.autocomplete({
            delay: input.data('autocomplete-delay') || 500,
            minLength: input.data('autocomplete-minlength') || 3,
            search: $.proxy(this._autocompleteSearch, this),
            source: function(request, response) {
                self._autocompleteSource(input, request, response);
            },
            select: this._autocompleteSelect
        }).keydown(this._autocompleteKeydown);
    },
    
    _setupCustomAutocomplete: function(idx, input) {
        var plugin = $(input).data('autocomplete').substr(7);
        $(input)[plugin]();
    },

    destroy: function() {
        if(true === this.options.dialog) {
            this._super('destroy');
        }
    },

    /**
     * Open method stub. Calls dialog's open method if widget is configured as
     * an dialog (asDialog: true), otherwise just goes on and does nothing.
     */
    open: function() {
        if(true === this.options.asDialog) {
            this.element.dialog('open');
        }
    },

    /**
     * Close method stub. Calls dialog's close method if widget is configured
     * as an dialog (asDialog: true), otherwise just goes on and does nothing.
     */
    close: function() {
        if(true === this.options.asDialog) {
            this.element.dialog('close');
        }
    },

    /**
     * Set up result table when a search was selected.
     *
     * @param  jqEvent event Change event
     */
    _selectSearch: function(event) {
        this.selected = $(event.target).val();

        this._getLayer().removeAllFeatures();
        var container = $('.search-results', this.element).empty();
        
        if(this.options.routes[this.selected].hideSearchButton === true) {
            $('a[role="search_router_search"]').hide();
        } else {
            $('a[role="search_router_search"]').show();
        }

        this._setupResultCallback();
        
        if(typeof this.options.routes[this.selected].results.headers === 'undefined') {
            return;
        }
        
        var headers = this.options.routes[this.selected].results.headers;

        var table = $('<table></table>'),
            thead = $('<thead><tr></tr></thead>').appendTo(table);

        for(var header in headers) {
            thead.append($('<th>' + header + '</th>'));
        }
        
        table.append($('<tbody></tbody>'));
        
        container.append(table);

    },

    /**
     * Autocomplete search handler.
     *
     * Checks if enough time has been passed since the last search. Basically
     * this prevents an autocomplete poping up when a search is triggered by
     * keybord.
     *
     * @param  jQuery.Event event search event
     * @param  Object       ui    n/a
     */
    _autocompleteSearch: function(event, ui) {
        var delay = $(event.target).autocomplete('option', 'delay'),
            diff = (new Date()) - this.lastSearch;

        if(diff <= delay * this.options.timeoutFactor) {
            event.preventDefault();
        }
    },

    /**
     * Autocomplete source handler, does all Ajax magic.
     *
     * @param  HTMLDomNode target   Input element
     * @param  Object      request  Request object with term attribute
     * @param  function    response Autocomplete callback
     */
    _autocompleteSource: function(target, request, response) {
        var url = this.callbackUrl + 'autocomplete';

        var data = {
            target: target.attr('name'),
            term: request.term,
            data: target.closest('form').serialize(),
            srs: null,
            extent: null
        };

        var _error = function() {
            response([]);
        };

        this._setActive();
        $.ajax({
            url: url,
            data: data,
            type: 'POST',
            success: response,
            error: _error,
            complete: $.proxy(this._setInactive, this)
        });
    },

    /**
     * Store autocomplete key if suggestion was selected.
     *
     * @param  jQuery.Event event Selection event
     * @param  Object       ul    Selected item
     */
    _autocompleteSelect: function(event, ul) {
        var suggestions = $(this).data('autocomplete-suggestions');
        if(typeof ul.item.key !== 'undefined') {
            $(event.target).data('autocomplete-key', ul.item.key);
        }
    },

    /**
     * Remove stored autocomplete key when key was pressed.
     *
     * @param  jQuery.Event event Keydown event
     */
    _autocompleteKeydown: function(event) {
        $(event.target).removeData('autocomplete-key');
    },

    /**
     * Does search for current form.
     *
     * @param  jQEvent event  jQuery Event
     */
    _search: function(event) {
        // Store start time for preventing "concurrent" autocompletes
        this.lastSearch = new Date();
        event.preventDefault();
        var form = $('form[name="' + this.selected + '"]'),
            url = this.callbackUrl + 'search',
            autocomplete_keys = [];


        $(':input', form).each(function(idx, input) {
            input = $(input);
            var autocomplete_key = input.data('autocomplete-key');
            if(typeof autocomplete_key !== 'undefined') {
                autocomplete_keys.push(
                    encodeURIComponent(input.attr('name')) + '=' +
                    encodeURIComponent(autocomplete_key));
            }
        });

        var data = {
            target: this.selected,
            data: form.serialize(),
            autocomplete_keys: autocomplete_keys.join('&'),
            srs: null,
            extent: null
        };

        this._setActive();
        $.ajax({
            url: url,
            data: data,
            type: 'POST',
            success: $.proxy(this._setSearchResults, this),
            complete: $.proxy(this._setInactive, this)
        });
    },

    /**
     * Rebuilds result table with search result data.
     *
     * @param Object data Result data
     */
    _setSearchResults: function(data) {
        var headers = this.options.routes[this.selected].results.headers,
            table = $('.search-results table', this.element),
            tbody = $('<tbody></tbody>'),
            layer = this._getLayer();
        
        $('tbody', table).remove();
        layer.removeAllFeatures();

        for(var result in data) {
            var row = $('<tr></tr>');
            row.data('feature', data[result]);
            for(var header in headers) {
                d = data[result][headers[header]];
                row.append($('<td>' + (d || '') + '</td>'));
            }
            tbody.append(row);
        }

        table.append(tbody);

        // Build highlight layer
        var format = new OpenLayers.Format.WKT(),
            features = [];
        $('tr', tbody).each(function(idx, row) {
            row = $(row);
            var f = $.extend({}, row.data('feature')),
                feature = format.read(f.geom);
            
            delete f.geom;
            feature.attributes = f;

            features.push(feature);

            row.data('feature', feature);
        });
        layer.addFeatures(features);
    },

    /**
     * Add active class to widget for styling when Ajax is running
     */
    _setActive: function() {
        var outer = this.options.asDialog ? this.element.parent() : this.element;
        outer.addClass('search-active');
    },

    /**
     * Remove active class from widget
     */
    _setInactive: function() {
        var outer = this.options.asDialog ? this.element.parent() : this.element;
        outer.removeClass('search-active');
    },

    /**
     * Get highlight layer. Will construct one if neccessary.
     *
     * @return OpenLayers.Layer.Vector Highlight layer
     */
    _getLayer: function() {
        if(this.highlightLayer === null) {
            this.highlightLayer = new OpenLayers.Layer.Vector('Search Highlight', {
                styleMap: new OpenLayers.StyleMap(this.options.style)
            });
        }

        if(this.highlightLayer.map === null) {
            var map = $('#' + this.options.target).data('mbMap').map.olMap;
            map.addLayer(this.highlightLayer);
        }

        return this.highlightLayer;
    },

    /**
     * Set up result callback (zoom on click for example)
     */
    _setupResultCallback: function() {
        var anchor = $('.search-results', this.element);
        if(this.resultCallbackEvent !== null) {
            anchor.undelegate('tbody tr', this.resultCallbackEvent,
                this.resultCallbackProxy);
            this.resultCallbackEvent = null;
        }

        var event = this.options.routes[this.selected].results.callback.event;
        if(typeof event === 'string') {
            anchor.delegate('tbody tr', event, this.resultCallbackProxy);
            this.resultCallbackEvent = event;
        }
    },

    /**
     * Result callback
     *
     * @param  jQuery.Event event Mouse event
     */
    _resultCallback: function(event) {
        var row = $(event.currentTarget),
            feature = row.data('feature'),
            featureExtent = feature.geometry.getBounds(),
            map = feature.layer.map,
            callbackConf = this.options.routes[this.selected].results.callback;

        // buffer, if needed
        if(callbackConf.options && callbackConf.options.buffer) {
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
            (callbackConf.options.maxScale || callbackConf.options.minScale)) {

            var res = map.getResolutionForZoom(zoom);
            var units = map.baseLayer.units;
            var scale = OpenLayers.Util.getScaleFromResolution(res, units);

            
            if(callbackConf.options.maxScale) {
                var maxRes = OpenLayers.Util.getResolutionFromScale(
                    callbackConf.options.maxScale, map.baseLayer.units);
                if(Math.round(res) < maxRes) {
                    zoom = map.getZoomForResolution(maxRes);
                }
            }

            if(callbackConf.options.minScale) {
                var minRes = OpenLayers.Util.getResolutionFromScale(
                    callbackConf.options.minScale, map.baseLayer.units);
                if(Math.round(res) > minRes) {
                    zoom = map.getZoomForResolution(minRes);
                }
            }
        }

        // finally, zoom
        map.setCenter(featureExtent.getCenterLonLat(), zoom);
    }
});

})(jQuery);

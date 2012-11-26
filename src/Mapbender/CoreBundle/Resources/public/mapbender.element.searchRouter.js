(function($) {

$.widget('mapbender.mbSearchRouter', {
    options: {
        asDialog: true
    },

    callbackUrl: null,
    selected: null,

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
        $('select#search_routes_route', this.element).change(
            $.proxy(this._selectSearch, this)).change();

        // Prepare autocompletes
        $('form input[data-autocomplete="on"]', this.element).each(
            $.proxy(this._setupAutocomplete, this));

        // Prepare search button
        $('a[role="search_router_search"]').click($.proxy(this._search, this));
    },

    /**
     * Set up autocomplete widgets for all inputs with data-autcomplete="on"
     * @param  integer      idx   Running index
     * @param  HTMLDomNode  input Input element
     */
    _setupAutocomplete: function(idx, input) {
        var self = this;
        input = $(input);
        input.autocomplete({
            delay: input.data('autocomplete-delay') || 500,
            minLength: input.data('autocomplete-minlength') || 3,
            source: function(request, response) {
                self._autocompleteSource(input, request, response);
            },
            select: this._autocompleteSelect
        }).keydown(this._autocompleteKeydown);
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
            this._super('open');
        }
    },

    /**
     * Close method stub. Calls dialog's close method if widget is configured
     * as an dialog (asDialog: true), otherwise just goes on and does nothing.
     */
    close: function() {
        if(true === this.options.asDialog) {
            this._super('close');
        }
    },

    /**
     * Set up result table when a search was selected.
     *
     * @param  jqEvent event Change event
     */
    _selectSearch: function(event) {
        this.selected = $(event.target).val();

        var container = $('.search-results', this.element).empty(),
            headers = this.options.routes[this.selected].results.headers;

        var table = $('<table></table>'),
            thead = $('<thead><tr></tr></thead>').appendTo(table);

        for(var header in headers) {
            thead.append($('<th>' + header + '</th>'));
        }
        
        table.append($('<tbody></tbody>'));
        
        container.append(table);

    },

    /**
     * Autocomplete source handler, does all Ajax magic
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
     * Store autocomplete key if suggestion was selected
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
     * Remove stored autocomplete key when key was pressed
     * @param  jQuery.Event event Keydown event
     */
    _autocompleteKeydown: function(event) {
        $(event.target).data('autocomplete-key', undefined);
    },

    /**
     * Does search for current form
     * @param  jQEvent event  jQuery Event
     */
    _search: function(event) {
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
     * Rebuilds result table with search result data
     * @param Object data Result data
     */
    _setSearchResults: function(data) {
        var fragment = document.createDocumentFragment(),
            headers = this.options.routes[this.selected].results.headers,
            tbody = $('.search-results tbody', this.element).empty();

        for(var result in data) {
            var row = $('<tr></tr>');
            for(var header in headers) {
                d = data[result][headers[header]];
                row.append($('<td>' + (d || '') + '</td>'));
            }
            tbody.append(row);
        }
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
    }
});

})(jQuery);

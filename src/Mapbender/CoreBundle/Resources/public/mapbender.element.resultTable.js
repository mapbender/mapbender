(function($) {
    /**
     * Mapbender result table element.
     * Uses DataTables API
     *
     * @see http://datatables.net/reference/api/
     *
     * @example $('<div/>').resultTable( lengthChange: false,
     searching:    false,
     info:         false,
     columns:      [{data: 'id', title: 'ID'}, {data: 'label', title: 'Title'}],
     data:         [{id: 1, label: 'example'}]
     */
    $.widget("mapbender.resultTable", {

        _table:     null,
        _dataTable: null,
        _selection: null,

        /**
         * Constructor.
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var el = $(widget.element);
            var table = widget._table = $('<table class="table table-striped table-hover"></table>');
            var options = widget.options;
            var isSelectable = _.has(options, 'selectable') && options.selectable;
            var hasBottomNavigation = _.has(options, 'bottomNavigation') && _.isArray(options.bottomNavigation);
            var hasRowButtons = options.hasOwnProperty('buttons');
            var dataTableContainer = null;

            el.append(table);
            el.addClass('mapbender-element-result-table');

            if(isSelectable) {
                widget._addSelection();
            }

            if(hasRowButtons) {
                widget._addButtons(options.buttons);
            }

            var dataTable = widget._dataTable = table.DataTable($.extend(options, {
                "oLanguage": {
                    sEmptyTable: "Keine Ergebnisse gefunden",
                    sInfo:       "_START_ bis _END_ von _TOTAL_",
                    "oPaginate": {
                        "sSearch":   "Filter:",
                        "sNext":     "Weiter",
                        "sPrevious": "Zurück"
                    }
                }
            }));

            dataTableContainer = table.closest('.dataTables_wrapper');
            dataTableContainer.find('.dataTables_paginate a').addClass('button');

            if(isSelectable) {

                var selectionManager = widget.getSelection();

                dataTable.on('page', function() {

                    $.each(dataTable.$('tr'), function() {
                        var tr = this;
                        var rowData = widget.getDataByRow(tr);
                        var foundData = null;

                        $.each(selectionManager.list,function(){
                            var selectedData = this;
                            if(rowData == selectedData){
                                foundData = selectedData;
                                return false;
                            }
                        });

                        var $tr = $(tr);
                        var checkbox = $('td.selection input[type=checkbox]', $tr);

                        if(foundData) {
                            checkbox.prop('checked', true);
                            $tr.addClass('warning');
                        }else{
                            checkbox.prop('checked', false);
                            $tr.removeClass('warning');
                        }
                    });
                });

                selectionManager.on('add', function(data) {
                    var tr = widget.getRowByData(data);
                    if(!tr){
                        return;
                    }
                    var checkbox = $('td.selection input[type=checkbox]', tr);
                    checkbox.prop('checked', true);
                    tr.addClass('warning');
                }).on('remove', function(data) {
                    var tr = widget.getRowByData(data);
                    if(!tr){
                        return;
                    }
                    $('td.selection input[type=checkbox]', tr).prop('checked', false);
                    tr.removeClass('warning');
                });

                $(table).delegate("tbody>tr[role='row']", 'click', function(e) {
                    var tr = $(this);
                    var isSelected = !tr.hasClass('warning');
                    var data = dataTable.row(this).data();

                    if(isSelected) {
                        selectionManager.add(data);
                    } else {
                        selectionManager.remove(data);
                    }
                });
            }

            if(hasRowButtons) {
                $.each(options.buttons, function(idx, button) {
                    if(!button.hasOwnProperty('onClick'))
                        return;

                    $(table).delegate("tbody>tr[role='row'] button." + button.className, 'click', function(e) {
                        var $button = $(this);
                        var data = dataTable.row($button.closest('tr')[0]).data();
                        e.stopPropagation();
                        button.onClick(data, $button);
                    });
                });
            }

            if(hasBottomNavigation) {
                this.addBottomNavigation(options.bottomNavigation);
            }
        },

        genNavigation: function(elements) {
            var html = $('<div class="button-navigation"/>');
            $.each(elements, function(idx, element) {

                var type = 'button';
                if(_.has(element,'type')){
                    type = element.type;
                }else if(_.has(element,'html')){
                    type = 'html';
                }

                switch(type){
                    case 'html':
                        html.append(element.html);
                        break;
                    case 'button':
                        var button = $('<button class="button" title="' + element.title + '">' + element.title + '</button>');
                        if(_.has(element,'cssClass')){
                             button.addClass(element.cssClass);
                        }
                        if(_.has(element,'className')){
                            button.addClass("icon-"+element.className);
                            button.addClass( element.className);
                        }

                        html.append(button);
                        break;
                }
            });
            return html;
        },

        /**
         * Get DataTables API
         * @see http://datatables.net/reference/api/
         */
        getApi: function() {
            return this._dataTable;
        },
        
        /**
         * Get widget itself
         * 
         * @returns widget
         */
        getWidget: function(){
            return this;
        },

        /**
         * Get selection manager
         */
        getSelection: function() {
            var widget = this;
            if(!widget._selection) {
                widget._selection = $.extend(true, new function() {
                    var me = this;
                    var list = me.list = [];
                    this.table = widget._table;

                    /**
                     * Add selection
                     *
                     * @param data
                     */
                    me.add = function(data) {
                        if(_.indexOf(list,data) != -1){
                            return this;
                        }
                        list.push(data);
                        me.dispatch('add', data);
                        me.dispatch('change', list);
                        return this;
                    };

                    /**
                     * Remove selection
                     * @param data
                     * @return {boolean}
                     */
                    me.remove = function(data) {
                        if(_.indexOf(list, data) == -1) {
                            return this;
                        }
                        list.splice(_.indexOf(list, data), 1);
                        me.dispatch('remove', data);
                        me.dispatch('change', list);
                        return this;
                    };
                }, EventDispatcher);
            }
            return widget._selection;
        },

        /**
         * Set option listener
         *
         * @param key
         * @param value
         * @private
         */
        _setOption: function(key, value) {
            switch (key) {
                case "data":
                    this.setData(value);
            }
        },

        /**
         * Set table data
         *
         * @param data
         */
        setData: function(data) {
            var options = $.extend(this.options, {aaData: data});
            this.options.data = data;
            this._dataTable.destroy();
            this._dataTable = $(this._table).DataTable(options);
        },

        _addSelection: function() {
            var options = this.options;
            var columns = options.columns;

            options.columns = _.union([{
                data:  null,
                title: ''
            }], columns);

            var columnDef = [{
                targets:        0,
                className:      'selection',
                width:          "1%",
                orderable:      false,
                searchable:     false,
                defaultContent: '<input type="checkbox" value="1"/>'
            }];

            // merge definitions
            options.columnDefs = options.hasOwnProperty('columnDefs') ? _.flatten(options.columnDefs, columnDef) : columnDef;
        },

        _addButtons: function(buttons) {
            var options = this.options;

            options.columns.push({
                data:  null,
                title: ''
            });

            var columnDef = [{
                targets:        -1,
                className:      'buttons',
                width:          "1%",
                orderable:      false,
                searchable:     false,
                defaultContent: $('<div>').append(this.genNavigation(options.buttons).clone()).html()
            }];

            // merge definitions
            options.columnDefs = options.hasOwnProperty('columnDefs') ? _.union(options.columnDefs, columnDef) : columnDef;
        },

        /**
         *
         * @param buttons
         * @return {*}
         */
        addBottomNavigation: function(buttons) {
            var widget = this;
            var el = $(widget.element);
            var options = widget.options;
            var navigation = widget.genNavigation(buttons).addClass('bottom-navigation');

            $('button', navigation).on('click', function(event) {
                var button = $(event.currentTarget);
                // find and run callback, if defined in configuration
                $.each(options.bottomNavigation, function(idx, config) {
                    if(button.hasClass(config.className) && config.hasOwnProperty('onClick')) {
                        config.onClick($.extend(event, {
                            widget:    widget,
                            dataTable: widget._dataTable,
                            table:     widget._table,
                            config:    config
                        }));
                    }
                });
            });

            el.append(navigation);

            return navigation;
        },

        getRowByData: function(data) {
            var widget = this;
            var r = null;
            $.each(widget.getVisibleRows(), function() {
                if(widget.getDataByRow(this) == data) {
                    r = $(this);
                    return false;
                }
            });
            return r;
        },

        getVisibleRows: function() {
            return $(">tbody>tr[role='row']", this._table);
        },

        getVisibleRowData: function() {
            var list = [];
            var widget = this;

            $.each(widget.getVisibleRows(), function() {
                list.push(widget.getDataByRow(this));
            });

            return list;
        },

        getDataByRow: function(tr) {
            return this._dataTable.row(tr).data();
        },

        selectVisibleRows: function() {
            var widget = this;
            var selectionManager = widget.getSelection();
            $.each(widget.getVisibleRows(), function() {
                selectionManager.add(widget.getDataByRow(this));
            });
        },

        // TODO: realize
        selectAll: function() {
            var widget = this;
            var selectionManager = widget.getSelection();
            $.each(widget._dataTable.data(), function() {
                selectionManager.add(this);
            });
        },

        deselectVisibleRows: function() {
            var widget = this;
            var selectionManager = widget.getSelection();
            $.each(widget.getVisibleRows(), function() {
                selectionManager.remove(widget.getDataByRow(this));
            });
        },

        // TODO: realize
        deselectAll: function() {
            var widget = this;
            var selectionManager = widget.getSelection();
            $.each(widget._dataTable.data(), function() {
                selectionManager.remove(this);
            });
        },

        hasUnselectedVisibleRows: function() {
            var r = false;
            $.each(this.getVisibleRows(),function(){
                if(!$(this).hasClass('warning')){
                    r = true;
                    return false;
                }
            });
            return r;
        },
        
        /**
         * 
         * @param {type} id
         * @param {type} key
         * @returns {@exp;selector|@exp;seed@pro;length|@exp;selector@call;slice|String|@exp;compiled@pro;selector|@exp;selector@call;replace|@exp;handleObjIn@pro;selector|until|seed.length|compiled.selector|handleObjIn.selector|@exp;type|@exp;type@call;slice|@exp;callback|@exp;props|@exp;params|@arr;@this;|@exp;data|@exp;speed|@exp;options|Array|@exp;props@call;split|@exp;jQuery@call;param|@exp;query@call;split|@exp;jQuery@call;makeArray|@exp;selectorundefined|@exp;options@pro;duration|@exp;_@call;extend|options|@exp;s@call;join@call;replace|selectorundefined|_@call;extend.duration|options.duration}Get data by id
         */
        getDataById: function(value, key){    
            var result;
            
            if(!key){
                key = 'id'
            }
            $.each(this.getApi().data(),function(i, data){
                if(value === data[key]){
                    result = data;
                    return false
                }
            });
            return result;
        },
        
        /**
         * 
         * @param {type} dom
         * @returns {undefined}
         */
        getDomRowByData: function(data){
           var tableApi = this.getApi();
           var rows = tableApi.rows().nodes();
           var result;

           $.each(rows, function(i, domRow){
                var tr = $(domRow);
                var row = tableApi.row( tr );
                if( row.data() == data){
                    result = tr;
                    return false;
                }
           });
           
           return result;
        },
        
        /**
         * Show by DOM row
         * @return int page number
         */
        showByRow: function(domRow){
            var tableApi =  this._dataTable;
            var rowsOnOnePage = tableApi.page.len();

            if(domRow.hasOwnProperty('length')){
                domRow = domRow[0]
            }
            
            var nodePosition = tableApi.rows({order: 'current'}).nodes().indexOf(domRow);
            var pageNumber = Math.floor(nodePosition / rowsOnOnePage);
            tableApi.page(pageNumber).draw( false );
            return pageNumber;
        },
    });

})(jQuery);
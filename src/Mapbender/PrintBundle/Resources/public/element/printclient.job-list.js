$.widget("mapbender.mbPrintClientJobList", {
    options: {
        locale: null,
        url: null
    },
    reloadEnabled: false,
    currentReloadInterval: null,
    queueRefreshDelay: 3000,
    $table: null,

    _create: function() {
        this.$table = $('table', this.element).first();
    },
    start: function() {
        this._refresh(this.$table, false);
    },
    stop: function() {
        this.reloadEnabled = false;
        if (this.currentReloadInterval) {
            this.currentReloadInterval = clearTimeout(this.currentReloadInterval);
        }
    },

    _refresh: function($table, once) {
        var firstLoad = !this._hasTableApi($table);
        if (typeof once !== 'undefined') {
            this.reloadEnabled = !once;
        }
        var callback;
        if (!this.reloadEnabled) {
            this.currentReloadInterval = clearTimeout(this.currentReloadInterval);
            callback = this._noop;
        } else {
            callback = function() {
                // just in case there are concurrent refresh loops going on, cancel them
                this.currentReloadInterval = clearTimeout(this.currentReloadInterval);
                if (this.reloadEnabled) {
                    // schedule next reload (call to same method)
                    this.currentReloadInterval = setTimeout(this._refresh.bind(this, $table, undefined), this.queueRefreshDelay);
                }
            }.bind(this);
        }
        if (firstLoad) {
            // We're freshly initializing our data table. This triggers the Ajax reload automatically.
            // We just have to set our callback to be invoked once after it's drawn.
            // This prevents an immediately canceled Ajax request, and also delays timer looping
            // until the first request has returned (which may take a long time on app initialization, depending
            // on layers / other elements fetching data etc)
            $table.one('draw.dt', callback);
            this._getTableApi($table);
        } else {
            this._getTableApi($table).ajax.reload(callback);
        }
    },
    _hasTableApi: function($table) {
        // @see https://datatables.net/reference/api/$.fn.dataTable.isDataTable()
        return $.fn.DataTable.isDataTable($table);
    },
    _getTableApi: function($table) {
        if (!this._hasTableApi($table)) {
             var self = this;
             var columns = ['id', 'ctime', 'status', 'interface'].map(function(name, i) {
                 var column = {
                     targets: i,
                     // className: name,
                     data: null,
                     searchable: false,
                     sortable: name === 'ctime'
                 };
                 switch(name) {
                     case 'ctime':
                         column.render = function (val, type, row, meta) {
                             if (type !== 'display') {
                                 return val;
                             }
                             var date = new Date(row['ctime']* 1000);
                             return [
                                 date.toLocaleDateString(self.options.locale),
                                 date.toLocaleTimeString(self.options.locale)
                             ].join(' ');
                         };
                         break;
                     case 'interface':
                         column.render = function(val, type, row, meta) {
                             if (type !== 'display') {
                                 return null;
                             }
                             return self._renderInterface(row);
                         };
                         break;
                     case 'status':
                         column.render = function(val, type, row, meta) {
                             if (type !== 'display') {
                                 return null;
                             }
                             return Mapbender.trans(row['status']);
                         };
                         break;
                     default:
                         column.render = function (val, type, row, meta) {
                             if (type !== 'display') {
                                 return row[name];
                             }
                             return '' + row[name];
                         };
                         break;
                 }
                 return column;
             });
             $table.DataTable({
                 ajax:       {
                     url: this.options.url,
                     dataSrc: "",
                     type: "GET"
                 },
                 paging:     false,
                 searching:  false,
                 info:       false,
                 autoWidth:  false,
                 columnDefs: columns,
                 language: {
                     "loadingRecords" : Mapbender.trans('mb.print.printclient.joblist.loading'),
                     "emptyTable" : Mapbender.trans('mb.print.printclient.joblist.nodata')
                 }
             });
        }
        return $table.dataTable().api();
    },
    _renderInterface: function (row) {
        if (row.downloadUrl) {
            var $a = $('<a />')
                .attr('href', row.downloadUrl)
                .attr('target', '_blank')
                .attr('title', Mapbender.trans('mb.print.printclient.joblist.open'))
            ;
            var $icon = $('<i/>').addClass('fa fa-file-pdf-o');
            $a.append($icon);
            return $a.get(0).outerHTML;
        }
        return '<span><i class="fa fa-cog fa-spin" /></span>';
    },
    _noop: function() {}
});




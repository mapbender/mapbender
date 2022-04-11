$.widget("mapbender.mbPrintClientJobList", {
    options: {
        locale: null,
        url: null
    },
    reloadEnabled: false,
    resumeState: false,
    currentReloadInterval: null,
    queueRefreshDelay: 3000,
    $table: null,

    _create: function() {
        this.$table = $('table', this.element).first();
        this.$table.on('click', '.-fn-delete', this._deleteHandler.bind(this));
    },
    start: function() {
        this.resumeState = true;
        this._refresh(this.$table, false);
    },
    stop: function() {
        this.resumeState = false;
        this._stop();
    },
    pause: function() {
        this.resumeState = this.reloadEnabled;
        this._stop();
    },
    resume: function() {
        if (this.resumeState) {
            this.start();
        }
    },
    _stop: function() {
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
                     orderarble: name === 'ctime',
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
                         column.className = 'interface';
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
                 ordering: false,
                 order: [1, 'desc'],
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
        var loader = null;
        var $icon;

        var buttonsEmpty = true;
        var $group = $(document.createElement('div'))
            .addClass('btn-group btn-group-xs')
        ;
        if (row.downloadUrl) {
            var $a = $('<a />')
                .addClass('btn btn-default')
                .attr('href', row.downloadUrl)
                .attr('target', '_blank')
                .attr('title', Mapbender.trans('mb.print.printclient.joblist.open'))
            ;
            $icon = $('<i/>').addClass('fa fa-file-pdf-o');
            $a.append($icon);
            $group.append($a);
            buttonsEmpty = false;
        } else {
            loader = '<span class="loading"><i class="fa fa-cog fa-spin"></i></span>';
        }
        if (row.deleteUrl) {
            var deleteTitle = row.downloadUrl
                ? 'mb.print.printclient.joblist.delete'
                : 'mb.print.printclient.joblist.cancel'
            ;
            var $deleteSpan = $('<span />')
                .addClass('-fn-delete')
                .addClass('btn btn-default')
                .attr('data-url', row.deleteUrl)
                .attr('data-id', row.id)
                .attr('title', Mapbender.trans(deleteTitle))
            ;
            $icon = $('<i/>').addClass('fa fa-remove');
            $deleteSpan.append($icon);
            $group.append($deleteSpan);
            buttonsEmpty = false;
        }
        var html = loader || '';
        if (!buttonsEmpty) {
            html = [html, $group.get(0).outerHTML].join('');
        }
        return html;
    },
    _deleteHandler: function(evt) {
        var $button = $(evt.currentTarget);
        var $tr = $button.closest('tr');
        this.stop();
        $tr.animate({opacity: 0.0});
        $.ajax({
            url: $button.attr('data-url'),
            data: {
                id: $button.attr('data-id')
            },
            method: 'POST'
        }).then(function() {
            $tr.remove();
        }).always(this.start.bind(this));
    },
    _noop: function() {}
});




class MbPrintJobList {

    constructor(configuration, $element) {
        this.options = configuration;
        this.$element = $element;
        this.reloadEnabled = false;
        this.resumeState = false;
        this.currentReloadInterval = null;
        this.queueRefreshDelay = 3000;
        this.$table = $('table', this.$element).first();
        this.$table.on('click', '.-fn-delete', this._deleteHandler.bind(this));
    }

    start() {
        this.resumeState = true;
        this._refresh(this.$table, false);
    }

    stop() {
        this.resumeState = false;
        this._stop();
    }

    pause() {
        this.resumeState = this.reloadEnabled;
        this._stop();
    }

    resume() {
        if (this.resumeState) {
            this.start();
        }
    }

    _stop() {
        this.reloadEnabled = false;
        if (this.currentReloadInterval) {
            this.currentReloadInterval = clearTimeout(this.currentReloadInterval);
        }
    }

    _refresh($table, once) {
        var firstLoad = !this._hasTableApi($table);
        if (typeof once !== 'undefined') {
            this.reloadEnabled = !once;
        }
        var callback;
        if (!this.reloadEnabled) {
            this.currentReloadInterval = clearTimeout(this.currentReloadInterval);
            callback = function() {};
        } else {
            callback = function () {
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
    }

    _hasTableApi($table) {
        // @see https://datatables.net/reference/api/$.fn.dataTable.isDataTable()
        return $.fn.DataTable.isDataTable($table);
    }

    _getTableApi($table) {
        if (!this._hasTableApi($table)) {
            var self = this;
            var columns = ['id', 'ctime', 'status', 'interface'].map(function (name, i) {
                var column = {
                    targets: i,
                    orderarble: name === 'ctime',
                    data: null,
                    searchable: false,
                    sortable: name === 'ctime'
                };
                switch (name) {
                    case 'ctime':
                        column.render = function (val, type, row, meta) {
                            if (type !== 'display') {
                                return val;
                            }
                            var date = new Date(row['ctime'] * 1000);
                            return [
                                date.toLocaleDateString(self.options.locale),
                                date.toLocaleTimeString(self.options.locale)
                            ].join(' ');
                        };
                        break;
                    case 'interface':
                        column.className = 'interface';
                        column.render = function (val, type, row, meta) {
                            if (type !== 'display') {
                                return null;
                            }
                            return self._renderInterface(row);
                        };
                        break;
                    case 'status':
                        column.render = function (val, type, row, meta) {
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
                ajax: {
                    url: this.options.url,
                    dataSrc: "",
                    type: "GET"
                },
                paging: false,
                searching: false,
                ordering: false,
                order: [1, 'desc'],
                info: false,
                autoWidth: false,
                columnDefs: columns,
                language: {
                    "loadingRecords": Mapbender.trans('mb.print.printclient.joblist.loading'),
                    "emptyTable": Mapbender.trans('mb.print.printclient.joblist.nodata')
                }
            });
        }
        return $table.dataTable().api();
    }

    _renderInterface(row) {
        var $icon;

        var buttonsEmpty = true;
        var $group = $(document.createElement('div'))
            .addClass('mb-element-printclient-icon-wrapper');
        if (row.downloadUrl) {
            var $a = $('<a />')
                .addClass('hover-highlight-effect')
                .attr('href', row.downloadUrl)
                .attr('target', '_blank')
                .attr('tabindex', '0')
                .attr('role', 'button')
                .attr('title', Mapbender.trans('mb.print.printclient.joblist.open'))
            ;
            $icon = $('<i/>').addClass('far fa-file-pdf fa-lg');
            $a.append($icon);
            $group.append($a);
            buttonsEmpty = false;
        } else {
            const loaderHtml = '<span class="loading"><i class="fas fa-gear fa-spin fa-lg"></i></span>';
            $group.append(loaderHtml);
        }
        if (row.deleteUrl) {
            var deleteTitle = row.downloadUrl
                ? 'mb.print.printclient.joblist.delete'
                : 'mb.print.printclient.joblist.cancel'
            ;
            var $deleteSpan = $('<span />')
                .addClass('-fn-delete hover-highlight-effect')
                .attr('tabindex', '0')
                .attr('role', 'button')
                .attr('data-url', row.deleteUrl)
                .attr('data-id', row.id)
                .attr('title', Mapbender.trans(deleteTitle))
            ;
            $icon = $('<i/>').addClass('far fa-trash-can');
            $deleteSpan.append($icon);
            $group.append($deleteSpan);
            buttonsEmpty = false;
        }
        return buttonsEmpty ? '' : $group.get(0).outerHTML;
    }

    _deleteHandler(evt) {
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
        }).then(function () {
            $tr.remove();
        }).always(this.start.bind(this));
    }
}

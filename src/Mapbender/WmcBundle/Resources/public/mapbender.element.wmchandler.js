(function($) {
    $.widget("mapbender.mbWmcHandler", {
        options: {},
        elementUrl: null,
        currentElm: null,
        editorElm: null,
        loaderElm: null,
        suggestElm: null,
        _create: function() {
            if (!Mapbender.checkTarget("mbWmcHandler", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function() {
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if (this.options.useEditor === true) {
                this.editorElm = this.element.find("#wmceditor");
                this.editorElm.hide().appendTo($('body'));
                $(this.editorElm).find('#wmc-new').bind("click", $.proxy(self._editorLoadForm, self));
                $(this.editorElm).find('#tab-wmc-load').bind("click", function(e) {
                    self._editorLoadList();
                });
            }
            if (this.options.useSuggestMap === true) {
                this.suggestElm = this.element.find("#suggestmap");
                this.suggestElm.hide().appendTo($('body'));
                this.suggestElm.find('#suggestmap-email').bind("click", $.proxy(self._suggestState, self));
            }
            if (this.options.useLoader === true) {
                this.loaderElm = this.element.find("#wmcloader");
                this.loaderElm.hide().appendTo($('body'));
            }
            if (typeof this.options.load !== 'undefined') {
                if (typeof this.options.load.wmc !== 'undefined') {
                    this._loadFromId(this.options.load.wmc, "wmc");
                } else if (typeof this.options.load.state !== 'undefined') {
                    this.loadFromId(this.options.load.state, "state");
                }
            }

        },
        close: function() {
            if (this.currentElm)
                this.currentElm.hide().appendTo($('body'));
            this.currentElm = null;
            $("body").mbPopup("close");
        },
        _editorAjaxForm: function() {
            if (!this.options.useEditor)
                return;
            var self = this;
            this.editorElm.find('form').ajaxForm({
                url: self.elementUrl + 'save',
                type: 'POST',
                beforeSerialize: $.proxy(self._editorBeforeSave, self), 
                contentType: 'json',
                context: self,
                success: self._editorWmcSeccess,
                error: self._editorWmcError
            });
        },
        _editorLoadForm: function(id) {
            if (!this.options.useEditor)
                return;
            var self = this;
            if (id instanceof jQuery.Event || id === null)
                id = "";
            self.editorElm.find("#container-wmc-form").load(self.elementUrl + "get", {wmcid: id}, function() {
                self._editorAjaxForm();
            });

            if (id !== "") {
                this.removeWmcFromMap();
                this.loadFromId(id, "wmc");
            }
        },
        _editorLoadList: function() {
            if (!this.options.useEditor)
                return;
            var self = this;
            self.editorElm.find("#container-wmc-load").load(this.elementUrl + "list", function() {
                self.editorElm.find("#container-wmc-load .iconEdit").bind("click", function() {
                    self._editorLoadForm($(this).attr("data-id"));
                    self.editorElm.find('#tab-wmc-edit').click();
                    return true;
                });
                self.editorElm.find("#container-wmc-load .iconRemove").bind("click", function(e) {
                    var wmcid = $(this).attr("data-id");
                    if (Mapbender.confirm("Remove WMC ID:" + wmcid) === true) {
                        var url = self.elementUrl + 'remove';
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                wmcid: wmcid
                            },
                            dataType: 'json',
                            success: function(data) {
                                if (data.error)
                                    Mapbender.error(data.error);
                                else {
                                    Mapbender.info(data.success);
                                    self._editorLoadList();
                                }
                            },
                            error: function(data) {
                                alert("error")
                            }
                        });
                    }
                    return false;
                });

            });
        },
        openEditor: function() {
            if (!this.options.useEditor) {
                Mapbender.error("A WMC Editor is not available. Configure your WMC Handler to use a WMC Editor.")
                return;
            }
            var self = this;
            this.currentElm = this.editorElm;
            if (!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function() {
                    self.close();
                });
                $("body").mbPopup('showCustom', {
                    title: this.editorElm.attr("title"),
                    showHeader: true,
                    content: this.editorElm,
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: false,
                    overflow: true
                });
                $('#popupContent').css({
                    height: "500px"
                });
                $('#popup').css({
                    width: "400px"
                });
                this.editorElm.show();
                self._editorLoadList();
            }
        },
        _editorBeforeSave: function(e) {
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            this.editorElm.find('form#save-wmc').find('input#wmc_state_json').val(JSON.stringify(state));
        },
        _editorWmcSeccess: function(response) {
            response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
            Mapbender.info(response.success);
            this._editorLoadForm("");
        },
        _editorWmcError: function(response) {
            Mapbender.error(response.error);
        },
        loadFromId: function(id, type) {
            $.ajax({
                url: this.elementUrl + 'load',
                type: 'POST',
                data: {
                    _id: id,
                    type: type
                },
                dataType: 'json',
                contetnType: 'json',
                context: this,
                success: this._loadFromIdSuccess,
                error: this._loadFromIdError
            });
            return false;
        },
        _addToMap: function(wmcid, state) {
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if (widget.length == 1) {
                widget = widget[0];
            } else {
                widget = widget[1];
            }
            var model = target[widget]("getModel");
            var wmcProj = model.getProj(state.extent.srs),
                    mapProj = model.map.olMap.getProjectionObject();
            if (wmcProj === null) {
                Mapbender.error('SRS "' + state.extent.srs + '" is not supported by this application.');
            } else if (wmcProj.projCode === mapProj.projCode) {
                var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                this.removeWmcFromMap();
                target[widget]("removeAllSources", !this.options.keepBaseSources);
                this._addWmcToMap(wmcid, state);
            } else {
                model.changeProjection({
                    projection: wmcProj
                });
                var boundsAr = [state.extent.minx, state.extent.miny, state.extent.maxx, state.extent.maxy];
                target[widget]("zoomToExtent", OpenLayers.Bounds.fromArray(boundsAr));
                this.removeWmcFromMap();
                target[widget]("removeAllSources", !this.options.keepBaseSources);
                this._addWmcToMap(wmcid, state);
            }
        },
        _loadFromIdSuccess: function(response, textStatus, jqXHR) {
            if (response.data) {
                //                var wmcstate = $.parseJSON(response.data);
                for (stateid in response.data) {
                    var state = $.parseJSON(response.data[stateid]);
                    if (!state.window)
                        state = $.parseJSON(state);
                    this._addToMap(stateid, state);
                }
            } else if (response.error) {
                Mapbender.error(response.error);
            }
        },
        _loadFromIdError: function(response) {
            Mapbender.error(response);
        },
        removeWmcFromMap: function() {
            if (this.sources_wmc !== null) {
                var target = $('#' + this.options.target);
                var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
                if (widget.length == 1) {
                    widget = widget[0];
                } else {
                    widget = widget[1];
                }
                var model = target[widget]("getModel");
                for (stateid in this.sources_wmc) {
                    for (var i = 0; i < this.sources_wmc[stateid].sources.length; i++) {
                        var source = this.sources_wmc[stateid].sources[i];
                        if (!source.configuration.isBaseSource || (source.configuration.isBaseSource && !this.options.keepBaseSources)) {
                            var toremove = model.createToChangeObj(source);
                            model.removeSource(toremove);
                        }
                    }
                }
            }
        },
        _addWmcToMap: function(wmcid, sources) {
            this.removeWmcFromMap();
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init.split('.');
            if (widget.length == 1) {
                widget = widget[0];
            } else {
                widget = widget[1];
            }
            this.sources_wmc = {};
            this.sources_wmc[wmcid] = sources;
            for (var i = 0; i < this.sources_wmc[wmcid].sources.length; i++) {
                var source = this.sources_wmc[wmcid].sources[i];
                if (!source.configuration.isBaseSource || (source.configuration.isBaseSource && !this.options.keepBaseSources)) {
                    target[widget]("addSource", source);
                }
            }
        },
        openSuggestMap: function() {
            if (!this.options.useSuggestMap) {
                Mapbender.error("A Suggest Map is not available. Configure your WMC Handler to use a Suggest map.")
                return;
            }
            var self = this;
            this.currentElm = this.suggestElm;
            if (!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function() {
                    self.close();
                });
                $("body").mbPopup('showCustom', {
                    title: self.currentElm.attr("title"),
                    showHeader: true,
                    content: self.currentElm,
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: false,
                    overflow: true
                });
                this.currentElm.show();
            }
        },
        _suggestState: function(e) {
            var self = this;
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            var stateSer = JSON.stringify(state);
            $.ajax({
                url: self.elementUrl + 'state',
                type: 'POST',
                data: {
                    state: stateSer
                },
                dataType: 'json',
                contetnType: 'json',
                context: self,
                success: self._suggestStateSuccess,
                error: self._suggestStateError
            });
            return false;
        },
        _suggestEmail: function(e) {

        },
        _suggestStateSuccess: function(response, textStatus, jqXHR) {
            if (response.id) {
                var help = document.location.href.split("?");
                var url = help[0];
                url = url.replace(/#/gi, '') + "?state=" + response.id;
                var mail_cmd = "mailto:?subject=" + this.suggestElm.find('#suggestmap-email').attr("data-subject") + "&body=" + encodeURIComponent(url);
                document.location.href = mail_cmd;
            } else if (response.error) {
                Mapbender.error(response.error);
            }
        },
        _suggestStateError: function() {
            Mapbender.error(response);
        },
        openLoader: function() {
            if (!this.options.useEditor) {
                Mapbender.error("A WMC Loader is not available. Configure your WMC Handler to use a WMC Loader.")
                return;
            }
            var self = this;
            this.currentElm = this.loaderElm;
            if (!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Load", "button right", function() {
                    self.loaderElm.find('form').submit();
                }).mbPopup("addButton", "Cancel", "button buttonCancel critical right", function() {
                    self.close();
                }).mbPopup('showCustom', {
                    title: this.loaderElm.attr("title"),
                    showHeader: true,
                    content: this.loaderElm,
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: true,
                    overflow: true
                });
                this.loaderElm.show();
                this._loaderAjaxForm();
            }
        },
        _loaderAjaxForm: function() {
            if (!this.options.useEditor)
                return;
            var self = this;
            this.loaderElm.find('form').ajaxForm({
                url: self.elementUrl + 'loadxml',
                type: 'POST',
                contentType: 'json',
                context: self,
                success: self._loaderWmcSeccess,
                error: self._loaderWmcError
            });
        },
        _loaderWmcSeccess: function(response) {
            response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
            if (response.data) {
                //                var wmcstate = $.parseJSON(response.data);
                for (stateid in response.data) {
                    var state = response.data[stateid];
                    if (!state.window)
                        state = $.parseJSON(state);
                    this._addToMap(stateid, state);
                    this.close();
                }
            } else if (response.error) {
                Mapbender.error(response.error);
            }
        },
        _loaderWmcError: function(response) {
            Mapbender.error(response.error);
        },
        loaderCreateWmc: function() {
            var self = this;
            var map = $('#' + self.options.target).data('mbMap');
            var state = map.getMapState();
            this.loaderElm.find('form input#wmcload_state').val(JSON.stringify(state));
            this.loaderElm.find('form').submit();

        },
        _destroy: $.noop
    });

})(jQuery);
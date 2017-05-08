(function($) {

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {
        },
        scalebar: null,
        loadStarted: null,
        contextAddStart: false,
        _create: function() {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcher", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            var self = this;
            this.loadStarted = {};
            $('.basesourcesetswitch:not(.basesourcegroup)', this.element).on('click', $.proxy(self._toggleMapset, self));
            $('.basesourcegroup', this.element).on('click', function(e) {
                var bsswtch = $('.basesourcesubswitcher', $(e.currentTarget));
                $('.basesourcesubswitcher', $(self.element)).addClass('hidden');
                if (bsswtch.hasClass('hidden')) {
                    bsswtch.removeClass('hidden');
                } else {
                    bsswtch.addClass('hidden');
                }
            });
            this._hideSources();
            this._showActive();
            this.element.find('.basesourcesetswitch:first').click();
            $(document).on('mbmapcontextaddstart', $.proxy(self._onContextAddStart, self));
        },
        _hideSources: function() {
            var me = $(this.element);
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var model = map.getModel();
            $.each(me.find('.basesourcesetswitch'), function(idx, elm) {
                $(elm).attr("data-state", "");
                var sourcesIds = $(elm).attr("data-sourceset").split(",");
                for (var i = 0; i < sourcesIds.length; i++) {
                    if(sourcesIds[i] !== '') {
                        var source_list = model.findSource({origId: sourcesIds[i]});
                        if(source_list.length === 0) {
                            Mapbender.error(Mapbender.trans("mb.core.basesourceswitcher.error.sourcenotavailable")
                                .replace('%id%', sourcesIds[i]), {'id': sourcesIds[i]});
                        }
                        for (var j = 0; j < source_list.length; j++) {
                            var tochange = {
                                change: {
                                    sourceIdx: {id: source_list[j].id},
                                    options: {
                                        configuration: {
                                            options: {visibility: false}
                                        },
                                        type: 'selected'
                                    }
                                }
                            };
                            model.changeSource(tochange);
                        }
                    }
                }
            });
        },
        _showActive: function() {
            var me = $(this.element);
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var model = map.getModel();
            $.each(me.find('.basesourcesetswitch[data-state="active"]'), function(idx, elm) {
                var sourcesIds = $(elm).attr("data-sourceset").split(",");
                for (var i = 0; i < sourcesIds.length; i++) {
                    if (sourcesIds[i] !== '') {
                        var source_list = model.findSource({origId: sourcesIds[i]});
                        for (var j = 0; j < source_list.length; j++) {
                            var tochange = {
                                change: {
                                    sourceIdx: {id: source_list[j].id},
                                    options: {
                                        configuration: {
                                            options: {visibility: true}
                                        },
                                        type: 'selected'
                                    }
                                }
                            };
                            model.changeSource(tochange);
                        }
                    }
                }
            });
        },
        _toggleMapset: function(event) {
            var me = $(this.element);
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var a = $(event.currentTarget);
            this._hideSources();
            me.find('.basesourcesetswitch,.basesourcegroup').not(a).attr('data-state', '');
            a.attr('data-state', 'active');
            a.parents('.basesourcegroup:first').attr('data-state', 'active');//.addClass('hidden');
            a.parents('.basesourcesubswitcher:first').addClass('hidden');
            if(a.hasClass('notgroup')){
                $('.basesourcesubswitcher', me).addClass('hidden');
            }
            this._showActive();
            return false;
        },
        _onSourceLoadStart: function(event, options) {
            if (this.contextAddStart && options.source) {
                this.loadStarted[options.source.id ] = true;
            }
        },
        _onSourceLoadEnd: function(event, option) {
            if (option.source && this.loadStarted[option.source.id]) {
                delete(this.loadStarted[option.source.id]);
                this._checkReset();
            }
        },
        _onSourceLoadError: function(event, option) {
            if (option.source && this.loadStarted[option.source.id]) {
                delete(this.loadStarted[option.source.id]);
                this._checkReset();
            }
        },
        _onContextAddStart: function(e){
            this.contextAddStart = true;
            $(document).on('mbmapcontextaddend', $.proxy(this._onContextAddEnd, this));
            $(document).on('mbmapsourceloadstart', $.proxy(this._onSourceLoadStart, this));
            $(document).on('mbmapsourceloadend', $.proxy(this._onSourceLoadEnd, this));
            $(document).on('mbmapsourceloaderror', $.proxy(this._onSourceLoadError, this));
        },
        _onContextAddEnd: function(e){
            this._checkReset();
        },
        _checkReset: function(){
            for(var id in this.loadStarted){
                return;
            }
            this.contextAddStart = false;
            $(document).off('mbmapcontextaddend', $.proxy(this._onContextAddEnd, this));
            $(document).off('mbmapsourceloadstart', $.proxy(this._onSourceLoadStart, this));
            $(document).off('mbmapsourceloadend', $.proxy(this._onSourceLoadEnd, this));
            $(document).off('mbmapsourceloaderror', $.proxy(this._onSourceLoadError, this));
            $('.basesourcesetswitch[data-state="active"]:not(.basesourcegroup)', this.element).click();
        },
        /**
         *
         */
        ready: function(callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy: $.noop
    });

})(jQuery);
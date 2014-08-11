(function($) {

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {
        },
        scalebar: null,
        _create: function() {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcher", this.options.target)) {
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            var self = this;
            $('.basesourcesetswitch', this.element).click($.proxy(self._toggleMapset, self));
            $('.basesourcegroup', this.element).click(function(e) {
                var bsswtch = $('.basesourcesubswitcher', $(e.currentTarget));
                var substate = bsswtch.hasClass('hidden');
                $('.basesourcesubswitcher', $(self.element)).addClass('hidden');
                if (substate) {
                    $('.basesourcesubswitcher', $(this)).removeClass('hidden');
                } else {
                    $('.basesourcesubswitcher', $(this)).addClass('hidden');
                }
            });
            this._hideSources();
            this._showActive();
        },
        _hideSources: function() {
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    model = map.getModel();
            $.each(me.find('.basesourcesetswitch'), function(idx, elm) {
                var sourcesIds = $(elm).attr("data-sourceset").split(",");
                for (var i = 0; i < sourcesIds.length; i++) {
                    if (sourcesIds[i] !== '') {
                        var source = model.getSource({origId: sourcesIds[i]});
                        if (source) {
                            var tochange = {change: {sourceIdx: {id: source.id}, options: {configuration: {options: {visibility: false}}, type: 'selected'}}};
                            model.changeSource(tochange);
                        } else {
                            Mapbender.error(Mapbender.trans("mb.core.basesourceswitcher.error.sourcenotavailable", {'id': +sourcesIds[i]}));
                        }
                    }
                }
            });
        },
        _showActive: function() {
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    model = map.getModel();
            $.each(me.find('.basesourcesetswitch[data-state="active"]'), function(idx, elm) {
                var sourcesIds = $(elm).attr("data-sourceset").split(",");
                for (var i = 0; i < sourcesIds.length; i++) {
                    if (sourcesIds[i] !== '') {
                        var source = model.getSource({origId: sourcesIds[i]});
                        if (source) {
                            var tochange = {change: {sourceIdx: {id: source.id}, options: {configuration: {options: {visibility: true}}, type: 'selected'}}};
                            model.changeSource(tochange);
                        }
                    }
                }
            });
        },
        _toggleMapset: function(event) {
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    a = $(event.currentTarget);
            this._hideSources();
            me.find('.basesourcesetswitch,.basesourcegroup').not(a).attr('data-state', '');
            a.attr('data-state', 'active');
            a.parents('.basesourcegroup:first').attr('data-state', 'active').addClass('hidden');
            a.parents('.basesourcesubswitcher:first').addClass('hidden');
            if(a.hasClass('notgroup')){
                $('.basesourcesubswitcher', me).addClass('hidden');
            }
            this._showActive();
            return false;
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
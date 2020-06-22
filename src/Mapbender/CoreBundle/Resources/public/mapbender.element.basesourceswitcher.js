(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {},

        _create: function () {
            if (!Mapbender.checkTarget("mbBaseSourceSwitcher", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function () {
            $('.basesourcesetswitch:not(.basesourcegroup)', this.element).on('click', $.proxy(this._toggleMapset, this));

            $('.basesourcesubswitcher', $(this.element)).addClass('hidden');

            $('.basesourcegroup', this.element).on('mouseenter', $.proxy(this._showMenu, this));
            $('.basesourcegroup', this.element).on('mouseleave', $.proxy(this._hideMenu, this));

            this._showActive();
        },

        _showMenu: function(e) {
            var $bsswtch = $('.basesourcesubswitcher', $(e.currentTarget));

            $bsswtch.removeClass('hidden');
        },

        _hideMenu: function(e) {
            var $bsswtch = $('.basesourcesubswitcher', $(e.currentTarget));

            $bsswtch.addClass('hidden');
        },

        _hideSources: function () {
            var sourceVisibility = false;
            this._changeSource('.basesourcesetswitch', sourceVisibility);
        },

        _showActive: function () {
            var sourceVisibility = true;
            this._changeSource('.basesourcesetswitch[data-state="active"]', sourceVisibility);
        },

        _changeSource: function (selector, visibility) {
            var $me = $(this.element),
                $map = $('#' + this.options.target).data('mapbenderMbMap'),
                model = $map.getModel(),
                switched = false,
                source;

            $me.find(selector).each(function (idx, elm) {
                if (false === visibility) {
                    $(elm).attr("data-state", "");
                }

                var sourcesIds = $(elm).attr("data-sourceset").split(",");

                sourcesIds.map(function (sourcesId) {
                    if (sourcesId.length === 0) {
                        return;
                    }
                    source = model.getSourceById(sourcesId);

                    if (!source) {
                        Mapbender.error(Mapbender.trans("mb.core.basesourceswitcher.error.sourcenotavailable")
                            .replace('%id%', sourcesId), {'id': sourcesId});
                    } else {
                        model.setSourceVisibility(source, visibility);
                        switched = true;
                    }
                });
            });
            if (switched) {
                this._hideMobile();
            }
        },

        _toggleMapset: function (event) {
            var $me = $(this.element),
                $currentTarget = $(event.currentTarget);

            this._hideSources();

            $me.find('.basesourcesetswitch,.basesourcegroup').not($currentTarget).attr('data-state', '');

            $currentTarget.attr('data-state', 'active');
            $currentTarget.parents('.basesourcegroup:first').attr('data-state', 'active');
            $currentTarget.parents('.basesourcesubswitcher:first').addClass('hidden');

            if ($currentTarget.hasClass('notgroup')) {
                $('.basesourcesubswitcher', $me).addClass('hidden');
            }

            this._showActive();
        },

        _hideMobile: function() {
            $('.mobileClose', $(this.element).closest('.mobilePane')).click();
        },

        _dummy_: null
    });

})(jQuery);

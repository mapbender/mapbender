(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {},
        mbMap: null,

        _create: function () {
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbBaseSourceSwitcher", self.options.target)
            });
        },

        _setup: function () {
            var menuItems = $('.basesourcesetswitch[data-sourceset]', this.element).get();
            for (var i = 0; i < menuItems.length; ++i) {
                var menuItem = menuItems[i];
                var $menuItem = $(menuItem);
                var sourceIds = $menuItem.attr('data-sourceset').split(',').filter(function(x) {
                    return !!x;
                });
                var sources = [];
                for (var j = 0; j < sourceIds.length; ++j) {
                    var source = this.mbMap.model.getSourceById(sourceIds[j]);
                    if (source) {
                        if (source.getSelected()) {
                            this._highlight($menuItem, true);
                        }
                        sources.push(source);
                    } else {
                        console.warn("No source with id " + sourceIds[j]);
                    }
                }
                if (sourceIds.length && !sources.length) {
                    console.warn("Removing menu item with entirely invalid source associations", menuItem);
                    $menuItem.remove();
                } else {
                    $menuItem.data('sources', sources);
                }
            }
            $('.basesourcesetswitch', this.element).on('click', $.proxy(this._toggleMapset, this));
        },
        _highlight: function($node, state) {
            if (state) {
                $node.attr('data-state', 'active');
                $node.parentsUntil(this.element, '.basesourcegroup').attr('data-state', 'active');
            } else {
                $node.attr('data-state', null);
                var $group = $node.closest('.basesourcegroup', this.element);
                while ($group.length) {
                    if ($('.basesourcesetswitch[data-state="active"]', $group).length) {
                        break;
                    } else {
                        $group.attr('data-state', null);
                    }
                    $group = $group.parent().closest('.basesourcegroup', this.element);
                }
            }
        },

        _toggleMapset: function (event) {
            var $menuItem = $(event.currentTarget);
            var $others = $('.basesourcesetswitch', this.element).not($menuItem.get(0));
            var sourcesOn = $menuItem.data('sources');
            var sourcesOff = [];
            $others.map(function() {
                sourcesOff = sourcesOff.concat($(this).data('sources'));
            });
            // Sanity...
            sourcesOff = sourcesOff.filter(function(source, index) {
                if (-1 !== sourcesOn.indexOf(source)) {
                    console.warn("Same source is assigned to multiple Base Source Switcher items. Skipping deactivation.", source);
                    return false;
                }
                if (-1 !== sourcesOff.slice(0, index).indexOf(source)) {
                    // occurs multiple times, remove duplicate
                    return false;
                }
                return true;
            });
            // make before break
            var i, self = this;
            this._highlight($menuItem, true);
            for (i = 0; i < sourcesOn.length; ++i) {
                this.mbMap.model.setSourceVisibility(sourcesOn[i], true);
            }
            $others.each(function() {
                self._highlight($(this), false);
            });
            for (i = 0; i < sourcesOff.length; ++i) {
                this.mbMap.model.setSourceVisibility(sourcesOff[i], false);
            }
        },

        _hideMobile: function() {
            $('.mobileClose', $(this.element).closest('.mobilePane')).click();
        },

        _dummy_: null
    });

})(jQuery);

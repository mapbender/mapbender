(function ($) {
    'use strict';

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {},
        mbMap: null,

        _create: function () {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget('mbBaseSourceSwitcher')
            });
        },

        _setup: function () {
            var self = this;
            this.element.on('click', '.basesourcesetswitch', function(evt) {
                self._toggleMapset(evt);
            });
            this.mbMap.element.on('mbmapsourcechanged', function() {
                self.updateHighlights();
            });
            this.updateHighlights();
        },
        updateHighlights: function() {
            var allActiveSources = [];
            var remainingMenuItems = [];
            var i, menuItem, $menuItem;
            var menuItems = $('.basesourcesetswitch[data-sourceset]', this.element).get();
            for (i = 0; i < menuItems.length; ++i) {
                menuItem = menuItems[i];
                $menuItem = $(menuItem);
                var sourceIds = $menuItem.attr('data-sourceset').split(',').filter(function(x) {
                    return !!x;
                });
                var sources = [];
                for (var j = 0; j < sourceIds.length; ++j) {
                    var source = this.mbMap.model.getSourceById(sourceIds[j]);
                    if (source) {
                        if (source.getSelected() && -1 === allActiveSources.indexOf(source)) {
                            allActiveSources.push(source);
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
                    remainingMenuItems.push(menuItem);
                }
            }
            for (i = 0; i < remainingMenuItems.length; ++i) {
                $menuItem = $(remainingMenuItems[i]);
                var itemSources = $menuItem.data('sources');
                var activeSubset = itemSources.filter(function(source) {
                    return -1 !== allActiveSources.indexOf(source);
                });
                // For active highlight all sources associated with the item must be active, and there must NOT be any other
                // active sources also controlled by this base source switcher
                var allAssociatedSourcesActive = activeSubset.length === itemSources.length;
                var noOtherSourcesActive = activeSubset.length === allActiveSources.length;
                this._highlight($menuItem, allAssociatedSourcesActive && noOtherSourcesActive);
            }
        },
        _highlight: function($node, state) {
            // Fake radio button for font size scalability on mobile
            // @todo: use a real radio button..?
            var $fakeRadio = $('>.state-check', $node);
            $node.attr('data-state', state && 'active' || null);
            $('>i', $fakeRadio)
                .toggleClass('fa-circle', !state)
                .toggleClass('fa-dot-circle fa-dot-circle-o', state)
            ;
            if (state) {
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
            // Turn off all other controlled sources except for the ones we want to turn on
            sourcesOff = sourcesOff.filter(function(source, index) {
                if (-1 !== sourcesOn.indexOf(source)) {
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
                this.mbMap.model.setSourceVisibility(sourcesOn[i], true, true);
            }
            $others.each(function() {
                self._highlight($(this), false);
            });
            for (i = 0; i < sourcesOff.length; ++i) {
                this.mbMap.model.setSourceVisibility(sourcesOff[i], false, true);
            }
            this._hideMobile();
        },

        _hideMobile: function() {
            $('.mobileClose', $(this.element).closest('.mobilePane')).click();
        },

        _dummy_: null
    });

})(jQuery);

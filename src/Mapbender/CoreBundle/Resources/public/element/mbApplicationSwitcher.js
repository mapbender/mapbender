;!(function($) {
    "use strict";
    $.widget("mapbender.mbApplicationSwitcher", {
        options: {
            open_in_new_tab: false
        },
        mbMap: null,
        baseUrl: null,
        elementUrl: null,

        _create: function() {
            var self = this;
            this.elementUrl = [Mapbender.configuration.application.urls.element, this.element.attr('id')].join('/');
            this.baseUrl = window.location.href.replace(/(\/application\/).*$/, '$1');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setup(mbMap);
            });
            this._loadGrants();
            this._updateDropoutDirection();
        },
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
        },
        _loadGrants: function() {
            $.ajax([this.elementUrl, 'granted'].join('/')).then((response) => {
                var slugs = response.concat([Mapbender.configuration.application.slug]);
                this._filterGranted(slugs);
            }).fail((e) => Mapbender.handleAjaxError(e, () => this._loadGrants()));
        },
        _initEvents: function() {
            var self = this;
            this.element.on('change', 'select', function() {
                self._switchApplication($(this).val());
            });
            window.addEventListener('resize', function() {
                self._updateDropoutDirection();
            });
        },
        _filterGranted: function(slugs) {
            var $options = $('select option', this.element);
            var optionsCount = $options.length;
            for (var i = 0; i < $options.length; ++i) {
                var value = $options[i].value;
                if (value && -1 === slugs.indexOf(value)) {
                    $options.eq(i).remove();
                    --optionsCount;
                    // Custom dropdown widget support needs separate removal of display element corresponding to option
                    $('.dropdownList [data-value="' + value + '"]', this.element).remove();
                }
            }
            $('select', this.element).trigger('dropdown.changevisual');
            // If we have nothing left to switch to (deleted yaml applications...), remove from DOM
            if (optionsCount <= 1) {
                this.element.remove();
            }
        },
        _switchApplication: function(slug) {
            var targetApplicationUrl = [this.baseUrl, slug].join('');
            var viewParams = this.mbMap.getModel().getCurrentViewParams();
            var targetHash = this.mbMap.getModel().encodeViewParams(viewParams);
            var targetUrl = [targetApplicationUrl, targetHash].join('#');
            if (this.options.open_in_new_tab) {
                window.open(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        },
        _updateDropoutDirection: function() {
            if (this.element.closest('.toolBar').length) {
                var windowWidth = $('html').get(0).clientWidth;
                var node = this.element.get(0);
                var ownWidth = node.clientWidth;
                var distanceLeft = 0;
                do {
                    distanceLeft += node.offsetLeft;
                    node = node.offsetParent;
                } while (node);
                var distanceRight = windowWidth - distanceLeft - ownWidth;
                if (windowWidth && distanceRight >= windowWidth / 2) {
                    // Extend dropout list into free space to the right
                    $('.dropdownList', this.element).css({
                        left: 0,
                        right: ''
                    });
                } else {
                    // Extend dropout list into free space to the left
                    $('.dropdownList', this.element).css({
                        left: '',
                        right: 0
                    });
                }
            }
        },
        __dummy__: null
    });
})(jQuery);

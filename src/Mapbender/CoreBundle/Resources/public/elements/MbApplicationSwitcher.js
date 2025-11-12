(function() {

    class MbApplicationSwitcher extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.elementUrl = [Mapbender.configuration.application.urls.element, this.$element.attr('id')].join('/');
            this.baseUrl = window.location.href.replace(/(\/application\/).*$/, '$1');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            });
            this._updateDropoutDirection();
        }

        _setup(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
        }

        _initEvents() {
            this.$element.on('change', 'select', (e) => {
                this._switchApplication($(e.currentTarget).val());
            });
            window.addEventListener('resize', () => {
                this._updateDropoutDirection();
            });
        }

        _switchApplication(slug) {
            var targetApplicationUrl = [this.baseUrl, slug].join('');
            var viewParams = this.mbMap.getModel().getCurrentViewParams();
            var targetHash = this.mbMap.getModel().encodeViewParams(viewParams);
            var targetUrl = [targetApplicationUrl, targetHash].join('#');
            if (this.options.open_in_new_tab) {
                window.open(targetUrl);
            } else {
                window.location.href = targetUrl;
            }
        }

        _updateDropoutDirection() {
            if (this.$element.closest('.toolBar').length) {
                var windowWidth = $('html').get(0).clientWidth;
                var node = this.$element.get(0);
                var ownWidth = node.clientWidth;
                var distanceLeft = 0;
                do {
                    distanceLeft += node.offsetLeft;
                    node = node.offsetParent;
                } while (node);
                var distanceRight = windowWidth - distanceLeft - ownWidth;
                if (windowWidth && distanceRight >= windowWidth / 2) {
                    // Extend dropout list into free space to the right
                    $('.dropdownList', this.$element).css({
                        left: 0,
                        right: ''
                    });
                } else {
                    // Extend dropout list into free space to the left
                    $('.dropdownList', this.$element).css({
                        left: '',
                        right: 0
                    });
                }
            }
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbApplicationSwitcher = MbApplicationSwitcher;
})();

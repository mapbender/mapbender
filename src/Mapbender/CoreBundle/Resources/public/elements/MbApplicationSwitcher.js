(function() {

    class MbApplicationSwitcher extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.elementUrl = [Mapbender.configuration.application.urls.element, this.$element.attr('id')].join('/');
            this.baseUrl = window.location.href.replace(/(\/application\/).*$/, '$1');
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            });
        }

        _setup(mbMap) {
            this.mbMap = mbMap;
            this._initEvents();
        }

        _initEvents() {
            $('a', this.$element).on('click', (e) => {
                e.preventDefault();
                this._switchApplication($(e.currentTarget).attr('href'));
            });
        }

        _switchApplication(url) {
            url = this.replacePlaceholders(url);
            if (this.options.open_in_new_tab) {
                window.open(url);
            } else {
                window.location.href = url;
            }
        }

        replacePlaceholders(url) {
            const viewParams = this.mbMap.getModel().getCurrentViewParams();
            const center = ol.proj.transform(viewParams.center, viewParams.srsName, 'EPSG:4326');
            url = url.replaceAll('%scale%', parseInt(viewParams.scale));
            url = url.replaceAll('%lat%', center[0]);
            url = url.replaceAll('%lon%', center[1]);
            url = url.replaceAll('%center_x%', viewParams.center[0]);
            url = url.replaceAll('%center_y%', viewParams.center[1]);
            url = url.replaceAll('%rotation%', viewParams.rotation);
            url = url.replaceAll('%srs%', viewParams.srsName.toLowerCase().replace('epsg:', ''));
            url = url.replaceAll('%zoom%', this.mbMap.getModel().pickZoomForScale(viewParams.scale));
            return url;
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbApplicationSwitcher = MbApplicationSwitcher;
})();

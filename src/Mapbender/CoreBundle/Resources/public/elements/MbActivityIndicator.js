(function() {

    class MbActivityIndicator extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.ajaxActivity = false;
            this.tileActivity = false;
            this.loadingLayers = [];

            const $mbMap = $('.mb-element-map');
            if ($mbMap.length) {
                this._setupMap({element: $mbMap.eq(0)});
            } else {
                Mapbender.checkTarget('mbActivityIndicator');
            }
            this.$element.on('ajaxStart', this._onAjaxStart.bind(this));
            this.$element.on('ajaxStop', this._onAjaxStop.bind(this));
        }

        _setupMap(mbMap) {
            const self = this;
            mbMap.element.on('mbmapsourceloadstart', function(event, data) {
                const source = data.source;
                self.loadingLayers.push(source);
                self._onLayerLoadChange();
            });
            mbMap.element.on('mbmapsourceloadend mbmapsourceremoved mbmapsourceloaderror', function(event, data) {
                const source = data.source;
                self.loadingLayers = self.loadingLayers.filter(function(x) {
                    return x !== source;
                });
                self._onLayerLoadChange();
            });
        }

        /**
         * Listener for global ajaxStart events
         */
        _onAjaxStart() {
            this.ajaxActivity = true;
            this._updateBodyClass();
        }

        /**
         * Listener for global ajaxStop events
         */
        _onAjaxStop() {
            this.ajaxActivity = false;
            this._updateBodyClass();
        }

        _onLayerLoadChange() {
            const stillLoading = this.loadingLayers.length > 0;

            if (stillLoading !== this.tileActivity) {
                this.tileActivity = stillLoading;
                this._updateBodyClass();
            }
        }

        /**
         * Update body classes to match current activity
         */
        _updateBodyClass() {
            const $body = $('body');
            $body.toggleClass(this.options.ajaxActivityClass, this.ajaxActivity);
            $body.toggleClass(this.options.titleActivityClass, this.tileActivity);
            $body.toggleClass(this.options.activityClass, this.tileActivity || this.ajaxActivity);
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbActivityIndicator = MbActivityIndicator;

})();

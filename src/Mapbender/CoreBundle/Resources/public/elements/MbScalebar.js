(function () {
    class MbScalebar extends MapbenderElement {

        constructor(configuration, $element) {
            super(configuration, $element);

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, () => {
                Mapbender.checkTarget('mbScalebar');
            });
        }

        _setup() {
            var control = new ol.control.ScaleLine({
                target: $('.control-container', this.$element).get(0),
                minWidth: '' + Math.max(1, parseInt(this.options.maxWidth) / 3),
                geodesic: true,
                units: this.options.units === 'ml' ? 'imperial' : 'metric'
            });
            this.mbMap.getModel().olMap.addControl(control);
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbScalebar = MbScalebar;
})();

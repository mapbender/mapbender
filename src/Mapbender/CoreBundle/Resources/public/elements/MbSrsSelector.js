(function() {

    class MbSrsSelector extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.$select = $('select', this.$element);
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, function() {
                Mapbender.checkTarget('mbSrsSelector');
            });
        }

        _setup() {
            var self = this;
            var allSrs = this.mbMap.getAllSrs();
            for (var i = 0; i < allSrs.length; i++) {
                this._addSrsOption(this.$select, allSrs[i]);
            }
            this.$select.val(this.mbMap.model.getCurrentProjectionCode());

            if (typeof initDropdown === 'function') {
                initDropdown.call(this.$select.parent());
            }
            this.$select.on('change', this._switchSrs.bind(this));
            $(document).on('mbmapsrschanged', $.proxy(self._onSrsChanged, self));
            $(document).on('mbmapsrsadded', $.proxy(self._onSrsAdded, self));

            Mapbender.elementRegistry.markReady(this);
        }

        _switchSrs(evt) {
            var newSrsCode = this.getSelectedSrs();
            if (newSrsCode) {
                this.mbMap.changeProjection(newSrsCode);
            }
        }

        _onSrsChanged(event, data) {
            this.$select.val(data.to);
            if (typeof initDropdown === 'function') {
                initDropdown.call(this.$select.parent());
            }
        }

        _onSrsAdded(event, srsObj) {
            this._addSrsOption(this.$select, srsObj);
            if (typeof initDropdown === 'function') {
                initDropdown.call(this.$select.parent());
            }
        }

        /**
         * @param {jQuery} $select
         * @param {object} srsObj
         * @property {string} srsObj.title
         * @property {string} srsObj.name
         * @private
         */
        _addSrsOption($select, srsObj) {
            if (!$('option[value="' + srsObj.name + '"]', $select).length) {
                $select.append($('<option/>', {
                    value: srsObj.name,
                    text: srsObj.title
                }));
            }
        }

        getSelectedSrs() {
            return this.$select.val();
        }

        _destroy() { /* noop retained for parity */ }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbSrsSelector = MbSrsSelector;

})();

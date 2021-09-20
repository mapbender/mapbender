(function($){

    $.widget("mapbender.mbSrsSelector", {
        options: {
        },
        $select: null,
        mbMap: null,
        _create: function() {
            var self = this;
            this.$select = $('select', this.element);
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget('mbSrsSelector');
            });
        },
        _setup: function(){
            var self = this;
            var allSrs = this.mbMap.getAllSrs();
            for(var i = 0; i < allSrs.length; i++){
                this._addSrsOption(this.$select, allSrs[i]);
            }
            this.$select.val(this.mbMap.model.getCurrentProjectionCode());

            initDropdown.call(this.$select.parent());
            this.$select.on('change', $.proxy(this._switchSrs, this));
            $(document).on('mbmapsrschanged', $.proxy(self._onSrsChanged, self));
            $(document).on('mbmapsrsadded', $.proxy(self._onSrsAdded, self));
            
            this._trigger('ready');
        },
        _switchSrs: function(evt) {
            var newSrsCode = this.getSelectedSrs();
            if (newSrsCode) {
                this.mbMap.changeProjection(newSrsCode);
            }
        },
        _onSrsChanged: function(event, data) {
            this.$select.val(data.to);
            if (initDropdown) {
                initDropdown.call(this.$select.parent());
            }
        },
        _onSrsAdded: function(event, srsObj) {
            this._addSrsOption(this.$select, srsObj);
            if (initDropdown) {
                initDropdown.call(this.$select.parent());
            }
        },
        /**
         * @param {jQuery} $select
         * @param {object} srsObj
         * @property {string} srsObj.title
         * @property {string} srsObj.name
         * @private
         */
        _addSrsOption: function($select, srsObj) {
            if (!$('option[value="' + srsObj.name + '"]', $select).length) {
                $select.append($('<option/>', {
                    value: srsObj.name,
                    text: srsObj.title
                }));
            }
        },
        getSelectedSrs: function() {
            return $('select', this.element).val();
        },
        _destroy: $.noop
    });

})(jQuery);


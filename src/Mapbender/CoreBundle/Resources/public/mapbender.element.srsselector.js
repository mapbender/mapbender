(function($){

    $.widget("mapbender.mbSrsSelector", {
        options: {
            target: null
        },
        $select: null,
        mbMap: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbSrsSelector", this.options.target)){
                return;
            }
            this.$select = $('select', this.element);
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function(){
            var self = this;
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            var allSrs = this.mbMap.getAllSrs();
            for(var i = 0; i < allSrs.length; i++){
                this._addSrsOption(this.$select, allSrs[i]);
            }
            this.$select.val(this.mbMap.map.olMap.getProjection());

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
            this.$select.val(data.to.projCode);
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


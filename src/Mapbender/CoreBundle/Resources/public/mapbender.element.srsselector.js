(function($){

    $.widget("mapbender.mbSrsSelector", {
        options: {
            target: null
        },
        $select: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbSrsSelector", this.options.target)){
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },
        _setup: function(){
            var self = this;
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            var allSrs = mbMap.getAllSrs();
            this.$select = $('select', this.element);
            for(var i = 0; i < allSrs.length; i++){
                this._addSrsOption(this.$select, allSrs[i]);
            }
            this.$select.val(mbMap.map.olMap.getProjection());

            initDropdown.call(this.$select.parent());
            this.$select.on('change', $.proxy(this._switchSrs, this));
            $(document).on('mbmapsrschanged', $.proxy(self._onSrsChanged, self));
            $(document).on('mbmapsrsadded', $.proxy(self._onSrsAdded, self));
            
            this._trigger('ready');
        },
        _switchSrs: function(evt){
            var dest = new OpenLayers.Projection(this.getSelectedSrs());
            if(!dest.proj.units){
                dest.proj.units = 'degrees';
            }
            this._trigger('srsSwitched', null, {projection: dest});
            return true;
        },
        _onSrsChanged: function(event, data) {
            $('select', this.element).val(data.to.projCode);
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


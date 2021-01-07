(function($){
    $.widget("mapbender.mbWmcList", {
        options: {},
        elementUrl: null,
        wmcloader: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbWmcList", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(){
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.wmcloader = $('#' + this.options.target).data('mapbenderMbWmcLoader');
            $.ajax({
                url: self.elementUrl + "list",
                type: "POST",
                success: function(data){
                    if(data.success){
                        self.element.find('select').html('');
                        self.element.find('select').append('<option value="">' + Mapbender.trans("mb.wmc.element.wmclist.select.def_option") + ' ...</option>');
                        for (var wmc_id in data.success) {
                            var node = $('<option value="' + wmc_id + '"></option>');
                            self.element.find('select').append(node);
                            node.text(data.success[wmc_id]);
                        }
                        self.element.find("select").change($.proxy(self._selectWmc, self));
                        if(initDropdown){
                            initDropdown.call(self.element);
                        }
                    }
                }
            });
        },
        _selectWmc: function(e){
            var wmc_id = this.element.find("select").val();
            if(wmc_id !== '') this.wmcloader.loadFromId(wmc_id);
        },
        _destroy: $.noop
    });

})(jQuery);
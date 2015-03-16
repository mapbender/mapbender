(function($){

    $.widget("mapbender.mbDigitizer", {
        options: {},
        map: null,

        _create: function(){
            if(!Mapbender.checkTarget("mbDigitizer", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap').map.olMap;
            
            this._trigger('ready');
        },
        
    });

})(jQuery);

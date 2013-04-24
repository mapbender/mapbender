(function($) {

    $.widget("mapbender.mbWmsloader", $.ui.dialog, {
        options: {
            autoOpen: false
        },

        elementUrl: null,

        _create: function() {
            var self = this;
            this._super('_create');
            if(!Mapbender.checkTarget("mbWmsloader", this.options.target)){
                return;
            }
            $(document).one('mapbender.setupfinished', function() {
                $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
            });
            
        },

        _setup: function(){
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
            
            me.mbWmsloader("option","buttons",{
                "Cancel": function(){
                    $(this).mbWmsloader("close");
                },
                "Ok": function(){
                    self.loadWms.call(self,$(this).find(".url").val());
                    $(this).mbWmsloader("close");
                }

            });

            if(document.URL.indexOf('url=') !== -1) {
                var url = document.URL.substr((document.URL.indexOf('url=') + 4));
                url = decodeURIComponent((url + ''));
                url = decodeURIComponent((url + ''));
                this.loadWms(url.replace(/\+/g, '%20'));
            }
        },

        loadWms: function(getCapabilitiesUrl) {
            if(getCapabilitiesUrl === null || getCapabilitiesUrl === '') return;

            // dataType is 'text' as otherwise jQuery tries to parse the response
            // and often fails with GetCapabilities documents.
            $.ajax({
                url: Mapbender.configuration.application.urls.proxy,
                data: {
                    url: getCapabilitiesUrl
                },
                dataType: 'text',
                context: this,
                success: function(xml) {
                    this._getCapabilitiesUrlSuccess(xml, getCapabilitiesUrl);
                },
                error: this._getCapabilitiesError
            });
        },
        _getCapabilitiesUrlSuccess: function(xml, getCapabilitiesUrl) {
            var id = $('#' + this.options.target).data('mbMap').genereateSourceId();
            var layerDef = Mapbender.source.wms.layersFromCapabilities(xml, id);
            $('#' + this.options.target).data('mbMap').addSource(layerDef, null, null);
        },
        
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR) {
            alert("oh npo");
        },

        _destroy: $.noop
    });
    
})(jQuery);


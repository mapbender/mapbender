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
            var self = this;
            if(getCapabilitiesUrl === null || getCapabilitiesUrl === '' ||
                (getCapabilitiesUrl.toLowerCase().indexOf("http://") !== 0 && getCapabilitiesUrl.toLowerCase().indexOf("https://") !== 0)){
                Mapbender.error("WMSLoader: a WMS capabilities can't be loaded! The capabilities url is not valid.");
                return;
            }
            var params = OpenLayers.Util.getParameters(getCapabilitiesUrl);
            var version = null, request = null, service = null;
            for(param in params){
                if(param.toUpperCase() === "VERSION"){
                    version = params[param];
                } else if(param.toUpperCase() === "REQUEST"){
                    request = params[param];
                } else if(param.toUpperCase() === "SERVICE"){
                    service = params[param];
                }
            }
            if(request === null || service === null){
                Mapbender.error("WMSLoader: a WMS capabilities can't be loaded! The capabilities url is not valid.");
                return;
            }
            if(typeof version === 'undefined'){
                version = "1.3.0";
            }
            if(service.toUpperCase() !== "WMS"){
                Mapbender.error('WMSLoader: the service "'+service+'" is not supported!');
                return false;
            } else if(request.toUpperCase() !== "GETCAPABILITIES"){
                Mapbender.error('WMSLoader: the WMS Operation "'+request+'" is not supported!');
                return false;
            } else if(!(version.toUpperCase() === "1.1.0" || version.toUpperCase() === "1.1.1" || version.toUpperCase() === "1.3.0")){
                Mapbender.error('WMSLoader: the WMS version "'+version+'" is not supported!');
                return false;
            }
            // dataType is 'text' as otherwise jQuery tries to parse the response
            // and often fails with GetCapabilities documents.
            $.ajax({
                url: Mapbender.configuration.application.urls.proxy,
                data: {
                    url: getCapabilitiesUrl
                },
                dataType: 'text',
//                context: this,
                success: function(data, textStatus, jqXHR) {
                    self._getCapabilitiesUrlSuccess(data, getCapabilitiesUrl);

                    // Maybe to much, need to be scoped! 
                    $(".checkbox").trigger("change");
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _getCapabilitiesUrlSuccess: function(xml, getCapabilitiesUrl) {
            var self = this;
            var mbMap = $('#' + self.options.target).data('mbMap');
            var id = mbMap.genereateSourceId();
            var layerDefs = Mapbender.source.wms.layersFromCapabilities(xml, id, this.options.splitLayers, mbMap.model, this.options.defaultFormat, this.options.defaultInfoFormat);
            $.each(layerDefs, function(idx, layerDef){
                mbMap.addSource(layerDef, null, null);
            });
        },
        
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR) {
            Mapbender.error("WMSLoader: a wms capabilities can't be loaded!");
        },

        _destroy: $.noop
    });
    
})(jQuery);


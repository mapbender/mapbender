(function($) {

    $.widget("mapbender.mbWmsloader", {
        options: {
            autoOpen: false,
            title: "Load WMS"
        },

        elementUrl: null,

        _create: function() {
            var self = this;
            this._super('_create');
            if(!Mapbender.checkTarget("mbWmsloader", this.options.target)){
                return;
            }
            
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function(){
//            var self = this;
//            var me = $(this.element);
//            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id');

//            me.click(function() {
//                console.log("click")
//                self._onClick.call(self);
//            });
//
//            if(document.URL.indexOf('url=') !== -1) {
//                var url = document.URL.substr((document.URL.indexOf('url=') + 4));
//                url = decodeURIComponent((url + ''));
//                //                url = decodeURIComponent((url + ''));
//                this.loadWms(url.replace(/\+/g, '%20'));
//            }
        },

        open: function() {
            var self = this;

            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                var content = this.element.show();

                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function(){
                    $("body").mbPopup("close");
                }).mbPopup("addButton", "Load", "button right", function(){
                    self.loadWms.call(self,$('#' + $(self.element).attr('id') + ' input[name="loadWmsUrl"]').val());//.url").val());
                    $("body").mbPopup("close");
                    
                })
                .mbPopup('showCustom', {
                    title:this.options.title, 
                    content: content
                });
            }
        },

        loadWms: function(getCapabilitiesUrl) {
            var self = this;
            if(getCapabilitiesUrl === null || getCapabilitiesUrl === '' || getCapabilitiesUrl.toLowerCase().indexOf("http://") !== 0){
                Mapbender.error("A WMS cannot be loaded!");
                return;
            }
            var params = OpenLayers.Util.getParameters(getCapabilitiesUrl);
            var version, request, service;
            for(param in params){
                if(param.toUpperCase() === "VERSION"){
                    version = params[param];
                } else if(param.toUpperCase() === "REQUEST"){
                    request = params[param];
                } else if(param.toUpperCase() === "SERVICE"){
                    service = params[param];
                }
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
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    self._getCapabilitiesUrlError(jqXHR, textStatus, errorThrown);
                }
            });
        },
        _getCapabilitiesUrlSuccess: function(xml, getCapabilitiesUrl) {
            var self = this;
            var mbMap = $('#' + self.options.target).data('mbMap');
            var id = $('#' + this.options.target).data('mbMap').genereateSourceId();
            var layerDefs = Mapbender.source.wms.layersFromCapabilities(xml, id, this.options.splitLayers, mbMap.model, this.options.defaultFormat, this.options.defaultInfoFormat);
            $.each(layerDefs, function(idx, layerDef){
                mbMap.addSource(layerDef, null, null);
            });
        },
        
        _getCapabilitiesUrlError: function(xml, textStatus, jqXHR) {
            Mapbender.error("A WMS cannot be loaded!"); //  ???
        },

        _destroy: $.noop
    });
    
})(jQuery);


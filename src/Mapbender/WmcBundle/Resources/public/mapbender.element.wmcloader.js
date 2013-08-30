(function($){
    $.widget("mapbender.mbWmcLoader", {
        options: {},
        elementUrl: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbWmcLoader", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(){
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if(typeof this.options.load !== 'undefined'
                    && typeof this.options.load.wmc !== 'undefined'){
                var wmc_id = this.options.load.wmc;
                var map = $('#' + this.options.target).data('mapbenderMbMap');
                var wmcHandlier = new Mapbender.WmcHandler(map);
                wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
            }

        },
        /**
         * closes a dialog
         */
        close: function(){
            $("body").mbPopup("close");
        },
        /**
         * opens a dialog
         */
        open: function(){
            var self = this;
            if(!$('body').data('mbPopup')){
                $("body").mbPopup();
                $("body").mbPopup('addButton', "Back", "button buttonBack left", function(){
                    $("#popupSubContent").remove();
                    $("#popupSubTitle").text("");
                    $("#popup").find(".buttonYes, .buttonBack").hide();
                    $("#popupContent").show();
                });
                $("body").mbPopup(
                        'showAjaxModal',
                        {title: self.element.attr('title'), subTitle: ""},
                self.elementUrl + "list",
                        null,
                        null,
                        function(){  //afterLoad
                            var popup = $("#popup");
                            popup.find(".buttonYes, .buttonBack").hide();
                            popup.find(".loadWmc").on("click", $.proxy(self._loadFromId, self));
                            popup.find(".loadXmlWmc").on("click", $.proxy(self._loadForm, self));
                        }
                );
            }
            return false;
        },
        /**
         * Loads a wmc list
         */
        _loadList: function(){
            var self = this;
            $.ajax({
                url: self.elementUrl + "list",
                type: "POST",
                success: function(data){
                    $("#popupContent").html(data);
                    var popup = $("#popup");
                    popup.find(".loadWmc").on("click", $.proxy(self._loadFromId, self));
                    popup.find(".loadXmlWmc").on("click", $.proxy(self._loadForm, self));
                }
            });
        },
        /**
         * Loads a form to load a wmc
         */
        _loadForm: function(e){
            var self = this;
            var url = $(e.target).attr("href");
            if(url){
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(data){
                        $("#popupContent").wrap('<div id="contentWrapper"></div>').hide();
                        $("#contentWrapper").append('<div id="popupSubContent" class="popupSubContent"></div>');
                        $("#popupSubContent").html(data);
                        var subTitle = $("#popupSubContent").find("form").attr("title");
                        $("#popupSubTitle").text(" - " + subTitle);
                        $("#popup").find(".buttonBack").show();
                        self._ajaxForm();
                    }
                });
            }
            return false;
        },
        /**
         * ajaxform for load a wmc
         */
        _ajaxForm: function(){
            var self = this;
            $("#popup").find('form#wmc-load').ajaxForm({
                url: self.elementUrl + 'loadxml',
                type: 'POST',
                beforeSerialize: function(e){
                    var map = $('#' + self.options.target).data('mapbenderMbMap')
                    var state = map.getMapState();
                    $("#popup").find('input#wmc_state_json').val(JSON.stringify(state));
                },
                contentType: 'json',
                context: self,
                success: function(response){
                    response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
                    if(response.success){
                        $("#popupSubContent").remove();
                        $("#popupSubTitle").text("");
                        $("#popup").find(".buttonYes, .buttonBack").hide();
                        $("#popupContent").show();
                        for(wmc_id in response.success){
                            var map = $('#' + this.options.target).data('mapbenderMbMap');
                            var wmcHandlier = new Mapbender.WmcHandler(map, {
                                keepExtent: self.options.keepExtent,
                                keepSources: self.options.keepSources});
                            wmcHandlier.addToMap(wmc_id, response.success[wmc_id]);
                        }
//                        self.close();
                    }else if(response.error){
                        $("#popupSubContent").html(response.error);
                        $("#popupSubTitle").text("ERROR");
                    }
                },
                error: function(response){
                    Mapbender.error(response);
                }
            });
        },
        /**
         * Loads a wmc from id
         */
        _loadFromId: function(e){
            var wmc_id = $(e.target).parents('tr:first').attr('data-id');
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var wmcHandlier = new Mapbender.WmcHandler(map, {
                keepExtent: this.options.keepExtent,
                keepSources: this.options.keepSources});
            wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
//            this.close(); 
        },
        _destroy: $.noop
    });

})(jQuery);
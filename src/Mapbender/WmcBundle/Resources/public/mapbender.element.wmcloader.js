(function($){
    $.widget("mapbender.mbWmcLoader", {
        options: {},
        elementUrl: null,
        popup: null,
        mbMap: null,
        _create: function(){
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbWmcLoader", self.options.target);
            });
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if (typeof this.options.load !== 'undefined') {
                var handler = new Mapbender.WmcHandler(this.mbMap);
                if (typeof this.options.load.wmcid !== 'undefined') {
                    var wmc_id = this.options.load.wmcid;
                    handler.loadFromId(this.elementUrl + 'load', wmc_id);
                } else if (typeof this.options.load.wmcurl !== 'undefined') {
                    var wmc_url = this.options.load.wmcurl;
                    handler.loadFromUrl(this.elementUrl + 'wmcfromurl', wmc_url);
                }
            }
            this._trigger('ready');
        },
        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.open(callback);
        },
        /**
         * closes a dialog
         */
        close: function(){
            if(this.popup){
                this.element.hide().appendTo($('body'));
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }
            this.callback ? this.callback.call() : this.callback = null;
        },
        /**
         * opens a dialog
         */
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    resizable: true,
                    modal: false,
                    closeOnESC: false,
                    cssClass: 'mb-wmcEditor',
                    content: [$.ajax({
                            url: self.elementUrl + 'list',
                            complete: function(data){
                                $('.loadWmcId', self.popup.$element).on("click", $.proxy(self._loadFromId, self));
                                $('.loadWmcXml', self.popup.$element).on("click", $.proxy(self._loadForm, self));
                            }})],
                    destroyOnClose: true,
                    width: 480,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.cancel"),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.ok"),
                            cssClass: 'button buttonYes right',
                            callback: function(){
                                $('#wmc-load input[type="submit"]', self.popup.$element).click();
                                return false;
                            }
                        },
                        'back': {
                            label: Mapbender.trans("mb.wmc.element.wmcloader.popup.btn.back"),
                            cssClass: 'button left buttonBack',
                            callback: function(){
                                $(".popupSubContent").remove();
                                $(".popupSubTitle").text("");
                                $(".popup", self.popup.$element).find(".buttonYes, .buttonBack").hide();
                                $(".popupContent", self.popup.$element).show();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
                $(".popup", self.popup.$element).find(".buttonYes, .buttonBack").hide();
            }else{
                this.popup.open($.ajax({url: self.elementUrl + 'list'}));
            }
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
                    $(".loadWmcId").on("click", $.proxy(self._loadFromId, self));
                    $(".loadWmcXml").on("click", $.proxy(self._loadForm, self));
                }
            });
        },
        /**
         * Loads a form to load a wmc
         */
        _loadForm: function(e){
            if(this.popup && this.popup.$element){
                var self = this;
                var url = $(e.target).attr("href");
                if(url){
                    $.ajax({
                        url: url,
                        type: "GET",
                        complete: function(data){
                            if(typeof data !== 'undefined'){
                                var pop = $(".popup", self.popup.$element);
                                var popupContent = $(".popupContent", self.popup.$element);
                                var contentWrapper = pop.find(".contentWrapper");

                                if(contentWrapper.get(0) == undefined){
                                    popupContent.wrap('<div class="contentWrapper"></div>');
                                    contentWrapper = pop.find(".contentWrapper");
                                }
                                popupContent.hide();
                                var subContent = contentWrapper.find(".popupSubContent");

                                if(subContent.get(0) == undefined){
                                    contentWrapper.append('<div class="popupSubContent"></div>');
                                    subContent = contentWrapper.find('.popupSubContent');
                                }
                                subContent.html(data.responseText);

                                var subTitle = subContent.find("form").attr('title');
                                $(".popupSubTitle").text(" - " + subTitle);
                                $(".popup", self.popup.$element).find(".buttonYes, .buttonBack").show();
                                self._ajaxForm();
                            }
                        }
                    });
                }
            }
            return false;
        },
        /**
         * ajaxform for load a wmc
         */
        _ajaxForm: function(){
            if(this.popup && this.popup.$element){
                var self = this;
                $('form#wmc-load', this.popup.$element).ajaxForm({
                    url: self.elementUrl + 'loadxml',
                    type: 'POST',
                    beforeSerialize: function(e){
                        var state = self.mbMap.getMapState();
                        $('input#wmc_state_json', self.popup.$element).val(JSON.stringify(state));
                    },
                    contentType: 'json',
                    context: self,
                    success: function(response){
                        response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
                        if(response.success){
                            $(".popupSubContent", self.popup.$element).remove();
                            $(".popupSubTitle", self.popup.$element).text("");
                            $(".buttonYes, .buttonBack", self.popup.$element).hide();
                            $(".popupContent", self.popup.$element).show();
                            for (var wmc_id in response.success){
                                var wmcHandlier = new Mapbender.WmcHandler(self.mbMap, {
                                    keepExtent: self.options.keepExtent,
                                    keepSources: self.options.keepSources});
                                wmcHandlier.addToMap(wmc_id, response.success[wmc_id]);
                            }
                        }else if(response.error){
                            $(".popupSubContent", self.popup.$element).html(Mapbender.trans(response.error));
                            $(".popupSubTitle", self.popup.$element).text("ERROR");
                        }
                    },
                    error: function(response){
                        Mapbender.error(response);
                    }
                });
            }
        },
        /**
         * Loads a wmc from id (event handler)
         */
        _loadFromId: function(e){
            var wmc_id = $(e.target).parents('tr:first').attr('data-id');
            this.loadFromId(wmc_id);
        },
        loadFromId: function(wmc_id){
            var wmcHandlier = new Mapbender.WmcHandler(this.mbMap, {
                keepExtent: this.options.keepExtent,
                keepSources: this.options.keepSources});
            wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
        },
        removeFromMap: function(){
            var wmcHandlier = new Mapbender.WmcHandler(this.mbMap, {
                keepExtent: this.options.keepExtent,
                keepSources: this.options.keepSources});
            wmcHandlier.removeFromMap();
        },
        wmcAsXml: function(){
            var state = this.mbMap.getMapState();
            var form = $('<form method="POST" action="' + (this.elementUrl + 'wmcasxml') + '" target="_BLANK" />');
            $('<input>').attr('type', 'hidden').attr('name', 'state').val(JSON.stringify(state)).appendTo(form);
            form.appendTo($('body'));
            form.submit();
            form.remove();
        },
        _destroy: $.noop
    });

})(jQuery);
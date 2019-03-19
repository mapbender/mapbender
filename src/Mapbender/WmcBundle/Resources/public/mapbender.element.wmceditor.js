(function($){
    $.widget("mapbender.mbWmcEditor", {
        options: {},
        elementUrl: null,
        mbMap: null,
        _create: function(){
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self._setup(mbMap);
            }, function() {
                Mapbender.checkTarget("mbWmcEditor", self.options.target);
            });
        },
        /**
         * Initializes the wmc handler
         */
        _setup: function(mbMap) {
            this.mbMap = mbMap;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
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
            var width = self.element.attr('width');
            var height = self.element.attr('height');
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    modal: false,
                    closeOnESC: false,
                    cssClass: 'mb-wmcEditor',
                    content: [$.ajax({
                            url: self.elementUrl + 'list',
                            complete: function(data){
                                $(".checkWrapper input.checkbox", self.popup.$element).each(function(){
                                    initCheckbox.call(this);
                                    $(this).on("change", $.proxy(self._changePublic, self));
                                });
                                $(".editWmc", self.popup.$element).on("click", $.proxy(self._loadForm, self));
                                $(".addWmc", self.popup.$element).on("click", $.proxy(self._loadNewForm, self));
                                $(".removeWmc", self.popup.$element).on("click", $.proxy(self._loadRemoveForm, self));
                            }})],
                    destroyOnClose: true,
                    width: width,
                    height: height,
                    resizable: true,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.cancel"),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.ok"),
                            cssClass: 'button buttonYes right',
                            callback: function(){
                                $('form input[type="submit"]', self.popup.$element).click();
                                return false;
                            }
                        },
                        'back': {
                            label: Mapbender.trans("mb.wmc.element.wmceditor.popup.btn.back"),
                            cssClass: 'button left buttonBack',
                            callback: function(){
                                $(".popupSubContent", self.popup.$element).remove();
                                $(".popupSubTitle", self.popup.$element).text("");
                                $(".buttonYes, .buttonBack", self.popup.$element).hide();
                                $(".popupContent", self.popup.$element).show();
                            }
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
                $(".popup", self.popup.$element).find(".buttonYes, .buttonBack").hide();
            }else{
                $(".popupContent", self.popup.$element).empty();
                this.popup.open($.ajax({url: self.elementUrl + 'list'}));
            }
            return false;
        },
        /**
         * Loads a wmc list
         */
        _loadList: function(){
            var self = this;
            if(this.popup && this.popup.$element){
                $.ajax({
                    url: self.elementUrl + "list",
                    type: "POST",
                    success: function(data){
                        $(".popupContent", self.popup.$element).html(data);
                        $(".checkWrapper input.checkbox", self.popup.$element).each(function(){
                            initCheckbox.call(this);
                            $(this).on("change", $.proxy(self._changePublic, self));
                        });
                        $(".editWmc", self.popup.$element).on("click", $.proxy(self._loadForm, self));
                        $(".addWmc", self.popup.$element).on("click", $.proxy(self._loadNewForm, self));
                        $(".removeWmc", self.popup.$element).on("click", $.proxy(self._loadRemoveForm, self));
                    }
                });
            }
        },
        /**
         * Loads a form to create a new wmc
         */
        _loadNewForm: function(e){
            var self = this;
            if(this.popup && this.popup.$element){
                var url = $(e.target).attr("href");
                if(url){
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function(data){
                            if($('.contentWrapper', self.popup.$element).length === 0)
                                $(".popupContent", self.popup.$element).wrap('<div class="contentWrapper"></div>');
                            $(".popupContent", self.popup.$element).hide();
                            if($('.popupSubContent', self.popup.$element).length === 0)
                                $(".contentWrapper", self.popup.$element).append('<div class="popupSubContent"></div>');
                            $(".popupSubContent", self.popup.$element).html(data);
                            var subTitle = $("form#wmc-save", self.popup.$element).attr("title");
                            $(".popupSubTitle", self.popup.$element).text(" - " + subTitle);
                            $(".buttonBack, .buttonYes", self.popup.$element).show();
                            self._ajaxForm();
                        }
                    });
                }
            }
            return false;
        },
        /**
         * Loads a form to edit a wmc
         */
        _loadForm: function(e){
            var self = this;
            if(this.popup && this.popup.$element){
                var url = $(e.target).attr("data-url");
                if(url){
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function(data){
                            if($('.contentWrapper', self.popup.$element).length === 0)
                                $(".popupContent", self.popup.$element).wrap('<div class="contentWrapper"></div>');
                            $(".popupContent", self.popup.$element).hide();
                            if($('.popupSubContent', self.popup.$element).length === 0)
                                $(".contentWrapper", self.popup.$element).append('<div class="popupSubContent"></div>');
                            $(".popupSubContent").html(data);
                            var subTitle = $("form#wmc-save", self.popup.$element).attr("title");
                            $(".popupSubTitle", self.popup.$element).text(" - " + subTitle);
                            $(".buttonBack, .buttonYes", self.popup.$element).show();
                            self._ajaxForm();
                        }
                    });
                }
                var wmc_id = $(e.target).parents('tr:first').attr('data-id');
                var wmcHandlier = new Mapbender.WmcHandler(this.mbMap, {});
                wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
            }
            return false;
        },
        /**
         * ajaxform for edit/create a wmc
         */
        _ajaxForm: function(){
            var self = this;
            if(this.popup && this.popup.$element){
                $('form#wmc-save', this.popup.$element).ajaxForm({
                    url: self.elementUrl + 'save',
                    type: 'POST',
                    beforeSerialize: function(e){
                        var state = self.mbMap.getMapState();
                        $('input#wmc_state_json', self.popup.$element).val(JSON.stringify(state));
                    },
                    contentType: 'json',
                    context: self,
                    success: function(response){
                        this._loadList();
                        response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
                        $(".popupSubContent", self.popup.$element).html(response.success);
                        $(".popupSubTitle", self.popup.$element).text(" ");
                        $(".buttonYes", self.popup.$element).hide();
                    },
                    error: function(response){
                        this._loadList();
                        $(".popupSubContent", self.popup.$element).html(response.error);
                        $(".popupSubTitle", self.popup.$element).text("ERROR");
                        $(".buttonYes", self.popup.$element).hide();
                    }
                });
            }
        },
        /**
         * Loads a form to create a new wmc
         */
        _loadRemoveForm: function(e){
            var self = this;
            if(this.popup || this.popup.$element){
                var url = $(e.target).attr("data-url");
                if(url){
                    $.ajax({
                        url: url,
                        type: "GET",
                        success: function(data){
                            if($('.contentWrapper', self.popup.$element).length === 0)
                                $(".popupContent", self.popup.$element).wrap('<div class="contentWrapper"></div>');
                            $(".popupContent", self.popup.$element).hide();
                            if($('.popupSubContent', self.popup.$element).length === 0)
                                $(".contentWrapper", self.popup.$element).append('<div class="popupSubContent"></div>');
                            $(".popupSubContent").html(data);
                            var subTitle = $(".popupSubContent form", self.popup.$element).attr("title");
                            $(".popupSubTitle", self.popup.$element).text(" - " + subTitle);
                            $(".buttonBack, .buttonYes", self.popup.$element).show();
                            $('form#wmc-delete', self.popup.$element).on("submit", $.proxy(self._removeWmc, self));
                        }
                    });
                }
            }
            return false;
        },
        /**
         * Delets a wmc
         */
        _removeWmc: function(e){
            e.preventDefault();
            var self = this;
            if(this.popup || this.popup.$element){
                $.ajax({
                    url: self.elementUrl + 'delete',
                    type: "POST",
                    data: $('form#wmc-delete', self.popup.$element).serialize(),
                    success: function(response){
                        self._loadList();
                        if(response.success){
                            $(".popupSubContent", self.popup.$element).html(Mapbender.trans(response.success));
                            $(".popupSubTitle", self.popup.$element).text(" ");
                        }else{
                            self._loadList();
                            $(".popupSubContent", self.popup.$element).html(Mapbender.trans(response.error));
                            $(".popupSubTitle", self.popup.$element).text(" ");
                        }
                    },
                    error: function(response){
                        Mapbender.error(Mapbender.trans(response));
                    }
                });
            }
            return false;
        },
        /**
         * Changes an access (public/private) for a wmc
         */
        _changePublic: function(e){
            var self = this;
            var input = $(e.target);
            var wmcid = input.attr("data-id"),
                    publ = input.is(":checked") ? "enabled" : "disabled";
            e.preventDefault();
            $.ajax({
                url: self.elementUrl + 'public',
                type: "POST",
                data: {wmcid: wmcid, public: publ},
                success: function(response){
                    //TODO?
                },
                error: function(response){
                    Mapbender.error(Mapbender.trans(response));
                }
            });
            return false;
        },
        _destroy: $.noop
    });

})(jQuery);

(function($){
    $.widget("mapbender.mbWmcEditor", {
        options: {},
        elementUrl: null,
        _create: function(){
            if(!Mapbender.checkTarget("mbWmcEditor", this.options.target)){
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
        },
        /**
         * closes a dialog
         */
        close: function(){
            this.element.hide().appendTo($('body'));
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
                }).mbPopup(
                        'showAjaxModal',
                        {title: self.element.attr('title'), subTitle: ""},
                self.elementUrl + "list",
                        null,
                        null,
                        function(){  //afterLoad
                            var popup = $("#popup");
                            popup.find(".buttonYes, .buttonBack").hide();
                            popup.find(".checkWrapper").on("click", $.proxy(self._changePublic, self));
                            popup.find(".editWmc").on("click", $.proxy(self._loadForm, self));
                            popup.find(".addWmc").on("click", $.proxy(self._loadNewForm, self));
                            popup.find(".removeWmc").on("click", $.proxy(self._loadRemoveForm, self));
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
                    popup.find(".checkWrapper").on("click", $.proxy(self._changePublic, self));
                    popup.find(".editWmc").on("click", $.proxy(self._loadForm, self));
                    popup.find(".addWmc").on("click", $.proxy(self._loadNewForm, self));
                    popup.find(".removeWmc").on("click", $.proxy(self._loadRemoveForm, self));
                }
            });
        },
        /**
         * Loads a form to create a new wmc
         */
        _loadNewForm: function(e){
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
         * Loads a form to edit a wmc
         */
        _loadForm: function(e){
            var self = this;
            var url = $(e.target).attr("data-url");
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
            var wmc_id = $(e.target).parents('tr:first').attr('data-id');
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var wmcHandlier = new Mapbender.WmcHandler(map, {});
            wmcHandlier.loadFromId(this.elementUrl + 'load', wmc_id);
            return false;
        },
        /**
         * ajaxform for edit/create a wmc
         */
        _ajaxForm: function(){
            var self = this;
            $("#popup").find('form#wmc-save').ajaxForm({
                url: self.elementUrl + 'save',
                type: 'POST',
                beforeSerialize: function(e){
                    var map = $('#' + self.options.target).data('mapbenderMbMap')
                    var state = map.getMapState();
                    $("#popup").find('input#wmc_state_json').val(JSON.stringify(state));
                },
                contentType: 'json',
                context: self,
                success: function(response){
                    this._loadList();
                    response = $.parseJSON(response.replace(/<[^><]*>/gi, ''));
                    $("#popupSubContent").html(response.success);
                    $("#popupSubTitle").text(" ");
                },
                error: function(response){
                    this._loadList();
                    $("#popupSubContent").html(response.error);
                    $("#popupSubTitle").text("ERROR");
                }
            });
        },
        /**
         * Loads a form to create a new wmc
         */
        _loadRemoveForm: function(e){
            var self = this;
            var url = $(e.target).attr("data-url");
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
                        $("#popup").find('form#wmc-delete').on("submit", $.proxy(self._removeWmc, self));
                    }
                });
            }
            return false;
        },
        /**
         * Delets a wmc
         */
        _removeWmc: function(e){
            e.preventDefault();
            var self = this;
            $.ajax({
                url: self.elementUrl + 'delete',
                type: "POST",
                data: $("#popup").find('form#wmc-delete').serialize(),
                success: function(response){
                    self._loadList();
                    if(response.success){
                        $("#popupSubContent").html(response.success);
                        $("#popupSubTitle").text(" ");
                    }else{
                        self._loadList();
                        $("#popupSubContent").html(response.error);
                        $("#popupSubTitle").text(" ");
                    }
                },
                error: function(response){
                    Mapbender.error(response);
                }
            });
            return false;
        },
        /**
         * Changes an access (public/private) for a wmc
         */
        _changePublic: function(e){
            var self = this;
            var wmcid = $(e.target).find('input[type="checkbox"]').attr("data-id"),
                    publ = $(e.target).hasClass('iconCheckboxActive') ? "disabled" : "enabled";
            e.preventDefault();
            $.ajax({
                url: self.elementUrl + 'public',
                type: "POST",
                data: {wmcid: wmcid, public: publ},
                success: function(response){
                    //TODO?
                },
                error: function(response){
                    Mapbender.error(response);
                }
            });
            return false;
        },
        _destroy: $.noop
    });

})(jQuery);
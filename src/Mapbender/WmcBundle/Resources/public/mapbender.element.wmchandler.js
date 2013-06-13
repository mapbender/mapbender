(function($) {
    

    $.widget("mapbender.mbWmcHandler", {
        options: {},

        elementUrl: null,
        //        dlg: null,

        _create: function() {
            if(!Mapbender.checkTarget("mbWmcHandler", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the wmc handler
         */
        _setup: function() {
            var self = this;
            this._super('_create');
            $(this.element).tabs();
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
        //            if(this.options.useEditor === true){
        //                $(self.element).find(".delete").live("click",function(){
        //                    var url = self.elementUrl + 'delete?wmcid=' + $(this).attr("data-id");
        //                    $.post(url, function(){
        //                        $.proxy(self._reloadIndex,self)();
        //                    });
        //                    return false;
        //                });
        //            }
        //            $(this.element).find('#wmceditor-save form#save-wmc').bind('submit', $.proxy(this._save, this));
        //            var self = this;
        //            var me = $(this.element);
        //            this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';

        //            this.dlg = me.find('div.dialog');
        //
        //            $(this.element).find('#wmceditor-save form#save-wmc').ajaxForm({
        //                url: this.elementUrl + 'save',
        //                type: 'POST',
        //                beforeSerialize: $.proxy(this._beforeSave, this),
        //                context: this,
        //                success: this._createWmcSuccess,
        //                error: this._createWmcError
        //            });
        //            me.find('button')
        //            .button()
        //            .click($.proxy(this.open, this)).
        //            hide();
        //
        //            this.dlg.dialog({
        //                width: 500,
        //                autoOpen: false,
        //                close: function(){
        //                    self.dlg.find('form#wmceditor-save input[type="text"],textarea').val('');
        //                    self.dlg.find('[name=tkid]').remove();
        //                    self.dlg.find("form").get(0).reset();
        //                    self.dlg.find("#form_screenshot").attr("required","required");
        //                }
        //            }).tabs();
        },
        
        _loadList: function() {
            if(this.options.useEditor === true){
                var self = this;
                $(this.element).find("#container-wmc-load").load(this.elementUrl + "list",function(){
                    $(self.element).find("#container-wmc-load .iconEdit").bind("click",function(){
//                        var $anchor = $(this);
//                        var id = $.trim($anchor.parent().siblings(".id").text());
                        $(self.element).find("#container-wmc-edit").load(self.elementUrl+"get",{
                            wmcid: $(this).attr("data-id")
                        },function(){
                            
                            // since there is no way to force a fileinpout to display
                            // a preset, it can't be a required field
//                            $("#form_screenshot")
//                            .removeAttr("required");
//                    
//                            var id = $.trim($anchor.parent().siblings(".id").text());
//                            $(self.element).find("form")
//                            .append('<input name="tkid" type="hidden" value="'+ id +'" />');
//                            $(self.element).find("form").ajaxForm({
//                                url: self.elementUrl + 'update',
//                                type: 'POST',
//                                beforeSerialize: $.proxy(self._beforeSave, self),
//                                context: self,
//                                success: self._onSaveSuccess
//                    
//                            });
//                            $(self.element).tabs('select',0);
                        });
                        return false;
                    });
                    $(self.element).find("#container-wmc-load .iconRemove").bind("click",function(e){
                        var wmcid = $(this).attr("data-id");
                        if(Mapbender.confirm("Remove WMC ID:" + wmcid) === true){
                            var url = self.elementUrl + 'remove';
                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: {
                                    wmcid: wmcid
                                },
                                dataType: 'json',
                                success: function(data){
                                    if(data.error)
                                        Mapbender.error(data.error);
                                    else
                                        Mapbender.info(data.success);
                                },
                                error:  function(data){
                                    alert("error")
                                }
                            });
                        }
                        return false;
                    });

                });
            }
        },
        
        _removeWmc: function(id){
            var self = this;
            if(Mapbender.confirm("Remove WMC ID:"+id) === true){
                alert("remove")
                //                var url = this.elementUrl + 'delete?wmcid=' + $(this).attr("data-id");
                //                $.post(url, function(){
                //                    $.proxy(self._loadList, self);
                //                });
                return true;
            } else
                alert("not remove")
            return false;
        },
        _destroy: $.noop,

        open: function() {
            if(!this.options.useEditor){
                Mapbender.error("A WMC Editor is not available. To use a WMC Editor configure your WMC Handler.")
                return;
            }
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup("addButton", "Cancel", "button buttonCancel critical right", function(){
                    //close
                    self.close();
                    $("body").mbPopup("close");                    
                }).mbPopup("addButton", "Save", "button right", function(){
                    //print
                    self._print();
                }).mbPopup('showCustom', {
                    title: me.attr("title"), 
                    showHeader: true, 
                    content: this.element, 
                    draggable: true,
                    width: 300,
                    height: 180,
                    showCloseButton: false,
                    overflow:true
                });
                
                me.show();
                self._loadList();
            }
        //             var tabContainer = $('<div id="featureInfoTabContainer" class="tabContainer featureInfoTabContainer">' + 
        //                               '<ul class="tabs"></ul>' + 
        //                             '</div>');
        //            var header = me.find(".tabs");
        //            header.append(me.find("#wmc-edit"));
        //            var tab_edit = 
        //            newTab       = $('<li id="tab' + layer.id + '" class="tab">' + layer.label + '</li>');
        //            newContainer = $('<div id="container' + layer.id + '" class="container"></div>');
        //
        //            // activate the first container
        //            if(idx == 0){
        //                newTab.addClass("active");
        //                newContainer.addClass("active");
        //            }
        //
        //            header.append(tab_edit);
        //            tabContainer.append(newContainer);
        //            this._reloadIndex();

        },
        
        close: function() {
            this.element.hide().appendTo($('body'));
            this.popup = false;
        //            this._updateElements();
        },

        _reloadIndex_: function(){
            var self = this;
            //            self.dlg.dialog('open');
            $(this.element).find("#wmceditor-load").load(this.elementUrl + "index",function(){
                $(self.element).find("#wmceditor-load .edit").bind("click",function(){
                    var $anchor = $(this);
                    var id = $.trim($anchor.parent().siblings(".id").text());
                    $(self.element).find("#wmceditor-save")
                    .load(self.elementUrl+"get",{
                        'wmcid':id
                    },function(){
                        // since there is no way to force a fileinpout to display
                        // a preset, it can't be a required field
                        $("#form_screenshot")
                        .removeAttr("required");
        
                        var id = $.trim($anchor.parent().siblings(".id").text());
                        $(self.element).find("form")
                        .append('<input name="tkid" type="hidden" value="'+ id +'" />');
                        $(self.element).find("form").ajaxForm({
                            url: self.elementUrl + 'update',
                            type: 'POST',
                            beforeSerialize: $.proxy(self._beforeSave, self),
                            context: self,
                            success: self._onSaveSuccess
        
                        });
                        $(self.element).tabs('select',0);
                    });
                    return false;
                });
                $(self.element).find("#wmceditor-load .delete").bind("click",function(){
                    var url = self.elementUrl + 'delete?wmcid=' + $(this).attr("data-id");
                    $.post(url, function(){
                        $.proxy(self._reloadIndex,self)();
                    });
                    return false;
                });
        
            });
        },

        _beforeSave: function(form, options) {
            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            form.find('input#wmc_state_json').val(JSON.stringify(state));
        //            var map = $('#' + this.options.target);
        //        
        //            var projection = map.data('mbMap').map.olMap.getProjection();
        //            form.find('input#form_srs').val(projection);
        //        
        //            var extent = map.data('mbMap').map.olMap.getExtent().toBBOX();
        //            form.find('input#form_extent').val(extent);
        //                
        //                
        //            var mapcontext = $.extend({},map.data('mbMap').map.olMap);
        //            mapcontext.title = $("input#form_title").val();
        //            mapcontext.metadata = {};
        //            mapcontext.metadata.keywords = $("input#form_tags").val();
        //            mapcontext.metadata['abstract'] = $("textarea#form_description").val();
        //            var wmctext = new OpenLayers.Format.WMC().write(mapcontext);
        //            form.find('input#form_wmc').val(wmctext);
        //                
        //        
        //            var mqLayers = map.data('mbMap').map.layers();
        //            var layers = [];
        //            $.each(mqLayers, function(idx, mqLayer) {
        //                if(mqLayer.olLayer.isBaseLayer) {
        //                    return;
        //                }
        //        
        //                layers.push({
        //                    visible: mqLayer.visible(),
        //                    opacity: mqLayer.opacity(),
        //                    options: mqLayer.options
        //                });
        //            });
        //            form.find('input#form_services').val(JSON.stringify(layers));
        },

        _save: function(event) {

            var map = $('#' + this.options.target).data('mbMap')
            var state = map.getMapState();
            $(event.target).find('input#wmc_state_json').val(JSON.stringify(state));
            var values = $(event.target).serialize();
            $.ajax({
                url: this.elementUrl + 'save',
                type: 'POST',
                data: values,
                dataType: 'json',
                context: this,
                success: this._createWmcSuccess,
                error: this._createWmcError
            });
            return false;
        },

        _reset: function(){
        //            this._reloadIndex();
        //            this.dlg.dialog('close');
        },
        _createWmcSuccess: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            Mapbender.info('Themenkarte gespeichert mit der id=' + response);
        },
        _createWmcError: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            Mapbender.error('ERROR: Themenkarte gespeichert mit der id=' + response);
        }
    });

})(jQuery);
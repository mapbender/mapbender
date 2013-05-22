(function($) {
    

    $.widget("mapbender.mbWmcEditor",$.ui.dialog, {
        options: {},

        elementUrl: null,
        //        dlg: null,

        _create: function() {
            if(!Mapbender.checkTarget("mbViewerMenu", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the wmc editor
         */
        _setup: function() {
            this._super('_create');
            $(this.element).tabs();
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            //            $(this.element).find('#wmceditor-save form#save-wmc').bind('submit', $.proxy(this._save, this));
            //            var self = this;
            //            var me = $(this.element);
            //            this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';

            //            this.dlg = me.find('div.dialog');
            //
            $(this.element).find('#wmceditor-save form#save-wmc').ajaxForm({
                url: this.elementUrl + 'save',
                type: 'POST',
                beforeSerialize: $.proxy(this._beforeSave, this),
                context: this,
                success: this._createWmcSuccess,
                error: this._createWmcError
            });
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

        _destroy: $.noop,

        open: function() {
            this._super('open');
            this._reloadIndex();

        },

        _reloadIndex: function(){
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
                    var url = $(this).attr("href");
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
            //            this.dlg.find('input,textarea').each(function() {
            //                window.console && console.log(arguments);
            //            });
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
            alert('Themenkarte gespeichert mit der id=' + response);
        },
        _createWmcError: function(response) {
            //            window.console && console.log(response);
            //            this._reset();
            alert('ERROR: Themenkarte gespeichert mit der id=' + response);
        }
    });

})(jQuery);
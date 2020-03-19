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
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
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
                        }
                    }
                });
                this.popup.$element.on('close', $.proxy(this.close, this));
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
                }
            });
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
        _destroy: $.noop
    });

})(jQuery);

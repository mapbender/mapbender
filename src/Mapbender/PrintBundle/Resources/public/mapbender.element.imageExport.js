(function($){
    'use strict';
    $.widget('mapbender.mbImageExport', {
        options: {},
        map: null,
        popupIsOpen: true,
        _create: function() {
            if(!Mapbender.checkTarget('mbImageExport', this.options.target)) {
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        _setup: function() {
            this.model = Mapbender.elementRegistry.listWidgets().mapbenderMbMap.model;

            this._trigger('ready');
            this._ready();
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                    title: self.element.attr('title'),
                    draggable: true,
                    header: true,
                    modal: false,
                    closeButton: false,
                    closeOnESC: false,
                    content: self.element,
                    width: 250,
                    buttons: {
                        'cancel': {
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.cancel'),
                            cssClass: 'button buttonCancel critical right',
                            callback: function(){
                                self.close();
                            }
                        },
                        'ok': {
                            label: Mapbender.trans('mb.print.imageexport.popup.btn.ok'),
                            cssClass: 'button right',
                            callback: function(){
                                self._exportImage();
                                self.close();
                            }
                        }
                    }
                });

                this.popup.$element.on('close', $.proxy(this.close, this));
            } else {
                if(this.popupIsOpen === false){
                    this.popup.open(self.element);
                }
            }
            me.show();
            this.popupIsOpen = true;
        },
        close: function() {
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this.popupIsOpen = false;
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }

            if ( this.callback ) {
                this.callback.call();
            } else {
                this.callback = null;
            }
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }

            this.readyState = true;
        },
        _exportImage: function() {
            var format = $("input[name='imageformat']:checked").val();
            var mapSize = this.model.getMapSize();
            var mapExtent = this.model.getMapExtent();
            var mapCenter = this.model.getMapCenter();

            var activeSourceIds = this.model.getActiveSourceIds();
            var printConfigs = [];
            for (var i = 0; i < activeSourceIds.length; i++) {
                var printConfig = this.model.getSourcePrintConfig(activeSourceIds[i], mapExtent, mapSize);
                if (printConfig) {
                    printConfigs.push(printConfig);
                }
            }

            var vectorLayers = [];

            var data = {
                requests: printConfigs,
                format: format,
                width: mapSize[0],
                height: mapSize[1],
                centerx: mapCenter[0],
                centery: mapCenter[1],
                extentwidth: this.model.getWidthOfExtent(mapExtent),
                extentheight: this.model.getHeigthOfExtent(mapExtent),
                vectorLayers: vectorLayers
            };

            var url = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/export';

            var form = $('<form method="POST" action="' + url + '"/>');
            $('<input/>').attr('type', 'hidden').attr('name', 'data').val(JSON.stringify(data)).appendTo(form);
            form.appendTo($('body'));
            form.submit();
            form.remove();
        }

    });

})(jQuery);

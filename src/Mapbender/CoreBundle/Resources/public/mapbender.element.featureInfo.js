(function($){

    $.widget("mapbender.mbFeatureInfo", {
        options: {
            layers: undefined,
            target: undefined,
            deactivateOnClose: true
        },
        map: null,
        mapClickHandler: null,
        popup: null,

        _create: function(){
            if(!Mapbender.checkTarget("mbFeatureInfo", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function(){
            // Set up click handler
            this.map = $('#' + this.options.target).data('mapQuery');
            this.mapClickHandler = new OpenLayers.Handler.Click(this, {
                'click': this._triggerFeatureInfo
            }, {
                map: this.map.olMap
            });

            if(this.options.autoOpen)
                this.activate();
            this._trigger('ready');
            this._ready();
        },

        _setOption: function(key, value){
            switch(key){
                case "layers":
                    this.options.layers = value;
                    break;
                default:
                    throw Mapbender.trans("mb.core.featureinfo.error.unknownoption",
                        {'key': key, 'namespace': this.namespace, 'widgetname': this.widgetName});
            }
        },

        /**
         * Default action for mapbender element
         */
        defaultAction: function(callback){
            this.activate(callback);
        },

        activate: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            $('#' + this.options.target).addClass('mb-feature-info-active');
            this.mapClickHandler.activate();
        },

        deactivate: function(){
            $('#' + this.options.target).removeClass('mb-feature-info-active');
            $(".toolBarItemActive").removeClass("toolBarItemActive");
            if(this.popup){
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
            }

            this.mapClickHandler.deactivate();
            this.callback ? this.callback.call() : this.callback = null;
        },

        _onTabs: function(){
            $(".tabContainer", this.popup.$element).on('click', '.tab', function(){
                var me = $(this);
                me.parent().parent().find(".active").removeClass("active");
                me.addClass("active");
                $("#" + me.attr("id").replace("tab", "container")).addClass("active");
            });
        },

        _offTabs: function(){
            $(".tabContainer", this.popup.$element).off('click', '.tab');
        },

        /**
         * Trigger the Feature Info call for each layer.
         * Also set up feature info dialog if needed.
         */
        _triggerFeatureInfo: function(e){
            this._trigger('featureinfo', null, { action: "clicked", title: this.element.attr('title'), id: this.element.attr('id')});
            var self = this,
                x = e.xy.x,
                y = e.xy.y,
                fi_exist = false;

            $(this.element).empty();

            var tabContainer = $('<div id="featureInfoTabContainer" class="tabContainer featureInfoTabContainer">' +
                '<ul class="tabs"></ul>' +
                '</div>');
            var header = tabContainer.find(".tabs");
            var layers = this.map.layers();
            var newTab, newContainer;

            // XXXVH: Need to optimize this section for better performance!
            // Go over all layers
            var first = true;
            $.each(this.map.layers(), function(idx, layer){
                if(!layer.visible()){
                    return;
                }
                if(!layer.olLayer.queryLayers || layer.olLayer.queryLayers.length === 0){
                    return;
                }
                fi_exist = true;
                // Prepare result tab list
                newTab = $('<li id="tab' + layer.id + '" class="tab">' + layer.label + '</li>');
                newContainer = $('<div id="container' + layer.id + '" class="container"></div>');

                // activate the first container
                if(first){
                    newTab.addClass("active");
                    newContainer.addClass("active");
                    first = false;
                }

                header.append(newTab);
                tabContainer.append(newContainer);

                switch(layer.options.type){
                    case 'wms':
                        Mapbender.source.wms.featureInfo(layer, x, y, $.proxy(self._featureInfoCallback, self));
                        break;
                }
            });
            //console.log($(".tabContainer, .tabContainerAlt", self.element));
            //$(".tabContainer, .tabContainerAlt", self.element).on('click', '.tab', $.proxy(toggleTabContainer));
            var content = (fi_exist) ? tabContainer : '<p class="description">' + Mapbender.trans('mb.core.featureinfo.error.nolayer') + '</p>';
            if(this.options.type === 'dialog'){
                if(!this.popup || !this.popup.$element){
                    this.popup = new Mapbender.Popup2({
                        title: self.element.attr('title'),
                        draggable: true,
                        modal: false,
                        closeButton: false,
                        closeOnPopupCloseClick: false,
                        closeOnESC: false,
                        content: content,
                        width: 500,
                        buttons: {
                            'ok': {
                                label: Mapbender.trans('mb.core.featureinfo.popup.btn.ok'),
                                cssClass: 'button right',
                                callback: function(){
                                    if(self.options.deactivateOnClose){
                                        self.deactivate();
                                    }else{
                                        this.close();
                                    }
                                }
                            }
                        }
                    });
                    this._onTabs();
                }else{
                    this._offTabs();
                    this.popup.open(content);
                    this._onTabs();
                }
            } else if(this.options.type === 'element'){
                this.element.append(content);
            }
        },

        /**
         * Once data is coming back from each layer's FeatureInfo call,
         * insert it into the corresponding tab.
         */
        _featureInfoCallback: function(data){
            var text = '';
            try{ // cut css
                if(data.response.search('<link') > -1 || data.response.search('<style') > -1){
                    text = data.response.replace(/document.writeln[^;]*;/g, '')
                        .replace(/\n/g, '')
                        .replace(/<link[^>]*>/gi, '')
                        .replace(/<style[^>]*(?:[^<]*<\/style>|>)/gi, '');
                } else {
                    text = data.response.replace(/\n/g, '<br/>');
                }
            }catch(e){
            }
            //TODO: Needs some escaping love
            $('#container' + data.layerId).removeClass('loading').html(text);
        },

        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },

        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks){
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });
})(jQuery);

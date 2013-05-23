(function($) {

$.widget("mapbender.mbFeatureInfo", $.ui.dialog, {
    options: {
        layers: undefined,
        target: undefined,
        deactivateOnClose: true
    },

    map: null,
    mapClickHandler: null,

    _create: function() {
        if(!Mapbender.checkTarget("mbFeatureInfo", this.options.target)){
            return;
        }
        var self = this;
        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
    },

    _setup: function(){
        this._super('_create');
    },

    _setOption: function(key, value) {
        switch(key) {
            case "layers":
                this.options.layers = value;
                break;
            default:
                throw "Unknown or unhandled option " + key + " for " + this.namespace + "." + this.widgetName;
        }
    },

    activate: function() {
        var self = this;
        this.map = $('#' + this.options.target).data('mapQuery');
        $('#' + this.options.target).addClass('mb-feature-info-active');
        this.mapClickHandler = function(e) {
            self._triggerFeatureInfo.call(self, e);
        };
        this.map.element.bind('click', self.mapClickHandler);
    },

    deactivate: function() {
        if(this.map) {
            $('#' + this.options.target).removeClass('mb-feature-info-active');
            this.map.element.unbind('click', this.mapClickHandler);
        }
        if(this.element.data('dialog') && this.element.dialog('isOpen')) {
            this.element.dialog('close');
        }
    },

    /**
 * Trigger the Feature Info call for each layer.
 * Also set up feature info dialog if needed.
 */
    _triggerFeatureInfo: function(e) {
        var self = this,
        x = e.pageX - $(this.map.element).offset().left,
        y = e.pageY - $(this.map.element).offset().top,
        fi_exist = false;

        $(this.element).empty();

        var tabContainer = $('<div id="featureInfoTabContainer" class="tabContainer featureInfoTabContainer">' + 
                               '<ul class="tabs"></ul>' + 
                             '</div>');
        var header       = tabContainer.find(".tabs");
        var layers       = this.map.layers();
        var newTab, newContainer;

        // XXXVH: Need to optimize this section for better performance!
        // Go over all layers
        $.each(this.map.layers(), function(idx, layer) {
            if(!layer.visible()) {
                return;
            }
            if(!layer.olLayer.queryLayers || layer.olLayer.queryLayers.length === 0) {
                return;
            }
            fi_exist = true;
            // Prepare result tab list
            newTab          = $('<li id="tab' + layer.id + '" class="tab">' + layer.label + '</li>');
            newContainer = $('<div id="container' + layer.id + '" class="container"></div>');

            header.append(newTab);
            tabContainer.append(newContainer);

            switch(layer.options.type) {
                case 'wms':
                    self.element.find('a[href=#' + layer.id + ']').addClass('loading');
                    Mapbender.source.wms.featureInfo(layer, x, y, $.proxy(self._featureInfoCallback, self));
                    break;
            }
        });

        if(fi_exist){
            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup('showHint', {title:"Detail information", showHeader:true, content: tabContainer, modal:false, width:600});
            } else {
                $("body").mbPopup();
                $("body").mbPopup('showHint', {title:"Detail information", showHeader:true, content: "sss", modal:false});

                if(this.options.deactivateOnClose) {
                    this.element.bind('dialogclose', $.proxy(this.deactivate, this));
                }
            }
        } else {
            $("body").mbPopup();
            $("body").mbPopup('showHint', {title:"Detail information", showHeader:true, content: '<p class="description">No feature info layer exists.</p>', modal:false});
        }
    },

    /**
 * Once data is coming back from each layer's FeatureInfo call,
 * insert it into the corresponding tab.
 */
    _featureInfoCallback: function(data) {
        var text = '';
        try { // cut css
            text = data.response.replace(/document.writeln[^;]*;/g, '')
            .replace(/\n/g, '')
            .replace(/<link[^>]*>/gi, '')
            .replace(/<style[^>]*(?:[^<]*<\/style>|>)/gi, '');
        } catch(e) {}
        //TODO: Needs some escaping love
        $('#container' + data.layerId).removeClass('loading').html(text);
    }
});
})(jQuery);

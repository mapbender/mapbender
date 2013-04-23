(function($) {

$.widget("mapbender.mbFeatureInfo", $.ui.dialog, {//$.mapbender.mbButton, {
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
        //        this._super('activate');
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
        var tabs = $('<div></div>').attr('id', 'featureinfo-tabs').appendTo(this.element);
        var header = $('<ol></ol>').appendTo(tabs);

        // Go over all layers
        $.each(this.map.layers(), function(idx, layer) {
            if(!layer.visible()) {
                return;
            }

            var queryLayers = [];
            $.each(layer.options.allLayers, function(idx, l) {
                if(l.queryable === true && $.inArray(l.name, layer.olLayer.params.LAYERS) !== -1) {
                    queryLayers.push(l.name);
                }
            });
            if(queryLayers.length === 0) {
                return;
            }
            fi_exist = true;
            // Prepare result tab list
            header.append($('<li></li>').append($('<a></a>').attr('href', '#' + layer.id).html(layer.label)));
            tabs.append($('<div></div>').attr('id', layer.id));

            switch(layer.options.type) {
                case 'wms':
                    self.element.find('a[href=#' + layer.id + ']').addClass('loading');
                    Mapbender.source.wms.featureInfo(layer, x, y, $.proxy(self._featureInfoCallback, self));
                    break;
            }
        });
        if(fi_exist){
            tabs.tabs();
        } else {
            $('<p>No FeatureInfo layer exists.</p>').appendTo(this.element);
        }
        if(this.element.data('dialog')) {
            this.element.dialog('open');
        } else {
            this.element.dialog({
                title: 'Detail-Information',
                width: 600,
                height: 400
            });
            if(this.options.deactivateOnClose) {
                this.element.bind('dialogclose', $.proxy(this.deactivate, this));
            }
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
        this.element.find('a[href=#' + data.layerId + ']').removeClass('loading');
        this.element.find('#' + data.layerId).html(text);
    }
});
})(jQuery);

(function($) {
$.widget("mapbender.mbToc", $.ui.dialog, {
    options: {
        title: 'Table of Contents',
        autoOpen: true,
        target: null
    },

    mapDiv: null,
    map: null,

    _create: function() {
        if(this.options.target === null
            || new String(this.options.target).replace(/^\s+|\s+$/g, '') === ""
            || !$('#' + this.options.target)){
            Mapbender.error('The target element "map" is not defined for a ToC.');
            return;
        }
        var self = this;
        var me = $(this.element);
        me.find('div#open-button .button')
            .click($.proxy(this.open, this));
        me.find('div#frame .button')
            .click($.proxy(this.close, this));

        this._super('_create');

        this.mapDiv = $('#' + this.options.target);
        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
    },

    _setup: function() {
        this.map = this.mapDiv.data('mapQuery');
        var wmcStorage = $('.mb-element-wmc-storage');
        wmcStorage.bind('mbwmcstorageloaddone', $.proxy(this.reload, this));

        this.reload();
    },

    reload: function() {
        var me = $(this.element);
        var self = this;
        var services = me.find('div#services').hide();

        services.empty();

        $.each(this.map.layers(), function(idx, layer) {
            self._addLayer.call(self, layer);
        });

        if(services.data('accordion')) {
            services.accordion('destroy');
        }

        // inputs in accordion headers need to stop the event to be triggered
        me.find('h3 span input').click(function(evt){ evt.stopPropagation(); });
        me.find('input').change($.proxy(this._onChange, this));

        services.accordion({
            //autoHeight: false,
            clearStyle: true,
            collapsible: true,
            active: false

        });
        services.show();
    },

    _onChange: function(event) {
        var me = $(event.target);
        var onOff = me.filter(':checked').length !== 0;

        var serviceId = (me.hasClass('service') ?
            me.parents('h3').attr('id') :
            me.closest('div').prev('h3').attr('id'));
        var services = this.map.layers();
        var service = null;
        $.each(this.map.layers(), function(idx, s) {
            if(s.id == serviceId) {
                service = s;
            }
        });

        if(me.hasClass('service')) {
            // Service visibility
            if(service.olLayer.isBaseLayer) {
                service.olLayer.map.setBaseLayer(service.olLayer);
            } else {
                service.visible(onOff);
            }
        } else {
            // Layers
            var layers = [];
            me.closest('ol').find('li').each(function() {
                var li = $(this);
                if(li.find('input').filter(':checked').length) {
                    layers.push(li.data('layer'));
                }
            });
            layers.reverse();
            service.olLayer.params.LAYERS = layers;
            service.olLayer.redraw(true);
        }
    },

    _addLayer: function(layer) {
        var services = $(this.element).find('div#services');
        var bl = layer.options.isBaseLayer;
        var li = $('<h3></h3>')
            .attr('id', layer.id)
        var ctrl = $('<input></input>')
            .attr('type', bl ? 'radio' : 'checkbox')
            .attr('name', bl ? 'baselayer' : layer.id)
            .addClass('service');
        if(layer.olLayer.getVisibility()) {
            ctrl.attr('checked', 'checked');
        }
        var label = $('<a></a>')
            .attr('href', '#')
            .html(layer.label)
            .appendTo(li);

        $('<span></span>')
            .append(ctrl)
            .append(label)
            .appendTo(li);

        if(layer.options.allLayers) {
            var layers = $('<ol><ol>');
            $.each(layer.options.allLayers.reverse(), function(idx, sublayer) {
                var li = $('<li></li>')
                    .addClass('layer')
                    .data('layer', sublayer.name);
                var cb = $('<input></input>')
                    .attr('type', 'checkbox')
                    .addClass('layer')
                    .appendTo(li);
                if($.inArray(sublayer.name, layer.options.layers) !== -1) {
                    cb.attr('checked', 'checked');
                }
                var span = $('<span></span>')
                    .html(sublayer.title)
                    .appendTo(li);
                layers.prepend(li);
            });
        }

        services.append(li);
        services.append($('<div></div>').append(layers));
    }
});

})(jQuery);


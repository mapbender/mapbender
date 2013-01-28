(function($) {

$.widget("mapbender.mbRuler", {
    options: {
        target: null,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined,
        immediate: true,
        persist: true,
        title: 'Measurement',
        type: 'line',
        precision: 2
    },

    control: null,
    map: null,
    segments: null,
    total: null,

    _create: function() {
        if(this.options.type !== 'line' && this.options.type !== 'area') {
            throw 'mbRuler: Type must be line or area.';
        }
        if(this.options.target === null
            || this.options.target.replace(/^\s+|\s+$/g, '') === ""
            || !$('#' + this.options.target)){
            alert('The target element "map" is not defined for a Ruler ' + this.options.type + '.');
            return;
        }

        var sm = $.extend(true, {}, OpenLayers.Feature.Vector.style, {
            'default': this.options.style
        });
        var styleMap = new OpenLayers.StyleMap(sm);

        var handler = (this.options.type === 'line' ? OpenLayers.Handler.Path :
            OpenLayers.Handler.Polygon);

        this.control = new OpenLayers.Control.Measure(handler, {
            callbacks: {
                modify: function(point, feature, drawing) {
                    // Monkey patching, so modify uses a different event than
                    // the point handler. Sad, but true.
                    if (drawing && this.delayedTrigger === null &&
                        !this.handler.freehandMode(this.handler.evt)) {
                        this.measure(feature.geometry, "measuremodify");
                    }
                }
            },
            // This, too, is part of the monkey patch - unregistered event
            // types wont fire
            EVENT_TYPES: OpenLayers.Control.Measure.prototype.EVENT_TYPES
                .concat(['measuremodify']),
            handlerOptions: {
                layerOptions: {
                    styleMap: styleMap
                }
            },

            persist: this.options.persist,
            immediate: this.options.immediate
        });

        this.control.events.on({
            'scope': this,
            'measure': this._handleFinal,
            'measurepartial': this._handlePartial,
            'measuremodify': this._handleModify
        });

        this.map = $('#' + this.options.target);

        this.segments = $('<ul/>').appendTo(this.element);
        if(this.options.type === 'area') {
            // We don't want to show partials for areas
            this.segments.hide();
        }

        this.total = $('<div/>').appendTo(this.element);

        this.element.dialog({
            autoOpen: false,
            title: this.options.title
        });

        this.element.bind('dialogclose', $.proxy(this.deactivate, this));
    },

    /**
     * This activates this button and will be called on click
     */
    activate: function() {
        var olMap = this.map.data('mapQuery').olMap;
        olMap.addControl(this.control);
        this.control.activate();

        this._reset();
        this.element.dialog('open');
    },

    /**
     * This deactivates this button and will be called if another button of
     * this group is activated.
     */
    deactivate: function() {
        var olMap = this.map.data('mapQuery').olMap;
        this.control.deactivate();
        olMap.removeControl(this.control);

        if(this.element.dialog('isOpen')) {
            this.element.dialog('close');
        }
    },

    _reset: function() {
        this.segments.empty();
        this.total.html('');
    },

    _handleModify: function(event) {
        if(event.measure === 0.0) {
            return;
        }

        var measure = this._getMeasureFromEvent(event);
        this.total.html(measure);
    },

    _handlePartial: function(event) {
        if(event.measure == 0) {
            // if first point
            this._reset();
            return;
        }

        var measure = this._getMeasureFromEvent(event);
        this.segments.append($('<li/>', {
            html: measure
        }));
    },

    _handleFinal: function(event) {
        var measure = this._getMeasureFromEvent(event);
        this.total.html(measure);
    },

    _getMeasureFromEvent: function(event) {
        var measure = event.measure,
            units = event.units,
            order = event.order;

        measure = measure.toFixed(this.options.precision) + " " + units;
        if(order > 1) {
            measure += "<sup>" + order + "</sup>";
        }

        return measure;
    }
});

})(jQuery);


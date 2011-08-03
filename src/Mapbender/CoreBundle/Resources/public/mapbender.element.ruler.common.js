(function($) {

$.widget("mapbender.mbCommonRuler", $.mapbender.mbButton, {
    options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined,
        immediate: true,
        persist: true,
        title: 'Measurement'
    },

    control: null,
    map: null,
    dlg: null,

    _create: function(control) {
        if(typeof control === 'undefined') {
            throw "Control can not be undefined (mapbender.mbCommonRuler)";
        }
        this.control = control;

        this.control.persist = this.options.persist;
        this.control.handlerOptions.persist = this.control.persist;
        this.control.handler.persist = this.control.persist;

        this.control.setImmediate(this.options.immediate);
        
        this.control.events.on({
            'scope': this,
            'measure': this._handleMeasurements,
            'measurepartial': this._handleMeasurements
        });

        this.map = $('#' + this.options.target);

        this._super('_create');
    },

    /**
     * This activates this button and will be called on click
     */
    activate: function() {
        var olMap = this.map.data('mapQuery').olMap;
        olMap.addControl(this.control);
        this.control.activate();

        if(this.dlg === null) {
            this._createDialog();
        }
        this.dlg.empty();
        this.dlg.dialog('open');
    },

    /**
     * This deactivates this button and will be called if another button of
     * this group is activated.
     */
    deactivate: function() {
        //this._super('deactivate');
        var olMap = this.map.data('mapQuery').olMap;
        this.control.deactivate();
        olMap.removeControl(this.control);

        if(this.dlg !== null) {
            this.dlg.dialog('close');
        }
    },

    _handleMeasurements: function(event) {
        var measure = event.measure,
            units = event.units,
            order = event.order;

        var message = measure.toFixed(2) + " " + units;
        if(order > 1) {
            message += "<sup>" + order + "</sup>";
        }
        this.dlg.html(message);
    },

    _createDialog: function() {
        this.dlg = $('<div></div>')
            .attr('id', $(this.element).attr('id') + '-dialog')
            .dialog({
                width: 200,
                height: 100,
                autoOpen: false,
                title: this.options.title
            });
    }
});

})(jQuery);


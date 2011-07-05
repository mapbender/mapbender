(function($) {

$.widget("mapbender.mb{{ widgetName }}", $.mapbender.mbButton, {
    options: {
        target: undefined,
        click: undefined,
        icon: undefined,
        label: true,
        group: undefined
    },

    _create: function() {
        this._super('_create');
    },

    /**
     * This activates this button and will be called on click
     */
    activate: function() {
        /**
         * This is a demo: Call the targets action if this is configured
         */
        if(this.options.target && this.options.action) {
            var target = $('#' + this.options.target);
            var widget = Mapbender.configuration.elements[this.options.target].init;
            target[widget](this.options.action);
        }
    },

    /**
     * This deactivates this button and will be called if another button of
     * this group is activated.
     */
    deactivate: function() {
    }
});

})(jQuery);


(function($) {

$.widget("mapbender.mbActivityIndicator", {
    options: {
        activityClass: 'mb-activity',
        ajaxActivityClass: 'mb-activity-ajax',
        tileActivityClass: 'mb-activity-tile'
    },

    elementUrl: null,
    ajaxActivity: false,
    tileActivity: false,

    _create: function() {
        this.element.bind('ajaxStart', $.proxy(this._onAjaxStart, this));
        this.element.bind('ajaxStop', $.proxy(this._onAjaxStop, this));

        //TODO: Listen to layer tile events
    },

    _destroy: function() {
        var classes = [
            this.options.activityClass,
            this.options.ajaxActivityClass,
            this.options.tileActivityClass];
        $('body').removeClass(classes.join(' '));
    },

    /**
     * Listener for global ajaxStart events
     */
    _onAjaxStart: function() {
        this.ajaxActivity = true;
        this._updateBodyClass();
    },

    /**
     * Listener for global ajaxStop events
     */
    _onAjaxStop: function() {
        this.ajaxActivity = false;
        this._updateBodyClass();
    },

    /**
     * Update body classes to match current activity
     */
    _updateBodyClass: function() {
        var body = $('body'),
            hasAjaxClass = body.hasClass(this.options.ajaxActivityClass),
            hasTileClass = body.hasClass(this.options.ajaxActivityClass),
            hasActivityClass = body.hasClass(this.options.activityClass);

        if(this.ajaxActivity !== hasAjaxClass) {
            body.toggleClass(this.options.ajaxActivityClass);
        }

        if(this.tileActivity !== hasTileClass) {
            body.toggleClass(this.options.tileActivityClass);
        }

        if((this.tileActivity || this.ajaxActivity) !== hasActivityClass) {
            body.toggleClass(this.options.activityClass);
        }
    }
});

})(jQuery);


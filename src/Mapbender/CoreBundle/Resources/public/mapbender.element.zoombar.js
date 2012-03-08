(function($) {

$.widget("mapbender.mbZoomBar", {
    options: {
        stepSize: 50,
        stepByPixel: false,
        position: [0, 0],
        draggable: true},

    map: null,
    zoomslider: null,
    navigationHistoryControl: null,

    _create: function() {
        var self = this;

        this.map = $('#' + this.options.target).data('mbMap').map.olMap;
        this._setupSlider();
        this._setupZoomButtons();
        this._setupPanButtons();
        this.map.events.register('zoomend', this, this._onZoomEnd);
        this._onZoomEnd();

        if(this.options.draggable === true) {
            this.element.draggable({
                containment: this.element.closest('.region'),
                start: function() { $(this).add('dragging'); }
            });
        }

        this.element.css({
            left: this.options.position[0],
            top: this.options.position[1]});
    },

    _destroy: $.noop,

    _setupSlider: function() {
        this.zoomslider = this.element.find('ol.zoom-slider')
            .hide()
            .empty();

        for(var i = 0; i < this.map.getNumZoomLevels(); i++) {
            this.zoomslider.append($('<li></li>'));
        }
        this.zoomslider.show();

        var self = this;
        this.zoomslider.find('li').click(function() {
            var li = $(this);
            var index = li.index();
            var position = self.map.getNumZoomLevels() - 1 - index;
            self.map.zoomTo(position);
        });
    },

    _click: function(call) {
        if(this.element.hasClass('dragging')) {
            this.element.removeClass('dragging');
            return false;
        }
        call();
    },

    _setupZoomButtons: function() {
        this.navigationHistoryControl =
            new OpenLayers.Control.NavigationHistory();
        this.map.addControl(this.navigationHistoryControl);

        this.element.find('div.zoom-prev').bind('click',
            $.proxy(this.navigationHistoryControl.previousTrigger,
            this.navigationHistoryControl));
        this.element.find('div.zoom-next').bind('click',
            $.proxy(this.navigationHistoryControl.nextTrigger,
            this.navigationHistoryControl));

        this.element.find('div.zoom-in a').bind('click',
            $.proxy(this.map.zoomIn, this.map));
        this.element.find('div.zoom-out a').bind('click',
            $.proxy(this.map.zoomOut, this.map));
    },

    _setupPanButtons: function() {
        var self = this;
        var pan = $.proxy(this.map.pan, this.map);
        var stepSize = {
            x: this.options.stepSize,
            y: this.options.stepSize};

        if(!this.options.stepByPixel) {
            stepSize = {
                x: Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                    this.map.getSize().w,
                y: Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                    this.map.getSize().h};
        }

        this.element.find('div.pan a').click(function() {
            var type = $(this).attr('class');
            switch(type) {
                case 'pan-up':
                    pan(0, -stepSize.y);
                    break;
                case 'pan-right':
                    pan(stepSize.x, 0);
                    break;
                case 'pan-down':
                    pan(0, stepSize.y);
                    break;
                case 'pan-left':
                    pan(-stepSize.x, 0);
                    break;
            }
            return false;
        });
    },

    _onZoomEnd: function() {
        var position = this.map.getNumZoomLevels() - 1 - this.map.getZoom();
        this.zoomslider.find('li.active').removeClass('active');
        this.zoomslider.find('li').eq(position).addClass('active');
    }
});

})(jQuery);


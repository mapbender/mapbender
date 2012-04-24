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
    zoomBoxControl: null,

    _create: function() {
        var self = this;

        this.map = $('#' + this.options.target).data('mbMap').map.olMap;
        this._setupSlider();
        this._setupZoomButtons();
        this._setupPanButtons();
        this.map.events.register('zoomend', this, this._zoom2Slider);
        this._zoom2Slider();

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
        this.zoomslider.find('li').last()
            .addClass('active')
            .append($('<div></div>'));

        var step = [
            this.zoomslider.find('li').last().width(),
            this.zoomslider.find('li').last().height()];

        this.zoomslider.sortable({
                axis: 'y', // TODO: Orientation
                containment: this.zoomslider,
                grid: step,
                handle: 'div',
                tolerance: 'pointer',
                stop: $.proxy(this._slider2Zoom, this)
            });
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
        var self = this;

        this.navigationHistoryControl =
            new OpenLayers.Control.NavigationHistory();
        this.map.addControl(this.navigationHistoryControl);

        this.zoomBoxControl = new OpenLayers.Control();
        OpenLayers.Util.extend(this.zoomBoxControl, {
            handler: null,
            autoActivate: false,

            draw: function() {
                this.handler = new OpenLayers.Handler.Box(this, {
                    done: $.proxy(self._zoomToBox, self) }, {
                    keyMask: OpenLayers.Handler.MOD_NONE});
            },

            CLASS_NAME: 'Mapbender.Control.ZoomBox',
            displayClass: 'MapbenderControlZoomBox'
        });

        this.map.addControl(this.zoomBoxControl);
        this.element.find('div.zoom-box').bind('click', function() {
            $(this).toggleClass('active');
            if($(this).hasClass('active')) {
                self.zoomBoxControl.activate();
            } else {
                self.zoomBoxControl.deactivate();
            }
        });

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

    _zoomToBox: function(position) {
        var zoom, center;
        if(position instanceof OpenLayers.Bounds) {
            var minXY = this.map.getLonLatFromPixel(
                new OpenLayers.Pixel(position.left, position.bottom));
            var maxXY = this.map.getLonLatFromPixel(
                new OpenLayers.Pixel(position.right, position.top));
            var bounds = new OpenLayers.Bounds(minXY.lon, minXY.lat,
                maxXY.lon, maxXY.lat);
            zoom = this.map.getZoomForExtent(bounds);
            center = bounds.getCenterLonLat();
        } else {
            zoom = this.map.getZoom() + 1;
            center = this.map.getLonLatFromPixel(position);
        }

        this.map.setCenter(center, zoom);

        this.zoomBoxControl.deactivate();
        this.element.find('div.zoom-box').removeClass('active');
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

    /**
     * Set map zoom level from slider
     */
    _slider2Zoom: function() {
        var position = this.zoomslider.find('li.active').index(),
            index = this.map.getNumZoomLevels() - 1 - position;

        this.map.zoomTo(index);
    },

    /**
     * Set slider to reflect map zoom level
     */
    _zoom2Slider: function() {
        var position = this.map.getNumZoomLevels() - 1 - this.map.getZoom();

        this.zoomslider.find('li.active')
            .removeClass('active')
            .empty();
        this.zoomslider.find('li').eq(position)
            .addClass('active')
            .append($('<div></div>'));
    }
});

})(jQuery);


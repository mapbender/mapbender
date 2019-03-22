(function($) {

$.widget("mapbender.mbZoomBar", {
    options: {
        target: null,
        stepSize: 50,
        stepByPixel: false,
        position: [0, 0],
        draggable: true},

    map: null,
    zoomslider: null,
    navigationHistoryControl: null,
    zoomBoxControl: null,
    mbMap: null,

    _create: function() {
        if(!Mapbender.checkTarget("mbZoomBar", this.options.target)){
            return;
        }
        var self = this;
        Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
            self.mbMap = mbMap;
            self._setup();
        });
    },

    _setup: function() {
        this.map = this.mbMap.map.olMap;        // @todo: no direct access to OpenLayers map
        this._setupSlider();
        this._setupZoomButtons();
        this._setupPanButtons();
        this.map.events.register('zoomend', this, this._zoom2Slider);
        this._zoom2Slider();

        if(this.options.draggable === true) {
            this.element.addClass("iconMove").draggable({
                containment: this.element.closest('.region'),
                start: function() {
                    $(this).css("right", "inherit");
                }
            });
        }
        $(this.element).find('.iconZoomMin').bind("click" ,$.proxy(this._worldZoom, this));

        this._trigger('ready');
    },

    _destroy: $.noop,

    _worldZoom: function(e) {
        this.map.zoomToMaxExtent();
    },

    _setupSlider: function() {
        this.zoomslider = this.element.find('.zoomSlider .zoomSliderLevels')
            .hide()
            .empty();

        var levelsNumber = this.map.getNumZoomLevels();
        for(var i = 0; i < levelsNumber; i++) {
            var resolution = this.map.getResolutionForZoom(levelsNumber - i - 1);
            var scale = Math.round(OpenLayers.Util.getScaleFromResolution(resolution, this.map.units));
            this.zoomslider.append($('<li class="iconZoomLevel" title="1:' + scale + '"/>'));
        }

        this.zoomslider.find('li').last()
            .addClass('iconZoomLevelSelected')
        ;

        this.zoomslider.show();

        var self = this;
        this.zoomslider.find('li').click(function() {
            var li = $(this);
            var index = li.index();
            var position = self.map.getNumZoomLevels() - 1 - index;
            self.map.zoomTo(position);
        });
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
        this.element.find('.zoomBox').bind('click', function() {
            $(this).toggleClass('activeZoomIcon');
            if($(this).hasClass('activeZoomIcon')) {
                self.zoomBoxControl.activate();
            } else {
                self.zoomBoxControl.deactivate();
            }
        });

        this.element.find(".history .historyPrev").bind("click", function(){
            self.navigationHistoryControl.previous.trigger();
        });
        this.element.find(".history .historyNext").bind("click", function(){
            self.navigationHistoryControl.next.trigger();
        });

        this.element.find('.zoomSlider .iconZoomIn').bind('click',
            $.proxy(this.map.zoomIn, this.map));
        this.element.find('.zoomSlider .iconZoomOut').bind('click',
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
        this.element.find('.zoomBox').removeClass('activeZoomIcon');
    },

    _setupPanButtons: function() {
        var self = this;
        var pan = $.proxy(this.map.pan, this.map);
        var stepSize = {
            x: parseInt(this.options.stepSize),
            y: parseInt(this.options.stepSize)};

        if(this.options.stepByPixel === "false") {
            stepSize = {
                x: Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                    this.map.getSize().w,
                y: Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                    this.map.getSize().h};
        }

        this.element.find(".panUp").bind("click", function(){pan(0, -stepSize.y);});
        this.element.find(".panRight").bind("click", function(){pan(+stepSize.x, 0);})
        this.element.find(".panDown").bind("click", function(){pan(0, +stepSize.y);})
        this.element.find(".panLeft").bind("click", function(){pan(-stepSize.x, 0);})
    },

    /**
     * Set slider to reflect map zoom level
     */
    _zoom2Slider: function() {
        var position = this.map.getNumZoomLevels() - 1 - this.map.getZoom();

        this.zoomslider.find('.iconZoomLevelSelected')
            .removeClass('iconZoomLevelSelected')
            .empty();
        this.zoomslider.find('li').eq(position)
            .addClass('iconZoomLevelSelected')
            .append($('<div></div>'));
    }
});

})(jQuery);

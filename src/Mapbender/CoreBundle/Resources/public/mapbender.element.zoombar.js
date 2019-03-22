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

        var zoomLevels = this.mbMap.getModel().getZoomLevels();
        for (var i = zoomLevels.length - 1; i >= 0; --i) {
            var $zoomLi = $('<li>')
                .addClass('iconZoomLevel')
                .attr('title', '1:' + zoomLevels[i].scale)
                .attr('data-zoom', zoomLevels[i].level)
            ;
            this.zoomslider.append($zoomLi);
        }

        this.zoomslider.find('li').last()
            .addClass('iconZoomLevelSelected')
        ;

        this.zoomslider.show();

        var self = this;
        this.zoomslider.find('li').click(function() {
            var zoomLevel = parseInt($(this).attr('data-zoom'));
            self.mbMap.getModel().setZoomLevel(zoomLevel);
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
        var zoom, center, model = this.mbMap.getModel();
        if(position instanceof OpenLayers.Bounds) {
            var minXY = this.map.getLonLatFromPixel(
                new OpenLayers.Pixel(position.left, position.bottom));
            var maxXY = this.map.getLonLatFromPixel(
                new OpenLayers.Pixel(position.right, position.top));
            var bounds = new OpenLayers.Bounds(minXY.lon, minXY.lat,
                maxXY.lon, maxXY.lat);
            model.setExtent(bounds);
        } else {
            zoom = model.getCurrentZoomLevel() + 1;
            center = this.map.getLonLatFromPixel(position);
            model.centerXy(center.lon, center.lat, {
                zoom: zoom
            });
        }

        this.zoomBoxControl.deactivate();
        this.element.find('.zoomBox').removeClass('activeZoomIcon');
    },
    _setupPanButtons: function() {
        var self = this;
        this.element.on('click', '.panUp', function() {
            self._pan(0, -1);
        });
        this.element.on('click', '.panRight', function() {
            self._pan(1, 0);
        });
        this.element.on('click', '.panDown', function() {
            self._pan(0, 1);
        });
        this.element.on('click', '.panLeft', function() {
            self._pan(-1, 0);
        });
    },
    _pan: function(stepsX, stepsY) {
        var stepSize = {
            x: parseInt(this.options.stepSize),
            y: parseInt(this.options.stepSize)
        };
        if (!this.options.stepByPixel) {
            stepSize.x = Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                this.map.getSize().w;
            stepSize.y = Math.max(Math.min(stepSize.x, 100), 0) / 100.0 *
                this.map.getSize().h;
        }
        this.map.pan(stepsX * stepSize.x, stepsY * stepSize.y);
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

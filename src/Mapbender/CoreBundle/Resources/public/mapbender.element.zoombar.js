(function($) {

$.widget("mapbender.mbZoomBar", {
    options: {
        target: null,
        draggable: true
    },

    zoomslider: null,
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
        var self = this;
        this._setupSlider();
        this._setupZoomButtons();
        $(document).on('mbmapzoomchanged', function(e, data) {
            if (data.mbMap === self.mbMap) {
                self._zoom2Slider();
            }
        });
        this._zoom2Slider();

        if (this.options.draggable === true) {
            this.element.draggable();
        }
        this._initRotation();
        this._trigger('ready');
    },

    _destroy: $.noop,

    _worldZoom: function(e) {
        this.mbMap.zoomToFullExtent();
    },
    _setupSlider: function() {
        var self = this;
        this.zoomslider = $('.zoomSliderLevels', this.element);
        this.zoomslider.on('click', '[data-zoom]', function() {
            var zoomLevel = parseInt($(this).attr('data-zoom'));
            self.mbMap.getModel().setZoomLevel(zoomLevel, true);
        });
    },
    _initRotation: function() {
        var $rotationElement = $('.rotation', this.element);
        if (!$rotationElement.length) {
            return;
        }
        var engine = Mapbender.mapEngine;
        if (!engine.supportsRotation()) {
            throw new Error("Rotation not supported on current engine " + engine.code);
        }
        var model = this.mbMap.getModel();
        $('[data-degrees]', $rotationElement).on('click', function() {
            var increment = parseInt($(this).attr('data-degrees'));
            var degrees = increment + model.getViewRotation();
            model.setViewRotation(degrees, true);
        });
        var $resetElement = $('.reset-rotation', $rotationElement);
        var rotationBias = parseInt($resetElement.attr('data-rotation-bias') || '0');

        $('.reset-rotation', $rotationElement).on('click', function() {
            model.setViewRotation(0, true);
        });
        this.mbMap.element.on('mbmaprotationchanged', function() {
            var degrees = model.getViewRotation() + rotationBias;
            $('i',$resetElement).css({
                transform: 'rotate(' + degrees + 'deg)'
            });
        });
    },
    _setupZoomButtons: function() {
        var self = this;
        this.element.on('click', '.zoom-in', function() {
            self.mbMap.getModel().zoomIn();
        });
        this.element.on('click', '.zoom-out', function() {
            self.mbMap.getModel().zoomOut();
        });
        this.element.on('click', '.zoom-world', function() {
            self._worldZoom();
        });
    },
    /**
     * Set slider to reflect map zoom level
     */
    _zoom2Slider: function() {
        var zoomLevel = this.mbMap.getModel().getCurrentZoomLevel();
        var $activeItem = $('[data-zoom="' + zoomLevel + '"]', this.zoomslider);
        $('li', this.zoomslider).not($activeItem).removeClass('iconZoomLevelSelected');
        $activeItem.addClass('iconZoomLevelSelected');
    }
});

})(jQuery);

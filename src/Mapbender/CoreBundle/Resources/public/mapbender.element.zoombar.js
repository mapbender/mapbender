(function($) {

$.widget("mapbender.mbZoomBar", {
    options: {
        target: null,
        stepSize: 50,
        stepByPixel: false,
        position: [0, 0],
        draggable: true},

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
        this._setupPanButtons();
        $(document).on('mbmapzoomchanged', function(e, data) {
            if (data.mbMap === self.mbMap) {
                self._zoom2Slider();
            }
        });
        this._zoom2Slider();

        if (this.options.draggable === true) {
            this.element.draggable({
                containment: this.element.closest('.region'),
                start: function() {
                    // draggable operates by modifying 'left' css property
                    // disable any 'right' property value (from anchor-top-right) to keep width constant
                    self.element.css({right: 'initial'});
                }
            });
        }
        this._initRotation();
        this._trigger('ready');
    },

    _destroy: $.noop,

    _worldZoom: function(e) {
        this.mbMap.zoomToFullExtent();
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

        this.zoomslider.show();

        var self = this;
        this.zoomslider.find('li').click(function() {
            var zoomLevel = parseInt($(this).attr('data-zoom'));
            self.mbMap.getModel().setZoomLevel(zoomLevel, true);
        });
    },
    _initRotation: function() {
        var $rotationElement = $('.rotation', this.element);
        if (!$rotationElement.length) {
            return;
        }
        var engineCode = Mapbender.mapEngine.code;
        var engineSupportsRotation = engineCode === 'ol4';
        var deg2rad = function(x) {
            return x * Math.PI / 180;
        };
        var rad2deg = function(x) {
            return x * 180 / Math.PI;
        };
        if (!engineSupportsRotation) {
            throw new Error("Rotation is only supported on ol4 engine, not on current engine " + engineCode);
        }
        var olMap = this.mbMap.getModel().olMap;
        $('[data-degrees]', $rotationElement).on('click', function() {
            var increment = parseInt($(this).attr('data-degrees'));
            var view = olMap.getView();
            var rotationCurrentRadians = view.getRotation();
            var rotationNewRadians = rotationCurrentRadians + deg2rad(increment);
            view.animate({rotation: rotationNewRadians, duration: 400});
        });
        var $resetElement = $('.reset-rotation', $rotationElement);
        var rotationBias = parseInt($resetElement.attr('data-rotation-bias') || '0');

        $('.reset-rotation', $rotationElement).on('click', function() {
            var view = olMap.getView();
            view.animate({rotation: 0, duration: 400});
        });
        var displayRotation = function(e) {
            var degrees = rad2deg(e.target.getRotation()) + rotationBias;
            $('i',$resetElement).css({
                transform: 'rotate(' + degrees + 'deg)'
            });
        };

        olMap.getView().on('change:rotation', displayRotation);
        olMap.on('change:view', function(e) {
            displayRotation({target: olMap.getView()});
            e.target.getView().on('change:rotation', displayRotation);
        });
    },
    _setupZoomButtons: function() {
        var self = this;
        var model = this.mbMap.getModel();
        this.element.find('.zoomBox').bind('click', function() {
            $(this).toggleClass('activeZoomIcon');
            if($(this).hasClass('activeZoomIcon')) {
                model.zoomBoxOn();
            } else {
                model.zoomBoxOff();
            }
        });
        $(document).bind('mbmapafterzoombox', function(evt, data) {
            if (data.mbMap === self.mbMap) {
                $('.zoomBox', self.element).removeClass('activeZoomIcon');
            }
        });
        this.element.find(".history .historyPrev").bind("click", function() {
            self.mbMap.getModel().historyBack();
        });
        this.element.find(".history .historyNext").bind("click", function(){
            self.mbMap.getModel().historyForward();
        });
        this.element.find('.zoomSlider .iconZoomIn').bind('click', function() {
            self.mbMap.getModel().zoomIn();
        });
        this.element.find('.zoomSlider .iconZoomOut').bind('click', function() {
            self.mbMap.getModel().zoomOut();
        });
        this.element.find('.iconZoomMin').bind('click', function() {
            self._worldZoom();
        });
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
        if (this.options.stepByPixel) {
            this.mbMap.getModel().panByPixels(stepsX * stepSize.x, stepsY * stepSize.y);
        } else {
            this.mbMap.getModel().panByPercent(stepsX * Math.min(stepSize.x, 100), stepsY * Math.min(stepSize.y, 100));
        }
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

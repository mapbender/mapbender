(function() {

    class MbZoombar extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, function() {
                Mapbender.checkTarget('mbZoomBar');
            });
        }

        _setup() {
            var self = this;
            this.configuredMapSettings = this.mbMap.getModel().getConfiguredSettings();

            this._setupSlider();
            this._setupZoomButtons();
            $(document).on('mbmapzoomchanged', function(e, data) {
                if (data.mbMap === self.mbMap) {
                    self._zoom2Slider();
                }
            });
            this._zoom2Slider();

            if (this.options.draggable === true) {
                this.$element.draggable({containment: '.mb-element-map'});
            }
            this._initRotation();
            Mapbender.elementRegistry.markReady(this);
        }

        _worldZoom(e) {
            this.mbMap.zoomToFullExtent();
        }

        _setupSlider() {
            var self = this;
            this.zoomslider = $('.zoomSliderLevels', this.$element);
            this.zoomslider.on('click', '[data-zoom]', function() {
                var zoomLevel = parseInt($(this).attr('data-zoom'));
                self.mbMap.getModel().setZoomLevel(zoomLevel, true);
            });
        }

        _initRotation() {
            var $rotationElement = $('.rotation', this.$element);
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
            var updateRotationDisplay = function() {
                var degrees = model.getViewRotation() + rotationBias;
                $('i',$resetElement).css({
                    transform: 'rotate(' + degrees + 'deg)'
                });
            };
            updateRotationDisplay();
            this.mbMap.element.on('mbmaprotationchanged', updateRotationDisplay);
        }

        _setupZoomButtons() {
            var self = this;
            this.$element.on('click', '.zoom-in', function() {
                self.mbMap.getModel().zoomIn();
            });
            this.$element.on('click', '.zoom-out', function() {
                self.mbMap.getModel().zoomOut();
            });
            this.$element.on('click', '.zoom-world', function() {
                self._worldZoom();
            });
            this.$element.on('click', '.-fn-zoom-home', function() {
                var m = self.mbMap.getModel();
                if (self.options.zoomHomeRestoresLayers) {
                    m.applySettings(self.configuredMapSettings);
                } else {
                    m.applyViewParams(self.configuredMapSettings.viewParams);
                }
            });
        }

        _zoom2Slider() {
            var zoomLevel = this.mbMap.getModel().getCurrentZoomLevel();
            var $activeItem = $('[data-zoom="' + zoomLevel + '"]', this.zoomslider);
            $('li', this.zoomslider).each(function(e) {
                const isActive = $activeItem.is(this);
                $(this).find('.js-zoombar-dot')
                    .toggleClass('fas', isActive)
                    .toggleClass('far', !isActive)
                ;
            });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbZoombar = MbZoombar;
})();

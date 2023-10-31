$(function () {
    var collectionSelector = '#form_configuration_dimensionsets .collectionItem';
    var selectSelector = 'select[data-name="group"]';
    var dimHandler = {
        update: function($item, dimension, minStep, maxStep) {
            var $storeInput = $('input[name*="[extent]"]', $item);
            var min = dimension.valueFromStep(minStep);
            var max = dimension.valueFromStep(maxStep);
            var defaultValue = dimension.getInRange(min, max, dimension.getMax());
            $storeInput.val([min, max, dimension.getResolutionText()].join('/'));
            var displayText = [[min, max, dimension.getResolutionText()].join('/'), defaultValue].join(' - ');
            $('input[data-name="extentDisplay"]', $item).val(displayText);
        },
        getSliderSettings: function($item) {
            var extent = $('input[name*="[extent]"]', $item).val();
            var parts = (extent || '').split('/');
            return (parts.length >= 2) && {
                min: parts[0],
                max: parts[1]
            };
        },
        getDimension: function($item) {
            var dimension;
            var $selected = $(selectSelector + ' option:selected', $item);
            for (var i = 0; i < $selected.length; ++i) {
                var dimensionOptions = JSON.parse($($selected.get(i)).attr('data-config'));
                var nextDim = Mapbender.Dimension(dimensionOptions);
                if (dimension) {
                    dimension = dimension.innerJoin(nextDim) || dimension;
                } else {
                    dimension = nextDim;
                }
            }
            return dimension;
        },
        initSlider: function ($item) {
            var self = this;
            var $slider = $('.mb-slider', $item);
            var currentSettings = this.getSliderSettings($item);
            var dimension = this.getDimension($item);

            if (dimension) {
                var sliderRange = [0, dimension.getStepsNum()];
                var sliderValues = currentSettings && [
                    Math.max(0, dimension.getStep(currentSettings.min)),
                    Math.min(sliderRange[1], dimension.getStep(currentSettings.max))
                ];
                sliderValues = sliderValues || sliderRange.slice();
                $slider.slider({
                    range: true,
                    min: sliderRange[0],
                    max: sliderRange[1],
                    values: sliderValues,
                    slide: function (event, ui) {
                        self.update($item, dimension, ui.values[0], ui.values[1]);
                    }
                });
                $slider.addClass('-created');
                this.update($item, dimension, sliderValues[0], sliderValues[1]);
            } else {
                if ($slider.hasClass('-created')) {
                    $slider.slider("destroy");
                    $slider.removeClass('-created');
                }
            }
        },
        initExtentInput: function (collection) {
            $('input[data-extent-range]', collection).attr('type', 'hidden');
            var extentInput = $('.row .row:last-child .col-sm-10', collection);
            var extentDisplay = '<input data-name="extentDisplay" type="text" readonly="readonly" disabled="disabled" class="form-control" />';
            var slider = '<div class="mb-slider" style="margin: 0.5em 0;"></div>';
            extentInput.append(extentDisplay);
            extentInput.append(slider);
        }
    };
    var usedValues = [];
    var updateCollection = function updateCollection() {
        var $selects = $([collectionSelector, selectSelector].join(' '));
        usedValues = [];

        $selects.each(function() {
            usedValues = usedValues.concat($(this).val());
        });
        usedValues = Mapbender.Util.array_unique(usedValues);
        $('option', $selects).each(function() {
            $(this).prop('disabled', (usedValues.indexOf(this.value) !== -1) && !$(this).is(':selected'));
        });
    };

    $(document).on('change', 'select[data-name="group"]', function (event) {
        var $item = $(event.target).closest('.collectionItem');
        dimHandler.initSlider($item);
        updateCollection();
        return false;
    });
    $(document).on('click', '.collectionAdd', function (event) {
        var $collection = $(event.currentTarget).closest('[data-prototype]');
        var newCollection = $('.collectionItem', $collection).last();
        dimHandler.initExtentInput(newCollection);
        var nOpts = $(selectSelector + ' > option').length;
        if (usedValues.length >= nOpts) {
            // return false;   // no worky, we can't prevent creation from here :\
        }
        $('select[data-name="group"]', newCollection).trigger('change');
    });
    $(collectionSelector).each(function(ix, item) {
        var $item = $(item);
        dimHandler.initExtentInput($item);
        dimHandler.initSlider($item);
    });
});

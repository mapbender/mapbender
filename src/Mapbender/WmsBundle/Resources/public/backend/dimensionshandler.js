$(function () {
    var collectionSelector = '#form_configuration_dimensionsets .collectionItem';
    var selectSelector = 'select[data-name="group"]';
    var dimHandler = {
        updateExtents: function ($item, extent, default_) {
            var text = extent && ([extent.join('/'), default_].join(' - ')) || '';
            $('input[data-name="extentDisplay"]', $item).val(text);
        },
        setVals: function ($item, dimension, extent) {
            var gopts;
            if (dimension) {
                gopts = dimension.getOptions();
                if (extent) {
                    gopts = _.assign({}, gopts, {extent: extent});
                }
                $('input[data-name="dimension"]', $item).val(JSON.stringify(gopts || ''));
                this.updateExtents($item, gopts.extent, dimension.getDefault());
            } else {
                $('input[data-name="dimension"]', $item).val('');
                this.updateExtents($item, null, null);
            }
        },
        getDimension: function($item) {
            var dimensionOptions = JSON.parse($('input[data-name="dimension"]', $item).val() || '""') || {};
            var dimension = Mapbender.Dimension(dimensionOptions);
            var $selected = $(selectSelector + ' option:selected', $item);
            for (var i = 0; i < $selected.length; ++i) {
                dimensionOptions = JSON.parse($($selected.get(i)).attr('data-config'));
                var nextDim = Mapbender.Dimension(dimensionOptions);
                if (!dimension) {
                    dimension = nextDim;
                } else if (nextDim) {
                    var merged = dimension.innerJoin(nextDim);
                    dimension = merged || dimension;
                }
            }
            return dimension;
        },
        initSlider: function ($item) {
            var self = this;
            var $slider = $('.mb-slider', $item);
            var dimension = this.getDimension($item);
            this.setVals($item, dimension);

            if (dimension) {
                var gopts = dimension.getOptions();
                var sliderValues = [dimension.getStep(gopts.extent[0]), dimension.getStep(gopts.extent[1])];
                var sliderRange = [dimension.getStep(gopts.origextent[0]), dimension.getStep(gopts.origextent[1])];
                $slider.slider({
                    range: true,
                    min: sliderRange[0],
                    max: sliderRange[1],
                    steps: 1,
                    values: sliderValues,
                    slide: function (event, ui) {
                        var extent = [dimension.valueFromStep(ui.values[0]), dimension.valueFromStep(ui.values[1]), gopts.origextent[2]];
                        dimension.setDefault(dimension.getInRange(extent[0], extent[1], dimension.getDefault()));
                        self.setVals($item, dimension, extent);
                    }
                });
                $slider.addClass('-created');
            } else {
                this.setVals($item, null);
                if ($slider.hasClass('-created')) {
                    $slider.slider("destroy");
                    $slider.removeClass('-created');
                }
            }
        }
    };
    var usedValues = [];
    var updateCollection = function updateCollection() {
        var $selects = $([collectionSelector, selectSelector].join(' '));
        usedValues = [];

        $selects.each(function(i, el) {
            var v = $(el).val();
            usedValues.push(v);
            $('option[value="' + v + '"]', $selects).not(':selected').prop('disabled', true);
        });
        usedValues = _.uniq(usedValues);
        $selects.each(function(i, el) {
            $('option', el).each(function(j, opt) {
                var $opt = $(opt);
                if (usedValues.indexOf($opt.attr('val')) === -1) {
                    $opt.prop('disabled', false);
                }
            });
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
        var nOpts = $(selectSelector + ' > option').length;
        if (usedValues.length >= nOpts) {
            // return false;   // no worky, we can't prevent creation from here :\
        }
        var $new = $('.collectionItem', $collection).last();    // :)
        $('select[data-name="group"]', $new).trigger('change');
    });
    $(collectionSelector).each(function(ix, item) {
        var $item = $(item);
        dimHandler.initSlider($item);
    });
});

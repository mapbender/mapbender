var dimHandler = null;
$(function () {
    var collectionSelector = '#form_configuration_dimensionsets .collectionItem';
    var selectSelector = 'select[data-name="group"]';
    dimHandler = {
        updateExtents: function ($item, extent, default_) {
            var text = extent && ([extent.join('/'), default_].join(' - ')) || '';
            $('input[data-name="extentDisplay"]', $item).val(text);
        },
        getInputValues: function($item) {
            return JSON.parse($('input[data-name="dimension"]', $item).val() || '""') || {};
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
        merge: function (dimension, $select) {
            var $selectedOptions = $('option:selected', $select);
            var dim = dimension;
            for (var i = 0; i < $selectedOptions.length; ++i) {
                var optionConfig = JSON.parse($($selectedOptions.get(i)).attr('data-config'));
                var dimConfig = _.assign({}, optionConfig);
                var nextDim = Mapbender.Dimension(dimConfig);
                if (!dim) {
                    dim = nextDim;
                } else if (nextDim) {
                    dim = dim.innerJoin(nextDim) || dim;
                }
            }
            return dim;
        },
        initItem: function($item) {
            var values = this.getInputValues($item);
            var dim = Mapbender.Dimension(values);
            $item.data('dimension-instance', dim);
        },
        initSlider: function ($item) {
            var self = this;
            var $slider = $('.mb-slider', $item);
            var $select = $(selectSelector, $item);
            var dimension = $item.data('dimension-instance');
            if ($select.val() && $select.val().length > 0) {
                dimension = this.merge(dimension, $select);
                $item.data('dimension-instance', dimension);
                this.setVals($item, dimension);
            }
            if (dimension) {
                var gopts = dimension.getOptions();
                var extentStepNum = dimension.getStepsNum();
                var sliderValues = [extentStepNum * dimension.partFromValue(gopts.extent[0]), extentStepNum * dimension.partFromValue(gopts.extent[1])];
                var sliderRange = [extentStepNum * dimension.partFromValue(gopts.origextent[0]), extentStepNum * dimension.partFromValue(gopts.origextent[1])];
                $slider.slider({
                    range: true,
                    min: sliderRange[0],
                    max: sliderRange[1],
                    steps: 1,
                    values: sliderValues,
                    slide: function (event, ui) {
                        var extent = [dimension.valueFromPart(ui.values[0] / extentStepNum), dimension.valueFromPart(ui.values[1] / extentStepNum), gopts.origextent[2]];
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
        dimHandler.initSlider($new);
        updateCollection();
    });
    $(collectionSelector).each(function(ix, item) {
        var $item = $(item);
        dimHandler.initItem($item);
        dimHandler.initSlider($item);
    });
});

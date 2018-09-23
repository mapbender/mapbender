var dimHandler = null;
$(function () {
    dimHandler = {
        updateExtents: function ($div, extent, default_) {
            var text = extent ? extent[0] + '/' + extent[1] + '/' + extent[2] + ' - ' + default_ : '';
            $('input[data-name="display"]', $div.parents('.collectionItem:first')).val(text);
            $('input[data-name="extent"]', $div).val(extent ? JSON.stringify(extent) : '');
            $('input[data-name="default"]', $div).val(default_ ? default_ : '');
        },
        getInputValues: function($div) {
            var r = {};
            var extent = JSON.parse($('input[data-name="extent"]', $div).val() || '""') || null;
            var origextent = JSON.parse($('input[data-name="origextent"]', $div).val() || '""') || null;
            var default_ = $('input[data-name="default"]', $div).val() || null;
            if (extent) {
                r.extent = extent;
            }
            if (origextent) {
                r.origextent = origextent;
            }
            if (default_) {
                r.default = default_;
            }
            return r;
        },
        setVals: function ($div, dimension) {
            $('input[data-name="extent"]', $div).val(!dimension ? "" : JSON.stringify(dimension.getOptions().extent));
            // $('input[data-name="origextent"]', $div).val(!dimension ? "" : JSON.stringify(dimension.getOptions().origextent));
            $('input[data-name="type"]', $div).val(!dimension ? "" : dimension.getOptions().type);
            $('input[data-name="current"]', $div).val(!dimension ? "" : dimension.getOptions().current);
            $('input[data-name="nearestValue"]', $div).val(!dimension ? "" : dimension.getOptions().nearestValue);
            $('input[data-name="multipleValues"]', $div).val(!dimension ? "" : dimension.getOptions().multipleValues);
            $('input[data-name="default"]', $div).val(!dimension ? "" : dimension.getOptions()['default']);
            $('input[data-name="unitSymbol"]', $div).val(!dimension ? "" : dimension.getOptions().unitSymbol);
            $('input[data-name="units"]', $div).val(!dimension ? "" : dimension.getOptions().units);
            $('input[data-name="name"]', $div).val(!dimension ? "" : dimension.getOptions().name);
        },
        generateGrouped: function ($select, values) {
            var dimsConfig;
            if (!$select.data('dims-config')) {
                var dimsJson = $select.attr('data-dimension-group');
                dimsConfig = JSON.parse(dimsJson);
                $select.data('dims-config', dimsConfig);
            } else {
                dimsConfig = $select.data('dims-config');
            }
            var selectedValues = $select.val() || [];
            var grouped = null;
            for (var i = 0; i < selectedValues.length; ++i) {
                var dimConfig = _.assign({}, dimsConfig[selectedValues[i]], values);
                var dim = Mapbender.Dimension(dimConfig);
                if (grouped) {
                    grouped = grouped.innerJoin(dim) || grouped;
                } else {
                    grouped = dim;
                }
            }
            return grouped;
        },
        initSlider: function ($select) {
            var self = this;
            var $div = $select.parents('.collectionItem:first').find('input[data-extent="group-dimension-extent"]').parent();
            var values = this.getInputValues($div);
            if ($select.val() && $select.val().length > 0) {
                var grouped = this.generateGrouped($select, values);
                var gopts = grouped.getOptions();
                $div.addClass('sliderDiv').addClass('mb-slider');
                var extentStepNum = grouped.getStepsNum();
                var sliderValues = [extentStepNum * grouped.partFromValue(gopts.extent[0]), extentStepNum * grouped.partFromValue(gopts.extent[1])];
                var sliderRange = [extentStepNum * grouped.partFromValue(gopts.origextent[0]), extentStepNum * grouped.partFromValue(gopts.origextent[1])];
                this.setVals($div, grouped);
                this.updateExtents($div, gopts.extent, grouped.getDefault());
                $div.slider({
                    range: true,
                    min: sliderRange[0],
                    max: sliderRange[1],
                    steps: 1,
                    values: sliderValues,
                    slide: function (event, ui) {
                        var extent = [grouped.valueFromPart(ui.values[0] / extentStepNum), grouped.valueFromPart(ui.values[1] / extentStepNum), gopts.origextent[2]];
                        grouped.setDefault(grouped.getInRange(extent[0], extent[1], grouped.getDefault()));
                        self.updateExtents($div, extent, grouped.getDefault());
                    }
                });
            } else {
                this.setVals($div, null);
                this.updateExtents($div);
                if ($div.hasClass('mb-slider')) {
                    $div.slider("destroy");
                }
                $div.removeClass('sliderDiv').removeClass('mb-slider');
            }
        }
    };
    var usedValues = [];
    var selectSelector = '#form_configuration_dimensionsets .collectionItem select[data-dimension-group]';
    var updateCollection = function updateCollection() {
        var $selects = $(selectSelector);

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

    $(document).on('change', 'select[data-dimension-group]', function (event) {
        var $opt = $('option:selected', event.target);
        var $sel = $opt.parent();
        updateCollection();
        dimHandler.initSlider($sel);
        return false;
    });
    $(document).on('click', '.collectionAdd', function (event) {
        var $collection = $(event.currentTarget).closest('[data-prototype]');
        var nOpts = $(selectSelector + ' > option').length;
        if (usedValues.length >= nOpts) {
            // return false;   // no worky, we can't prevent creation from here :\
        }
        var $new = $('.collectionItem', $collection).last();    // :)
        updateCollection();
        dimHandler.initSlider($new);
    });
    $('select[data-dimension-group]').trigger('change');
});
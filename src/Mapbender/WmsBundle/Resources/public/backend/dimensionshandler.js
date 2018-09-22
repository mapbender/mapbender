var dimHandler = null;
$(function () {
    dimHandler = {
        updateExtents: function ($div, extent, default_) {
            var text = extent ? extent[0] + '/' + extent[1] + '/' + extent[2] + ' - ' + default_ : '';
            $('input[data-name="display"]', $div.parents('.collectionItem:first')).val(text);
            $('input[data-name="extent"]', $div).val(extent ? JSON.stringify(extent) : '');
//            $('input[data-name="origextent"]', $div).val(!dimension ? "" : JSON.stringify(dimension.getOptions().origextent));
            $('input[data-name="default"]', $div).val(default_ ? default_ : '');
        },
        setVals: function ($div, dimension) {
            $('input[data-name="extent"]', $div).val(!dimension ? "" : JSON.stringify(dimension.getOptions().extent));
            $('input[data-name="origextent"]', $div).val(!dimension ? "" : JSON.stringify(dimension.getOptions().origextent));
            $('input[data-name="type"]', $div).val(!dimension ? "" : dimension.getOptions().type);
            $('input[data-name="current"]', $div).val(!dimension ? "" : dimension.getOptions().current);
            $('input[data-name="nearestValue"]', $div).val(!dimension ? "" : dimension.getOptions().nearestValue);
            $('input[data-name="multipleValues"]', $div).val(!dimension ? "" : dimension.getOptions().multipleValues);
            $('input[data-name="default"]', $div).val(!dimension ? "" : dimension.getOptions()['default']);
            $('input[data-name="unitSymbol"]', $div).val(!dimension ? "" : dimension.getOptions().unitSymbol);
            $('input[data-name="units"]', $div).val(!dimension ? "" : dimension.getOptions().units);
            $('input[data-name="name"]', $div).val(!dimension ? "" : dimension.getOptions().name);
        },
        generateGrouped: function ($select, $last) {
            var dims = JSON.parse($select.attr('data-dimension-group'));
            var last = null;
            var grouped = null;
            for (inst in dims) {
                $.each(dims[inst], function (idx, item) {
                    if ($last && $last.val() === inst + "-" + item.name + "-" + item.type) {
                        last = Mapbender.Dimension(item);
                    } else if ($.inArray(inst + "-" + item.name + "-" + item.type, $select.val()) > -1) {
                        if (!grouped) {
                            grouped = Mapbender.Dimension(item);
                        } else {
                            var help = grouped.innerJoin(Mapbender.Dimension(item));
                            if (help !== null) {
                                grouped = help;
                            }
                        }
                    }
                });
            }
            if (last) {
                if (!grouped) {
                    grouped = last;
                } else {
                    var help = grouped.innerJoin(last);
                    if (help !== null) {
                        grouped = help;
                    }
                }
            }
            return grouped;
        },
        initSlider: function ($select, $last) {
            var self = this;
            var $div = $select.parents('.collectionItem:first').find('input[data-extent="group-dimension-extent"]').parent();
            if ($select.val() && $select.val().length > 0) {
                var grouped = this.generateGrouped($select, $last);
                console.log("grouped is now", grouped);
                var gopts = grouped.getOptions();
                var options = $.extend(true, {}, gopts);
                gopts.extent = gopts.origextent;
                grouped.setOptions(gopts);
                grouped = Mapbender.Dimension(grouped.getOptions());
                $div.addClass('sliderDiv').addClass('mb-slider');
                var rangeMin = grouped.partFromValue(options.extent[0]);// * 100;
                var rangeMax = grouped.partFromValue(options.extent[1]);// * 10
                this.setVals($div, grouped);
                this.updateExtents($div, options.extent, grouped.getDefault());
                $div.slider({
                    range: true,
                    min: 0,
                    max: 100,
                    steps: grouped.getStepsNum(),
                    values: [rangeMin * 100, rangeMax * 100], // [0,100],
                    slide: function (event, ui) {
                        var extent = [grouped.valueFromPart(ui.values[0] / 100), grouped.valueFromPart(ui.values[1] / 100), grouped.getOptions().extent[2]];
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
        },
        otherOptions: function ($opt, disable) {
            $('select[data-dimension-group] option[value="' + $opt.val() + '"]', $opt.parents('.collectionItem:first').parent()).not($opt).prop('disabled', disable);
        }
    }

    $(document).on('change', 'select[data-dimension-group]', function (event) {
        var $opt = $('option:selected', event.target);
        var $sel = $opt.parent();
        var last = $sel.data("selected") ? $sel.data("selected") : [];
        if ($opt.prop("selected")) {
            dimHandler.otherOptions($opt, true);
            $.each(last, function (idx, item) {
                var $item = $('option[value="' + item + '"]', $opt.parent());
                if (!$item.prop("selected")) {
                    $item.prop('disabled', false);
                    dimHandler.otherOptions($item, false);
                }
            });
        } else {
            dimHandler.otherOptions($opt, false);
        }
        dimHandler.initSlider($opt.parent(), $opt);
        $sel.data("selected", $sel.val());
        return false;
    });
    $(document).on('click', '.collectionAdd', function (event) {
        var $plus = $(event.target);
        var $parent = $plus.parent();
        var $new = $('.collectionItem select[data-dimension-group]:last', $parent);
        if($('.collectionItem', $parent).length > 1){
            $('.collectionItem select[data-dimension-group]:first option', $parent).each(function(idx, item){
                var $opt = $(item);
                if($opt.prop('disabled') || $opt.prop('selected')){
                    $('option[value="' + $opt.val() + '"]', $new).prop('disabled', true);
                }
            });
        }
        dimHandler.initSlider($new, null);
    });
    $('.collectionItem select[data-dimension-group]', $('.collectionAdd').parent()).each(function (idx, item) {
        dimHandler.initSlider($(item), null);
    });
});
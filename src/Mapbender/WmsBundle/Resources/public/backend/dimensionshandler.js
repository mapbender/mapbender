$(function() {
    $(document).on('click', '.collectionAdd', function(event) {
        var extentInput = $(event.target).parents('.elementForm:first').find('input[data-extent="group-dimension-extent"]');
        extentInput.each(function(idx, item) {
//            initSlider($(item));
        });
    });

    function initSlider($select, $last) {
        var $div = $select.parents('.collectionItem:first').find('input[data-extent="group-dimension-extent"]').parent();
        if ($select.val() && $select.val().length > 0) {
            var dims = JSON.parse($select.attr('data-dimension-group'));
            var grouped = {};
            var last = {};
            var current = null;
            for (inst in dims) {
                $.each(dims[inst], function(idx, item) {
                    if ($last && $last.val() === inst + "-" + item.name + "-" + item.type) {
                        last = Mapbender.Dimension(item);
                    } else if ($.inArray(inst + "-" + item.name + "-" + item.type, $select.val()) > -1) {
                        var dim = Mapbender.Dimension(item);
                        if (!current) {
                            current = dim;
                        } else {
                            var help = current.join(dim);
                            if (help !== null) {
                                current = help;
                            }
                        }
                        grouped[inst + "-" + item.name + "-" + item.type] = dim;
                    }
                });
            }
            if (last) {
                if (!current) {
                    current = last;
                } else {
                    var help = current.join(last);
                    if (help !== null) {
                        current = help;
                    }
                }
            }
            $('input[data-name="extent"]', $div).val(JSON.stringify(current.options.extent));
            $('input[data-name="origextent"]', $div).val(JSON.stringify(current.options.origextent));
            $('input[data-name="type"]', $div).val(JSON.stringify(current.options.type));
            $('input[data-name="current"]', $div).val(JSON.stringify(current.options.current));
            $('input[data-name="nearestValue"]', $div).val(JSON.stringify(current.options.nearestValue));
            $('input[data-name="multipleValues"]', $div).val(JSON.stringify(current.options.multipleValues));
            $('input[data-name="default"]', $div).val(JSON.stringify(current.options.default));
            $('input[data-name="unitSymbol"]', $div).val(JSON.stringify(current.options.unitSymbol));
            $('input[data-name="units"]', $div).val(JSON.stringify(current.options.units));
            $('input[data-name="name"]', $div).val(JSON.stringify(current.options.name));
            $div.addClass('sliderDiv').addClass('mb-slider');
            var rangeMinVal = new TimeStr(current.valueFromStart()).toISOString();
            var rangeMaxVal = new TimeStr(current.valueFromEnd()).toISOString();
            var rangeMin = current.partFromValue(rangeMinVal);// * 100;
            var rangeMax = current.partFromValue(rangeMaxVal);// * 10
            
            $div.slider({
                range: true,
                min: 0,
                max: 100,
                steps: current.getStepsNum(),
                values: [rangeMin * 100, rangeMax * 100], // [0,100],
                slide: function(event, ui) {
                    console.log(current.valueFromPart(ui.values[0] / 100), current.valueFromPart(ui.values[1] / 100));
                }
            });
        } else {
            
            $('input[data-name="extent"]', $div).val("");
            $('input[data-name="origextent"]', $div).val("");
            $('input[data-name="type"]', $div).val("");
            $('input[data-name="current"]', $div).val("");
            $('input[data-name="nearestValue"]', $div).val("");
            $('input[data-name="multipleValues"]', $div).val("");
            $('input[data-name="default"]', $div).val("");
            $('input[data-name="unitSymbol"]', $div).val("");
            $('input[data-name="units"]', $div).val("");
            $('input[data-name="name"]', $div).val("");
            if ($div.hasClass('mb-slider')) {
                $div.slider("destroy");
            }
            $div.removeClass('sliderDiv').removeClass('mb-slider');
            //destroy
        }
    }

    function otherOptions($opt, disable) {
        $('select[data-dimension-group] option[value="' + $opt.val() + '"]', $opt.parents('.collectionItem:first').parent()).not($opt).prop('disabled', disable);
    }

    $(document).on('click', 'select[data-dimension-group] option', function(event) {
        var $opt = $(event.target);
        var $sel = $opt.parent();
        var last = $sel.data("selected") ? $sel.data("selected") : [];
        if ($opt.prop("selected")) {
            otherOptions($opt, true);
            $.each(last, function(idx, item) {
                var $item = $('option[value="' + item + '"]', $opt.parent());
                if (!$item.prop("selected")) {
                    $item.prop('disabled', false);
                    otherOptions($item, false);
                }
            });
        } else {
            otherOptions($opt, false);
        }
        initSlider($opt.parent(), $opt);
        $sel.data("selected", $sel.val());
        return false;
    });
});
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
            $div.addClass('sliderDiv').addClass('mb-slider');
            var dims = JSON.parse($select.attr('data-dimension-group'));
            var grouped = {};
//            var check = {asc: [], desc: []};
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
            console.log(grouped);
        } else {
            $div.removeClass('sliderDiv').removeClass('mb-slider');
            //destroy
        }
//        if (!$context.parent().hasClass('sliderDiv')) {
//            var sliderdiv = $context.parent();
//            sliderdiv.addClass('sliderDiv').addClass('mb-slider');
//            sliderdiv.slider({
//                range: true,
//                min: 0,
//                max: 100,
//                steps: 100,
//                values: [0, 1000],
//                slide: function(event, ui) {
////                    intoInput(dimHandlerOrig.valueFromPart(ui.values[0] / 100), dimHandlerOrig.valueFromPart(ui.values[1] / 100), dimension['extent'][2]);
//                }
//            });
//        }
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
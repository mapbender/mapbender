$(function() {
    $(document).on('click', '.collectionAdd', function(event) {
        var extentInput = $(event.target).parents('.elementForm:first').find('input[data-extent="group-dimension-extent"]');
        extentInput.each(function(idx, item) {
            initSlider($(item));
//            if (!$(item).parent().hasClass('sliderDiv')) {
//                var sliderdiv = $(item).parent();
//                sliderdiv.addClass('sliderDiv').addClass('mb-slider');
//                sliderdiv.slider({
//                    range: true,
//                    min: 0,
//                    max: 100,
//                    steps: 100,
//                    values: [0, 1000],
//                    slide: function(event, ui) {
////                    intoInput(dimHandlerOrig.valueFromPart(ui.values[0] / 100), dimHandlerOrig.valueFromPart(ui.values[1] / 100), dimension['extent'][2]);
//                    }
//                });
//            }
        });
//        console.log(extentInput);
    });

    function initSlider($context) {
        if (!$context.parent().hasClass('sliderDiv')) {
            var sliderdiv = $context.parent();
            sliderdiv.addClass('sliderDiv').addClass('mb-slider');
            sliderdiv.slider({
                range: true,
                min: 0,
                max: 100,
                steps: 100,
                values: [0, 1000],
                slide: function(event, ui) {
//                    intoInput(dimHandlerOrig.valueFromPart(ui.values[0] / 100), dimHandlerOrig.valueFromPart(ui.values[1] / 100), dimension['extent'][2]);
                }
            });
        }
    }
    function otherOptions($opt, disable){
        var $select = $opt.parent();
        $('select[data-dimension-group] option', $opt.parents('.collectionItem:first').parent()).each(function(idx, item){
            var $fndOpt = $(item);
            if($fndOpt.val() === $opt.val() && $opt.length === $fndOpt.length && $opt.length !== $fndOpt.filter($opt).length){
                $fndOpt.prop('disabled', disable);
//                console.log($fndOpt.val(), $opt.val());
            }
        });
    }
    $(document).on('change', 'select[data-dimension-group]', function(event) {
//        
//        var el = $(this);
//        var option = $('option[value="'+el.val()+'"',el);
//        console.log(option);
        console.log("CHANGE",$(event.target), $(event.currentTarget), $(event.target).val());
    });
    $(document).on('click', 'select[data-dimension-group] option', function(event) {
        var $opt = $(event.target);
        if($opt.prop("selected")){
            //disable other init sliders
            otherOptions($opt, true);
        } else {
            //enable other init sliders
            otherOptions($opt, false);
        }
//        $(event.target);
        console.log("CLICK", $(event.target).val(), $(event.target).prop("selected"));//, $(event.currentTarget));
    });
});
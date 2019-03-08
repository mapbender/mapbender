$(function() {
    $('.extendedGroup').on("click", '.on-off', function(e) {
        var $target = $(e.target);
        if ($target.hasClass('checkWrapper') || $target.is('input[type="checkbox"]')) {
            return;
        }
        var $this = $(this);
        if ($this.hasClass('active')) {
            $this.removeClass('active');
            $this.parent().find('#' + $this.attr('id') + '-content').addClass('hidden');
        } else {
            $this.addClass('active');
            $this.parent().find('#' + $this.attr('id') + '-content').removeClass('hidden');
        }
    });
    $('.extendedGroup select').each(function() {
        if ($(this).attr('name').indexOf('[extentEdit]') > 0) {
            $(this).on('change', function(e) {
                var item = $(e.target);
                var extentId = item.attr('id').substr(0, item.attr('id').indexOf('extentEdit')) + 'extent';
                $('.extendedGroup #' + extentId).val(item.val());
            });
        }
    });
    $('.on-off-content[data-json]').each(function(idx, item) {
        var $this = $(item);
        var dimension = $this.data('json');
        if (dimension['type'] === 'interval') {
            var dimensionOrig = jQuery.extend(true, {}, dimension);
            dimensionOrig['extent'] = dimensionOrig['origextent'];
            var dimHandler = Mapbender.Dimension(dimension);
            var dimHandlerOrig = Mapbender.Dimension(dimensionOrig);
            var rangeMinVal = new TimeStr(dimHandler.valueFromStart()).toISOString();
            var rangeMaxVal = new TimeStr(dimHandler.valueFromEnd()).toISOString();
            var rangeMin = dimHandlerOrig.partFromValue(rangeMinVal);// * 100;
            var rangeMax = dimHandlerOrig.partFromValue(rangeMaxVal);// * 100;
            var inputEdit = $('input[name*="\[extentEdit\]"]', $this);
            var inputExtent = $('input[name*="\[extent\]"]', $this);
            var inputDefault = $('input[name*="\[default\]"]', $this);
            function intoInput(first, second, third) {
                inputExtent.val(first + '/' + second + '/' + third);
                inputEdit.val(inputExtent.val());
                dimension['extent'] = [first, second, third];
                dimHandler = Mapbender.Dimension(dimension);
                var def = dimHandler.partFromValue(new TimeStr(inputDefault.val()).toISOString());// * 100;
                inputDefault.val(def >= 1 ? second : def <= 0 ? first : inputDefault.val());
            }
            intoInput(dimHandlerOrig.valueFromPart(rangeMin), dimHandlerOrig.valueFromPart(rangeMax), dimension['extent'][2]);
            $(".mb-slider", $this).slider({
                range: true,
                min: 0,
                max: 100,
                steps: dimHandlerOrig.getStepsNum(),
                values: [rangeMin * 100, rangeMax * 100],
                slide: function(event, ui) {
                    intoInput(dimHandlerOrig.valueFromPart(ui.values[0] / 100), dimHandlerOrig.valueFromPart(ui.values[1] / 100), dimension['extent'][2]);
                }
            });
        }
    });
});
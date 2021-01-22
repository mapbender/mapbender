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
            var resolution = dimension.extent[2];
            var dimensionOrig = jQuery.extend(true, {}, dimension);
            dimensionOrig.extent = dimensionOrig.origextent;
            var dimHandlerOrig = Mapbender.Dimension(dimensionOrig);
            var inputEdit = $('input[name*="\[extentEdit\]"]', $this);
            var inputExtent = $('input[name*="\[extent\]"]', $this);
            var inputDefault = $('input[name*="\[default\]"]', $this);
            function intoInput(first, second) {
                inputExtent.val(first + '/' + second + '/' + resolution);
                inputEdit.val(inputExtent.val());
                inputDefault.val(dimHandlerOrig.getInRange(first, second, dimHandlerOrig.getMax()));
            }
            intoInput(dimension.extent[0], dimension.extent[1]);
            $(".mb-slider", $this).slider({
                range: true,
                min: 0,
                max: dimHandlerOrig.getStepsNum(),
                values: [dimHandlerOrig.getStep(dimension.extent[0]), dimHandlerOrig.getStep(dimension.extent[1])],
                slide: function(event, ui) {
                    intoInput(dimHandlerOrig.valueFromStep(ui.values[0]), dimHandlerOrig.valueFromStep(ui.values[1]));
                }
            });
        }
    });
});

$(function() {
    $('[data-diminstconfig]').each(function(idx, item) {
        var $this = $(item);
        var originalExtent = $this.attr('data-origextent') || '';
        var $rangesSelector = $('select[name*="[extentRanges]"]', item);
        var inputExtent = $('input[name*="[extent]"]', $this);
        $rangesSelector.on('change', function() {
            inputExtent.val(($(this).val() || []).join(','));
        });
        var dimension = JSON.parse($this.attr('data-diminstconfig'));
        if (originalExtent.indexOf('/') !== -1) {
            var resolution = dimension.extent[2];
            var dimensionOrig = jQuery.extend(true, {}, dimension);
            // @todo: Mapbender.Dimension should support unmangled extent strings directly
            dimensionOrig.extent = originalExtent.split(',')[0].split('/');
            var dimHandlerOrig = Mapbender.Dimension(dimensionOrig);
            var inputDefault = $('input[name*="\[default\]"]', $this);
            function intoInput(first, second) {
                inputExtent.val(first + '/' + second + '/' + resolution);
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

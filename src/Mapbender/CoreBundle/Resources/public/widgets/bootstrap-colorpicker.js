const mbInitColorPickers = function($pickers) {
    $pickers.each((index, picker) => {
        const $picker = $(picker);
        if ($picker.attr('data-colorpicker-initialized')) {
            return;
        }

        const container = document.createElement('div');
        container.className = 'color-picker-container';
        container.style.setProperty('--chosen-color', $picker.val() || $picker.attr('placeholder') || 'transparent');
        $(container).insertBefore($picker).append($picker);

        $picker.on('change keydown', (evt) => {
            container.style.setProperty('--chosen-color', $picker.val() || $picker.attr('placeholder') || 'transparent');
        });

        $picker.on('showPicker', (evt) => {
            if (!$picker.val() && $picker.attr('placeholder')) {
                $picker.colorpicker('setValue', $picker.attr('placeholder'));
            }
        });

        $picker.colorpicker({format: $picker.attr('data-colorpicker-style') || 'rgba'});
        $picker.attr('data-colorpicker-initialized', true);
    });
}

$(function() {
    mbInitColorPickers($('.-js-init-colorpicker'));
    $(document).on('shown.bs.modal', (evt) => {
        mbInitColorPickers($('.-js-init-colorpicker', evt.target));
    });
});


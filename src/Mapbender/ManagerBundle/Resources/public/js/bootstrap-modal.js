window.Mapbender = window.Mapbender || {};
window.Mapbender.bootstrapModal = (function ($) {
    var wrapperTemplate = [
        '<div class="modal" tabindex="-1" role="dialog">',
        '<div class="modal-dialog modal-dialog-scrollable" role="document"></div>',
        '</div>'
    ].join('');
    var contentTemplate = [
        '<div class="modal-header">',
        '<h2 class="modal-title"></h2>',
        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
        '</div>',
        '<div class="modal-body"></div>',
        '<div class="modal-footer"></div>'
    ].join('');

    var $wrapper, $mContent;

    function bootstrapModal(content, options) {
        $wrapper = $wrapper || $($.parseHTML(wrapperTemplate));
        $mContent = $mContent || $($.parseHTML(contentTemplate));
        var $content;
        try {
            $content = $(content);
        } catch (Error) {
            $content = $(document.createElement('p')).append(content);
        }
        var $element = $wrapper.clone();
        $('.modal-dialog', $element).addClass(options.cssClass || '');
        var $contentStructure = $mContent.clone();
        if (!options.closeButton) {
            $('.btn-close', $contentStructure).remove();
        }
        var $modalContent = $(document.createElement('div'))
            .addClass('modal-content')
            .append($contentStructure)
        ;
        $('.modal-body', $modalContent).append($content);
        $('.modal-dialog', $element).append($modalContent);
        var modalSubTitle = (options.subTitle) ? '<br><small class="fs-6">' + options.subTitle + '</small>' : '';
        var modalTitle = options.title + modalSubTitle;
        $('.modal-title', $element).html(modalTitle);

        var buttons_ = options.buttons || [];
        for (var b = 0; b < buttons_.length; ++b) {
            var buttonOptions = buttons_[b];
            var $b = $(document.createElement('button'))
                .attr({type: buttonOptions.type || 'button', 'class': buttonOptions.cssClass})
                .text(buttonOptions.label)
            ;
            if (/popupClose/.test(buttonOptions.cssClass)) {
                $b.attr('data-bs-dismiss', 'modal');
            } else if (buttonOptions.callback) {
                $b.on('click', buttonOptions.callback.bind($b.get(0)));
            }
            $('.modal-footer', $element).append($b);
        }
        $element.modal({backdrop: 'static'});
        $element.one('hidden.bs.modal', function () {
            $element.remove();
        });
        return $element;
    }

    return bootstrapModal;
})(jQuery);


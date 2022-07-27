$(function(){
    var $mobilePane = $('#mobilePane');
    $('.mb-element', $mobilePane).addClass('hidden');
    $(document).on('mobilepane.switch-to-element', function(e, data) {
        switchToElement_(data.element);
        toggle_(true);
    });

    $('.toolBar').on('click', '.mb-button', function(e) {
        var $button = $(this);
        // This element may actually not be a control button, but a Gps Button or anything else
        var button = $button.data('mapbenderMbButton');
        var targetId = ((button || {}).options || {}).target;
        var target = targetId && document.getElementById(targetId);
        if (!target || !$(target).closest('.mobilePane').length) {
            return;
        }
        // HACK: prevent button from ever gaining a visual highlight
        $button.removeClass('toolBarItemActive');

        e.stopImmediatePropagation();

        // supply button tooltip as emergency fallback if target element has no title
        switchToElement_($(target), $button.attr('title'));
        toggle_(true);

        return false;
    });
    function switchToElement_(element, titleFallback) {
        var $siblings = $('.mobileContent', $mobilePane).children();
        $siblings.not(element).addClass('hidden');
        $siblings.filter(element).removeClass('hidden');
        var headerText = element.attr('title') || element.data('title');
        if (!headerText || /^\w+(\.\w+)+$/.test(headerText)) {
            headerText = titleFallback || headerText || 'undefined';
        }
        $('.contentTitle', $mobilePane).text(headerText);
    }
    function toggle_(state) {
        if (state) {
            $mobilePane.attr('data-state', 'opened')
        } else {
            $mobilePane.removeAttr('data-state');
        }
    }
    $('#mobilePaneClose').on('click', function() {
        toggle_(false);
    });
    $.notify.defaults({position: "top left"});
});

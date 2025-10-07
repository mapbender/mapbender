;!(function($) {
    $(document).on('click', '.toolBar .menu-wrapper > button', function() {
        var $btn = $(this);
        // Leave Bootstrap script integration alone
        if (!$btn.attr('data-toggle')) {
            var $wrapper = $btn.closest('.menu-wrapper');
            var $toolbar = $wrapper.closest('.toolBar');

            // Calculate the dropdown-menu position
            if ($wrapper.hasClass('open')) {
                $wrapper.removeClass('open');
            } else {
                var toolbarRect = $toolbar[0].getBoundingClientRect();
                var dropdownTop = toolbarRect.bottom -3;

                document.documentElement.style.setProperty('--dropdown-top', dropdownTop + 'px');

                $wrapper.addClass('open');
            }

            // Wrapper is .dropdown OR .dropup
            // @see https://getbootstrap.com/docs/3.4/components/#dropdowns
            $btn.toggleClass('active', $wrapper.is('.open'));
            $('i', $btn)
                .toggleClass('fa-bars', !$wrapper.is('.open'))
                .toggleClass('fa-xmark', $wrapper.is('.open'));
        }
    });
}(jQuery));

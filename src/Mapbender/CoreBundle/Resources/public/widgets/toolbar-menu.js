;!(function($) {
    $(document).on('click', '.toolBar .menu-wrapper > button', function() {
        var $btn = $(this);
        // Leave Bootstrap script integration alone
        if (!$btn.attr('data-toggle')) {
            var $wrapper = $btn.closest('.menu-wrapper');
            var $toolbar = $wrapper.closest('.toolBar');

            if ($wrapper.hasClass('open')) {
                $wrapper.removeClass('open');
            } else {
                // Calculate the dropdown-menu position
                var toolbarHeight = $toolbar[0].getBoundingClientRect().bottom;
                var dropdownTop = toolbarHeight - 3;

                document.documentElement.style.setProperty('--dropdown-top', dropdownTop + 'px');

                // Calculate dropdown-menu height
                var toolbarBottomHeight = $('.toolBar.bottom')[0].getBoundingClientRect().height;
                var toolbarHeightSum = toolbarHeight + toolbarBottomHeight;
                var dropdownMenuMaxHeight = window.innerHeight - toolbarHeightSum - 3;

                document.documentElement.style.setProperty('--dropdown-menu-max-height', dropdownMenuMaxHeight + 'px');

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

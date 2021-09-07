;!(function($) {
    $(document).on('click', '.toolBar .menu-wrapper > button', function() {
        var $btn = $(this);
        // Leave Bootstrap script integration alone
        if (!$btn.attr('data-toggle')) {
            var $wrapper = $btn.closest('.menu-wrapper');
            // Wrapper is .dropdown OR .dropup
            // @see https://getbootstrap.com/docs/3.4/components/#dropdowns
            $wrapper.toggleClass('open');
            $btn.toggleClass('active', $wrapper.is('.open'));
        }
    });
}(jQuery));

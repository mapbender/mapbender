;!(function($) {
    // Simple toggle panel standin that can be used safely both in
    // frontend (no Bootstrap script) and backend (Bootstrap script present)
    $(document).on('click', '.content-toggle-container > .content-toggle', function() {
        var $cnt = $(this).parent();
        $cnt.toggleClass('closed');
        var state = !$cnt.hasClass('closed');
        $('>.content-toggle i', $cnt)
            .toggleClass('fa-plus', !state)
            .toggleClass('fa-minus', state)
        ;
    });
}(jQuery));

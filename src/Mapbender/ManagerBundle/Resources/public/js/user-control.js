$(function(){
    $('.selectedUsersGroups').each(function() {
        const $displayEl = $(this);
        const $body = $('>tbody', $displayEl.closest('table'));
        $body.on('change', 'input[type="checkbox"]', function() {
            const countSelected = $('input[type="checkbox"]:checked', $body).length;
            $displayEl.text(countSelected);
        });
    });
});

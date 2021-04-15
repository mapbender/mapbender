$(function(){
    $('#selectedUsersGroups').each(function() {
        var $displayEl = $(this);
        var $body = $('>tbody', $displayEl.closest('table'));
        $body.on('change', 'input[type="checkbox"]', function() {
            var countSelected = $('input[type="checkbox"]:checked', $body).length;
            $displayEl.text(countSelected);
        });
    });
});

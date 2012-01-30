$(function() {
    $('#apps-overview li').bind('click', function() {
        var target = $(this).find('h2 a').attr('href');
        window.location = target;
    });
});


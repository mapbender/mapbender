$(function(){
    $(document).on('mbfeatureinfofeatureinfo', function(e, options){
        if(options.action === 'haveresult') {
            $.each($('#mobilePane .mobileContent').children(), function(idx, item){
                $(item).addClass('hidden');
            });
            $('#' + options.id).removeClass('hidden');
            $('#mobilePane .contentTitle').text($('#' + options.id).attr('data-title'));
            $('#mobilePane').attr('data-state', 'opened');
        }
    });

    $('#footer').on('click', '.mb-button', function(e) {
        var $button = $(this);
        var button = $button.data('mapbenderMbButton');
        var buttonOptions = button.options;
        var target = $('#' + buttonOptions.target);
        var pane = target.closest('.mobilePane');
        if (!(target && target.length && pane && pane.length)) {
            return;
        }
        var paneContent = $('.mobileContent', pane);
        var paneTitle = $('.contentTitle', pane);
        e.stopImmediatePropagation();

        // hide frames
        $.each(paneContent.children().not(target), function(idx, item) {
            $(item).addClass('hidden');
        });
        target.removeClass('hidden');
        var headerText = target.attr('title') || target.data('title');
        if (!headerText || /^\w+(\.\w+)+$/.test(headerText)) {
            headerText = $button.attr('title') || headerText || 'undefined';
        }
        paneTitle.text(headerText);
        pane.attr('data-state', 'opened');

        return false;
    });
    $('#mobilePaneClose').on('click', function(){
        $('#mobilePane').removeAttr('data-state');
    });
    /* START center notifyjs dialog */
    $.notify.defaults({position: "top left"});
    /* END center notifyjs dialog */
});

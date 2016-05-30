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
    $('#footer').on('click', '.mb-element,.mb-button', function(e){
        var button = $('#' + $(e.target).attr('for')).data('mapbenderMbButton');
        if($('#' + button.options.target).parents('.mobilePane').length > 0){ // only for elements at '.mobilePane'
            $.each($('#mobilePane .mobileContent').children(), function(idx, item){
                $(item).addClass('hidden');
            });
            $('#' + button.options.target).removeClass('hidden');
            var targets = $('#' + button.options.target).data();
            $('#mobilePane .contentTitle').text('undefined');
            for(widgetName in targets){
                if(typeof targets[widgetName] === 'object' && targets[widgetName].options){
                    var title = null;
                    if(title = targets[widgetName].element.attr('title'));
                    else if(title = targets[widgetName].element.attr('data-title'));
                    $('#mobilePane .contentTitle').text(title);
                    break;
                }
            }
            $('#mobilePane').attr('data-state', 'opened');
            e.stopPropagation();
        }
    });
    $('#mobilePaneClose').on('click', function(){
        $('#mobilePane').removeAttr('data-state');
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-simplesearch input[type="text"]').on('mbautocomplete.selected', function(e){
        $('#mobilePaneClose').click();
    });
    /* START close mobilePane if a map is centred after search */
    var moved = false;
    Mapbender.elementRegistry.onElementReady("map", function() {
        Mapbender.Model.mbMap.map.olMap.events.register('moveend', null, function() {
            if($('#mobilePane').attr('data-state')) {
                moved = true;
            } else {
                moved = false;
            }
        });
    });

    $('.search-results').on('click', function(){
        if(moved){
            moved = false;
            $('#mobilePaneClose').click();
        }
    });
    /* END close mobilePane if a map is centred after search */
    /* START center notifyjs dialog */
    $.notify.defaults({position: "top left"});
    /* END center notifyjs dialog */
});
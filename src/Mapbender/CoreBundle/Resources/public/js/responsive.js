$(function(){
    'use strict';

    var toolbar       = $('#toolbar');
    var toolbarHeight = Math.round(toolbar.height());
    var last, sub, lastSub, item;

    window.onresize = function(event) {
        if(toolbarHeight < Math.round(toolbar.height())){

            last = toolbar.find('> .toolBarItem:last');
            sub  = last.clone(true);
            last.remove();
            $('#toolbarMoreMenu').prepend(sub);
            $('#toolbarMoreButton').attr('data-state', 'show');
        }
        else if(this.innerWidth >= Math.round(toolbar.width() + 150)){
            lastSub = $('#toolbarMoreMenu').find('> .toolBarItem:first');

            if(lastSub.length > 0){
                item = lastSub.clone(true);
                lastSub.remove();
                item.insertAfter(toolbar.find('> .toolBarItem:last'));
            }else{
                $('#toolbarMoreButton').removeAttr('data-state');
            }
        }else if($('#toolbarMoreMenu').find('.toolBarItem').length == 0){
            $('#toolbarMoreButton').removeAttr('data-state');
        }
    };

    $('#toolbarMoreButton').on('click', function(){
        $('#toolbarMoreMenu').attr('data-state', 'show');
        $('#toolbarMoreMenu').one('mouseover', function(){
            $('#content').one('mouseover', function(){
                $('#toolbarMoreMenu').removeAttr('data-state');
            });
        });
    });

    $('#infocontainerTrigger').on('click', function(){
        var wrapper = $('#templateWrapper');

        if(wrapper.attr('data-state')){
            wrapper.removeAttr('data-state');
        }else{
            wrapper.attr('data-state', 'open');
        }
    });
});

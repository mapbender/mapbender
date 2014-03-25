$(function(){
    'use strict';

    var toolbar       = $('#toolbar');
    var toolbarHeight = toolbar.attr('data-height');
    var last, sub, lastSub, item;

    function handlePanPos(){
        if(window.innerWidth > 600){
            var menu = $('#toolbarMoreMenu');
            var pan = $('.mb-element-zoombar');

            // Dynamic pan positioning
            if(pan.length > 0){
                var pos = 0;

                // Save position
                if((pan.length > 0) && !(pan.attr('data-pos'))){
                    pan.attr('data-pos', pan.css('top').replace('px', ''));
                }
                pos = parseInt(pan.attr('data-pos'));

                if(menu.attr('data-state') == 'show'){
                    pan.css('top', menu.height() + pos + 40);
                }else{
                    pan.css('top', pos);
                }
            }
        }else{
            pan.css('top', 'auto');
        }
    }

    window.onresize = function(event) {
        if(toolbarHeight < Math.round(toolbar.height())){
            last = toolbar.find('> .toolBarItem:last');
            sub  = last.clone(true);
            last.remove();
            $('#toolbarMoreMenu').prepend(sub);
            $('#toolbarMoreButton').attr('data-state', 'show');
            handlePanPos();
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

            handlePanPos();
        }else if($('#toolbarMoreMenu').find('.toolBarItem').length == 0){
            $('#toolbarMoreButton').removeAttr('data-state');
        }
    };

    $('#toolbarMoreButton').on('click', function(){
        var menu = $('#toolbarMoreMenu');

        if(menu.attr('data-state') == 'show'){
            menu.removeAttr('data-state');
        }else{
            menu.attr('data-state', 'show');
            menu.one('mouseover', function(){
                $('#content').one('mouseover', function(){
                    menu.removeAttr('data-state');
                    handlePanPos();
                });
            });
        }

        handlePanPos();
    });

    $('#infocontainerTrigger').on('click', function(){
        var wrapper = $('#templateWrapper');

        if(wrapper.attr('data-state')){
            wrapper.removeAttr('data-state');
        }else{
            wrapper.attr('data-state', 'open');
        }
    });

    while(toolbarHeight < toolbar.height()){
        $(window).trigger('resize');
    }
});

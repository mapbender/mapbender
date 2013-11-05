$(function(){
    var showInfoBox = function(){
        var me = $(this);
        var infoBox = me.find(".infoMsgBox");

        if(infoBox.hasClass("hide")){
            $(".infoMsgBox").addClass("hide");
            infoBox.removeClass("hide");
        }else{
            infoBox.addClass("hide");
        }
        return false;
    }

    function setRootState(className){
        var root = $("#" + className);
        var column = $("#instanceTableCheckBody").find("[data-check-identifier=" + className + "]")
        var rowCount = column.find(".checkWrapper:not(.checkboxDisabled)").length;
        var checkedCount = column.find(".iconCheckboxActive").length;

        root.removeClass("iconCheckboxActive").removeClass("iconCheckboxHalf");

        if(rowCount == checkedCount){
            root.addClass("iconCheckboxActive");
        }else if(checkedCount == 0){
            // do nothing!
        }else{
            root.addClass("iconCheckboxHalf");
        }
    }
    // toggle all permissions
    var toggleAllStates = function(){
        var self = $(this);
        var className = self.attr("id");
        var checkElements = $(".checkboxColumn[data-check-identifier=" + className + "]");
        var state = !self.hasClass("iconCheckboxActive");
        var me;

        // change all tagboxes with the same permission type
        checkElements.find(".checkbox:not(:disabled)").each(function(i, e){
            me = $(e);
            me.get(0).checked = state;

            if(state){
                me.parent().addClass("iconCheckboxActive");
            }else{
                me.parent().removeClass("iconCheckboxActive");
            }
        });

        // change root permission state
        setRootState(className);
    }
    // init permission root state
    var initRootState = function(){
        $(this).find(".iconCheckbox").each(function(){
            setRootState($(this).attr("id"));
            $(this).bind("click", toggleAllStates);
        });
    }
    $("#instanceTableCheckHead").one("load", initRootState).load();

    // toggle permission Event
    var toggleState = function(){
        setRootState($(this).parent().attr("data-check-identifier"));
    };
    $('tr[data-type="root"], tr[data-type="node"]', $('.instanceTable tbody')).each(function(){
        var that = $(this),
                id = $(this).attr("data-id"),
                children = [],
                start_pos = null,
                stop_pos = null;
        var changePosition = function(options, idx, reverse){
            $.ajax({
                'url': options[idx]['url'],
                'type': "POST",
                'data': {
                    'number': options[idx]['idx'],
                    'id': options[idx]['id']
                },
                success: function(data, textStatus, jqXHR){
                    if(data.error && data.error !== ''){
                        document.location.href = document.location.href;
                    }else{
                        var elm = $('.instanceTable tbody tr[data-id="' + options[idx]['id'] + '"]');
                        if(reverse){
                            if(options.length - 1 !== idx){
                                elm.insertAfter($('.instanceTable tbody tr[data-id="' + options[options.length - 1]['id'] + '"]'));
                            }
                        }else{
                            if(0 !== idx){
                                elm.insertAfter($('.instanceTable tbody tr[data-id="' + options[idx - 1]['id'] + '"]'));
                            }
                        }
                        if(idx < options.length - 1)
                            changePosition(options, idx + 1, reverse);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    document.location.href = document.location.href;
                }
            });
        };

        $('.instanceTable tbody').sortable({
            cursor: 'move',
            axis: 'y',
            items: 'tr:not(.root,[data-parent="' + id + '"])',
            distance: 6,
            containment: 'parent',
            start: function(event, ui){
                if($(ui.item).hasClass('root') || $(ui.item).hasClass('dummy'))
                    return false;
                var subs = $('.instanceTable tbody tr[data-parent="' + $(ui.item).attr('data-id') + '"]');
                children = [];
                start_pos = $(ui.item).index() - $(ui.item).prevAll('.header').length;
                stop_pos = null;
                if(subs.length > 0){
                    var nextAll = $(ui.item).nextAll('[data-id]');
                    for(var i = 0; i < nextAll.length; i++){
                        var tmp = $(nextAll.get(i));
                        if(tmp.attr('data-parent') === $(ui.item).attr('data-parent')){
                            break;
                        }
                        children.push({'id': tmp.attr('data-id'), 'url': tmp.attr('data-href')});
                    }
                }
            },
            stop: function(event, ui){
                var item = $(ui.item),
                        stop_pos = item.index() - item.prevAll('.header').length,
                        reverse = stop_pos > start_pos;
                var main_pos = reverse ? stop_pos - children.length : stop_pos;
                var toChange = [{'url': item.attr("data-href"), 'idx': main_pos, 'id': item.attr("data-id")}];//, 'children': children};
                $.each(children, function(i, item){
                    toChange.push({'url': item.url, 'idx': (main_pos + 1 + i), 'id': item.id});
                });
                if(reverse)
                    toChange.reverse();
                if(item.prev().length > 0 && $(item.prev().get(0)).attr("data-parent") === item.attr("data-parent")){
                    if(item.next().length > 0 && $(item.next().get(0)).attr("data-parent") === $(item.prev().get(0)).attr("data-id")){
                        return false;
                    }
                    changePosition(toChange, 0, reverse);
                    return true;
                }else if(item.next().length > 0 && $(item.next().get(0)).attr("data-parent") === item.attr("data-parent")){
                    changePosition(toChange, 0, reverse);
                    return true;
                }else{
                    return false;
                }
            }
        });
    });
    $("#instanceTable").on("click", ".iconMore", showInfoBox);
    $(document).on("click", "#instanceTable .checkWrapper", toggleState);
});
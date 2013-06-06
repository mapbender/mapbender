$(function() {
  var showInfoBox = function(){
    var me      = $(this);
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
        var root         = $("#" + className);
        var column       = $("#instanceTableCheckBody").find("[data-check-identifier=" + className + "]")
        var rowCount     = column.find(".checkWrapper:not(.checkboxDisabled)").length;
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
        var self          = $(this);
        var className     = self.attr("id");
        var checkElements = $(".checkboxColumn[data-check-identifier=" + className + "]");
        var state         = !self.hasClass("iconCheckboxActive");
        var me;

        // change all tagboxes with the same permission type
        checkElements.find(".checkbox:not(:disabled)").each(function(i,e){
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
    }

    $('.instanceTable tbody').each(function() {
        $(this).sortable({
            cursor: 'move',
            items: 'tr:not(.root,.dummy,.header)',
            distance: 20,
            containment: 'parent',
            stop: function(event, ui) {
                var item = $(ui.item),
                    index = item.index() - item.prevAll('.header').length;

                $.ajax({
                    url: $(ui.item).attr("data-href"),
                    type: "POST",
                    data: {
                        number: index,
                        id: $(ui.item).attr("data-id")
                    },
                    success: function(data, textStatus, jqXHR){
                        if(data.error && data.error !== ''){
                            document.location.href = document.location.href;
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown ){
                        document.location.href = document.location.href;
                    }
                });
            }
        });
    });

    $("#instanceTable").on("click", ".iconMore", showInfoBox);
    $(document).on("click", "#instanceTable .checkWrapper", toggleState);
});
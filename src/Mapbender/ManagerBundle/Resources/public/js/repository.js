$(function() {
  var showInfoBox = function(){
    $(".infoMsgBox").addClass("hide");
    $(this).find(".infoMsgBox").removeClass("hide");

    $(document).one("click", function(){
      $(".infoMsgBox").addClass("hide");
    });
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

  $("#instanceTable").on("click", ".iconMore", showInfoBox); 
  $(document).on("click", "#instanceTable .checkWrapper", toggleState);
});
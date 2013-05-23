$(function() {
  var showInfoBox = function(){
    $(".infoMsgBox").addClass("hide");
    $(this).find(".infoMsgBox").removeClass("hide");

    $(document).one("click", function(){
      $(".infoMsgBox").addClass("hide");
    });
    return false;
  }
  $("#instanceTable").on("click", ".iconInfo", showInfoBox);

  
});
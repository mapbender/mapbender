$(function() {
    // init sidebar toggle -------------------------------------------------------------------
    var sideBarToggle = function(){
        $('#templateWrapper').toggleClass("sidePaneOpened");
    };
    $(".toggleSideBar").bind("click", sideBarToggle);
});

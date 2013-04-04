$(function(){
    // Switch application state via Ajax when the current state icon is clicked
    $('table#application-list').on('click', 'i.application-state', function() {
        var icon = $(this),
            states = {
                'enabled': 'icon-eye-open',
                'disabled': 'icon-eye-close'
            },
            currentState = icon.hasClass('icon-eye-open') ? 'enabled' : 'disabled',
            requestedState = currentState === 'enabled' ? 'disabled' : 'enabled',
            slug = icon.closest('tr').attr('data-application-slug'),
            id = icon.closest('tr').attr('data-application-id');

        if(id === '') {
            alert('YAML-defined applications can not be edited.');
            return;
        }

        icon.removeClass(states[currentState]);
        icon.addClass(states[requestedState]);

        var errorHandler = function() {
            icon.removeClass(states[requestedState]);
            icon.addClass(states[currentState]);
            alert('Unfortunately, there was an error switching states.');
        }

        $.ajax({
            url: Routing.generate('mapbender_manager_application_togglestate', {
                slug: slug}),
            type: 'POST',
            data: {
                'state': requestedState
            },
            success: function(data) {
                if(data.newState !== requestedState) {
                    errorHandler();
                }
            },
            error: errorHandler
        });
    });

    $("#addPermission").bind("click", function(){
        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal', {content:"some content here!"});
        }
        return false;
    });

    function loadElementFormular(){
        var url = $(this).attr("href");

        if(url){
            $.ajax({
                url: url,
                type: "POST",
                success: function(data){
                   $("#popupContent").wrap('<div id="contentWrapper"></div>').hide();
                   $("#contentWrapper").append('<div id="popupSubContent"></div>');
                   $("#popupSubContent").append(data);
                   var subTitle = $("#popupSubContent").find("#form_title").val();
                   $("#popupSubTitle").text(" - New " + subTitle);
                   $("#popup").find(".buttonOk, .buttonBack").show();
                }
            });
        }

        return false;
    }

    $(".addElement").bind("click", function(){
        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('addButton', "Back", "button buttonBack left", function(){

                $("#popupSubContent").remove();
                $("#popupSubTitle").text("");
                $("#popup").find(".buttonOk, .buttonBack").hide();
                $("#popupContent").show();

            }).mbPopup('showAjaxModal', 
                              {title:"Select Element"},
                              $(this).attr("href"), 
                              function(){ //ok click
                                $("#elementForm").submit();

                                return false;
                              },
                              null,
                              function(){  //afterLoad
                                var popup = $("#popup");

                                popup.find(".buttonOk, .buttonBack").hide();
                                popup.find(".linkButton").on("click", loadElementFormular)
                              });
        }
        return false;
    });
})
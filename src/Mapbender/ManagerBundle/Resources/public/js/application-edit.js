$(function() {
    $("table.elementsTable tbody").sortable({
        connectWith: "table.elementsTable tbody",
        items: "tr:not(.dummy)",
        distance: 20,
        stop: function( event, ui ) {
            $(ui.item).parent().find("tr.element").each(function(idx, elm){
                if($(elm).attr("data-href")===$(ui.item).attr("data-href")){
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx,
                            region: $(ui.item).closest('table').attr("data-region")
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
        }
    });

    $('tr.element, tr.sourceinst').find('.iconCheckboxActive input[type="checkbox"]').click(function() {
        $.ajax({
            url: $(this).attr("data-href"),
            type: "POST",
            data: {
                enabled: $(this).is(":checked")
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
    });

    $("table.layersetTable tbody" ).sortable({
        connectWith: "table.layersetTable tbody",
        items: "tr:not(.header)",
        distance: 20,
        stop: function( event, ui ) {
            $(ui.item).parent().find("tr").each(function(idx, elm){
                if($(elm).attr("data-id")===$(ui.item).attr("data-id")){
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx,
                            new_layersetId: $(elm).closest('table').attr("data-id")
                        }
                    });
                }
            });
        }
    });

    $("ul.layercollection ul").each(function(){
        $(this).sortable({
            cursor: "move",
            connectWith: "ul.layercollection",
            items: "li:not(.header,.root,.dummy)",
            distance: 20,
            stop: function( event, ui ) {
                $(ui.item).parent().find("li").each(function(idx, elm){
                    if($(elm).attr("data-id")===$(ui.item).attr("data-id")){

                        $.ajax({
                            url: $(ui.item).attr("data-href"),
                            type: "POST",
                            data: {
                                number: idx - $("ul.layercollection li.header").length, // idx - header
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
            }
        })
    });

    $('ul.layercollection li.node span.colactive:first input[type="checkbox"]').bind("change", function(e){
        if($(this).attr("checked") === "checked"){
            $(this).parent().parent().parent().parent().find('span.colactive input[type="checkbox"]').attr("checked", true).attr("disabled", false);
        }else{
            $(this).parent().parent().parent().parent().find('span.colactive input[type="checkbox"]').attr("checked", false).attr("disabled", true);
            $(this).attr("disabled", false);
        }
    })
    $('ul.layercollection div.group button.groupon').bind("click", function(e){
        var className = $(this).parent().attr('class');
        $('ul.layercollection li span.'+className+' input[type="checkbox"]').each(function(index) {
            if($(this).attr("disabled") !== "disabled"){
                $(this).attr("checked", true);
            }
        });
        return false;
    });
    $('ul.layercollection div.group button.groupoff').bind("click", function(e){
        var className = $(this).parent().attr('class');
        $('ul.layercollection li span.'+className+' input[type="checkbox"]').each(function(index) {
            if($(this).attr("disabled") !== "disabled"){
                $(this).attr("checked", false);
            }
        });
        return false;
    });



    // Layout - Elements ---------------------------------------------------------------------------


    function loadElementFormular(){
        var url = $(this).attr("href");

        if(url){
            $.ajax({
                url: url,
                type: "GET",
                success: function(data){
                    $("#popupContent").wrap('<div id="contentWrapper"></div>').hide();
                    $("#contentWrapper").append('<div id="popupSubContent" class="popupSubContent"></div>');
                    $("#popupSubContent").append(data);
                    var subTitle = $("#popupSubContent").find("#form_title").val();
                    $("#popupSubTitle").text(" - " + subTitle);
                    $("#popup").find(".buttonYes, .buttonBack").show();

                    var submitHandler = function(e){
                        $.ajax({
                            url: $(this).attr('action'),
                            data: $(this).serialize(),
                            type: 'POST',
                            statusCode: {
                                200: function(response) {
                                    $("#popupSubContent").html(response);
                                    var subTitle = $("#popupSubContent").find("#form_title").val();
                                    $("#popupSubTitle").text(" - " + subTitle);
                                    $("#popup").find(".buttonYes, .buttonBack").show();
                                },
                                201: function() {
                                    $("body").mbPopup('close');
                                    window.location.reload();
                                },
                                205: function() {
                                    $("body").mbPopup('close');
                                    window.location.reload();
                                }
                            }

                        });
                        e.preventDefault();
                        return false;
                    };
                    $("#popupSubContent").on('submit', 'form', submitHandler);
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
                $("#popup").find(".buttonYes, .buttonBack").hide();
                $("#popupContent").show();

            })
            .mbPopup('showAjaxModal',
                     {title:"Add element"},
                     $(this).attr("href"),
                     function(){ //ok click
                       $("#elementForm").submit();
                       return false;
                     },
                     null,
                     function(){  //afterLoad
                       var popup = $("#popup");

                       popup.find(".buttonYes, .buttonBack").hide();
                       popup.find(".chooseElement").on("click", loadElementFormular);
                     });
        }
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    // Add layerset action
    $(".addLayerset").bind("click", function(){
        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showAjaxModal',
                              {title:"Add layerset",
                               btnOkLabel: "Add"},
                              $(this).attr("href"),
                              function(){ //ok click
                                $("#layersetForm").submit();
                              },
                              null);
        }
        return false;
    });
    // Edit layerset action
    $(".editLayerset").bind("click", function(){
        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showAjaxModal',
                              {title:"Edit layerset",
                               subTitle: " - " + $(this).siblings("legend").text(),
                               btnOkLabel: "Save"},
                              $(this).attr("href"),
                              function(){ //ok click
                                $("#layersetForm").submit();

                                return false;
                              },
                              null);
        }
        return false;
    });
    // Add Instance Action
    $(".addInstance").bind("click", function(event){
        event.preventDefault();

        if(!$('body').data('mapbenderMbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showAjaxModal', {
                    title:"Select source",
                    subTitle: " - " + $(this).parent().siblings(".subTitle").text()
                },
                $(this).attr("href"),
                null,
                null,
                function(){
                    $("#popup").find(".buttonYes").hide();
                });
        }
    });

    // Delete layerset Action
    $(".removeLayerset").bind("click", function(){
        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showAjaxModal', {
                title:"Delete layerset",
                subTitle: " - " + $(this).siblings("legend").text(),
                btnOkLabel: "Delete"
            },
            $(this).attr("href"),
            function(){ //ok click
                $("#deleteLaysersetForm").submit();
                return false;
            },
            null);
        }
        return false;
    });

    // Edit element
    $(".editElement").bind("click", function() {
        var url = $(this).attr("data-url");

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showAjaxModal', {
                    title:"Edit element",
                    method: 'GET'
                },
                url,
                function(){ //ok click
                    $("#elementForm").submit();
                    return false;
                },
                null,
                function(){
                    $("#popupContent").removeClass("popupContent")
                                      .addClass("popupSubContent");
                }
            );
        }
        return false;
    });

    // Delete element
    $('.removeElement').bind("click", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - element",
                    content:"Delete " + title + "?"
                },
                function(){
                    $.ajax({
                        url: me.attr('data-url'),
                        data : {'id': me.attr('data-id')},
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });

    // Delete instance
    $('.removeInstance').bind("click", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - layerset",
                    content:"Delete " + title + "?"
                },
                function(){
                    $.ajax({
                        url: me.attr('data-url'),
                        data : {
                            'slug': me.attr('data-slug'),
                            'id': me.attr('data-id')
                        },
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });
});
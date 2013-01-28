$(function() {
//    $('ul.sourcecollection li fieldset legend').live("click", function(event){
//        console.log("click", $(this).parent().next("div"));
//        if($(this).parent().parent().find("div.sourcecontent").css("display") === "none"){
//            $(this).parent().parent().find("div.sourcecontent").css({"display": "block"});
//        } else {
//            $(this).parent().parent().find("div.sourcecontent").css({"display": "none"})
//        }
//    });
    $("ul.elements" ).sortable({
        connectWith: "ul.elements",
        items: "li:not(.dummy)",
        stop: function( event, ui ) {
//            console.log($(ui.item).parent());
            $(ui.item).parent().find("li").each(function(idx, elm){
                if($(elm).attr("data-href")===$(ui.item).attr("data-href")){
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx - 1,
                            region: $(ui.item).parent().attr("data-region")
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
    $("ul.layerset" ).sortable({
        connectWith: "ul.layerset",
        items: "li:not(.header)",
        stop: function( event, ui ) {
//            console.log($(ui.item).parent());
            $(ui.item).parent().find("li").each(function(idx, elm){
                if($(elm).attr("data-id")===$(ui.item).attr("data-id")){
                    $.ajax({
                        url: $(ui.item).attr("data-href"),
                        type: "POST",
                        data: {
                            number: idx - $("ul.layerset li.header").length
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
    
    $("ul.layercollection" ).sortable({
        connectWith: "ul.layercollection",
        items: "li:not(.header)",
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
    });
});
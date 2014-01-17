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

    $('tr.element, tr.sourceinst').find('input[type="checkbox"]').click(function() {
        if($(this).attr('data-href') === undefined) {
            return;
        }

        $.ajax({
            url: $(this).attr("data-href"),
            type: "POST",
            data: {
                enabled: !$(this).is(":checked")
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
                            },
                        });
                    }
                });
            }
        })
    });

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


    var popup;

    // Layout - Elements ---------------------------------------------------------------------------
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
                    popup.close();
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 10);

                },
                205: function() {
                    popup.close();
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 10);
                }
            }

        });
        e.preventDefault();
        return false;
    };

    function loadElementFormular(){
        var url = $(this).attr("href");
        if(url){
            $.ajax({
                url: url,
                type: "GET",
                complete: function(data){
                    if(data != undefined){
                        var pop = $(".popup");
                        var popupContent   = $(".popupContent");
                        var contentWrapper = pop.find(".contentWrapper");

                        if(contentWrapper.get(0) == undefined){
                            popupContent.wrap('<div class="contentWrapper"></div>');
                            contentWrapper = pop.find(".contentWrapper");
                        }
                        popupContent.hide();
                        var subContent = contentWrapper.find(".popupSubContent");

                        if(subContent.get(0) == undefined){
                            contentWrapper.append('<div class="popupSubContent"></div>');
                            subContent = contentWrapper.find('.popupSubContent');
                        }
                        subContent.html(data.responseText);

                        var subTitle = subContent.find("#form_title").val();
                        $(".popupSubTitle").text(" - " + subTitle);
                        $(".popup").find(".buttonYes, .buttonBack").show();
                        subContent.on('submit', 'form', submitHandler);
                    }
                }
            });
        }

        return false;
    }

    $(".addElement").bind("click", function(){
        var self = $(this);
        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.add_element.title"),
            subtitle: " - " + Mapbender.trans(self.parent().siblings(".subTitle").text()),
            closeOnOutsideClick: true,
            cssClass:"elementPopup",
            content: [
                $.ajax({
                    url: self.attr("href"),
                    complete: function(){
                       var curPopup = $(".popup");

                       curPopup.find(".buttonYes, .buttonBack").hide();
                       curPopup.find(".chooseElement").on("click", loadElementFormular);
                    }
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        popup.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.ok"),
                    cssClass: 'button buttonYes right',
                    callback: function() {
                       $("#elementForm").submit();
                       return false;
                    }
                },
                'back': {
                    label: Mapbender.trans("mb.manager.components.popup.add_element.btn.back"),
                    cssClass: 'button left buttonBack',
                    callback: function() {
                        $(".popupSubContent").remove();
                        $(".popupSubTitle").text("");
                        $(".popup").find(".buttonYes, .buttonBack").hide();
                        $(".popupContent").show();
                    }
                }
            }
        });

        return false;
    });

    // Edit element
    $(".editElement").bind("click", function() {
        var self = $(this);

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title:Mapbender.trans("mb.manager.components.popup.edit_element.title"),
            closeOnOutsideClick: true,
            content: [
                $.ajax({
                    url: self.attr("data-url"),
                    complete: function(){
                        $(".popupContent").removeClass("popupContent")
                                          .addClass("popupSubContent");
                        $('.popupContent form').submit(submitHandler);
                    }
                })
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.edit_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.edit_element.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#elementForm").submit();
                        window.setTimeout(function() {
                            window.location.reload();
                        }, 50);
                    }
                }
            }
        });
        return false;
    });

    // Delete element
    $('.removeElement').bind("click", function(){
        var self = $(this);
        var content = $('<div/>').text(self.attr('title')).html();

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_element.title"),
            subTitle: " - " + Mapbender.trans("mb.manager.components.popup.delete_element.subtitle"),
            closeOnOutsideClick: true,
            content: [content + "?"],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_element.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_element.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data : {'id': self.attr('data-id')},
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
        return false;
    });

    // Layers --------------------------------------------------------------------------------------
    function addOrEditLayerset(edit){
        var self = $(this);

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: ((self.hasClass("editLayerset")) ? Mapbender.trans("mb.manager.components.popup.add_edit_layerset.title_edit")
                                                    : Mapbender.trans("mb.manager.components.popup.add_edit_layerset.title_add")),
            closeOnOutsideClick: true,
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.add_edit_layerset.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#layersetForm").submit();
                    }
                }
            }
        });
        return false;
    }

    // Add layerset action
    $(".addLayerset").bind("click", addOrEditLayerset);
    // Edit layerset action
    $(".editLayerset").bind("click", addOrEditLayerset);
    // Delete layerset Action
    $(".removeLayerset").bind("click", function(){
        var self = $(this);
        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_layerset.title"),
            subTitle: " - " + $(this).siblings("legend").text(),
            closeOnOutsideClick: true,
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_layerset.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_layerset.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $("#deleteLaysersetForm").submit();
                    }
                }
            }
        });
        return false;
    });
    // Add Instance Action
    $(".addInstance").bind("click", function(event){
        var self = $(this);
        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.add_instance.title"),
            subTitle: " - " + self.parent().siblings(".subTitle").text(),
            closeOnOutsideClick: true,
            content: [
                $.ajax({url: self.attr("href")})
            ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.add_instance.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                }
            }
        });
        return false;
    });
    // Delete instance
    $('.removeInstance').bind("click", function(){
        var self  = $(this);
        var content = self.attr('title');


        if(popup){
            popup = popup.destroy();
        }

        popup = new Mapbender.Popup2({
            title: Mapbender.trans("mb.manager.components.popup.delete_instance.title"),
            subtitle: " - layerset",
            closeOnOutsideClick: true,
            content: [content + "?"],
            buttons: {
                'cancel': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_instance.btn.cancel"),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans("mb.manager.components.popup.delete_instance.btn.ok"),
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data : {
                                'slug': self.attr('data-slug'),
                                'id': self.attr('data-id')
                            },
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
        return false;
    });
    $( document ).ready(function() {
        $('#listFilterLayersets .checkWrapper input.checkbox, #containerLayout .checkWrapper input.checkbox').each(function() {
            var self = this;
            initCheckbox.call(this);
            $(self).on("change", function(e){
                $.ajax({
                    url: $(self).attr('data-url'),
                    type: 'POST',
                    data : {
                        'id': $(self).attr('data-id'),
                        'enabled': $(self).is(":checked")
                    },
                    success: function(data) {
                        if(data.success){
                            if(data.success.enabled.after !== $(self).is(":checked"))
                                alert("Cannot be changed!");
                        } else if(data.error){
                                alert(data.error);
                        }
                    }
                });
            });
        });
    });

});

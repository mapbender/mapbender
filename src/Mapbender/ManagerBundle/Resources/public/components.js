/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js
 */
$(function() {
    //
    // Add manually a string trim function for IE8 support
    //
    if(typeof String.prototype.trim !== 'function') {
        String.prototype.trim = function() {
            return this.replace(/^\s+|\s+$/g, '');
        }
    }

    // init tabcontainers --------------------------------------------------------------------
    var tabs = $(".tabContainer").find(".tab");
    tabs.attr("tabindex", 0);
    tabs.bind("click keypress", function(e) {
        if(e.type == "keypress" && e.keyCode != 13) {
            return;
        }

        var me = $(this);
        var tabcont = me.parent().parent();
        $('>.tabs >.tab, >.container', tabcont).removeClass('active');
        me.addClass("active");
        $("#" + me.attr("id").replace("tab", "container"), tabcont).addClass("active");
    });





    // init filter inputs --------------------------------------------------------------------
    $(document).on("keyup", ".listFilterInput", function(){
        var me    = $(this);
        var val   = $.trim(me.val());
        var items = $("#" + me.attr("id").replace("input", "list")).find("li, tr");

        if(val.length > 0){
            var item = null;

            $.each(items, function(i, e){
                item = $(e);
                if(!item.hasClass("doNotFilter")){
                    (item.text().toUpperCase().indexOf(val.toUpperCase()) >= 0) ? item.show()
                                                                                : item.hide();
                }
            });
        }else{
            items.show();
        }
    });






    // init validation feedback --------------------------------------------------------------
    $(document).on("keypress", ".validationInput", function(){
      $(this).siblings(".validationMsgBox").addClass("hide");
    });

    var flashboxes = $(".flashBox").addClass("kill");
    // kill all flashes ---------------------------------------------------------------------
    flashboxes.each(function(idx, item){
        if(idx === 0){
            $(item).removeClass("kill");
        }
        setTimeout(function(){
            $(item).addClass("kill");
            if(flashboxes.length - idx !== 1){
                $(flashboxes.get(idx + 1)).removeClass("kill");
            }
        }, (idx + 1) * 2000);
    });


    // init user box -------------------------------------------------------------------------
    $("#accountOpen").bind("click", function(){
        var menu = $("#accountMenu");
        if(menu.hasClass("opened")){
            menu.removeClass("opened");
        }else{
            menu.addClass("opened");
        }
    });





    // init permissions table ----------------------------------------------------------------
    // set permission root state
    function setPermissionsRootState(className){
        var root         = $("#" + className);
        var permBody     = $("#permissionsBody");
        var rowCount     = permBody.find("tr").length;
        var checkedCount = permBody.find(".checkWrapper." + className + ' > input[type="checkbox"]:checked').length;
        root.removeClass("active").removeClass("multi");

        if(rowCount == checkedCount){
            root.addClass("active");
        }else if(checkedCount == 0){
            // do nothing!
        }else{
            root.addClass("multi");
        }
    }
    // toggle all permissions
    var toggleAllPermissions = function(){
        var self           = $(this);
        var className    = self.attr("id");
        var permElements = $(".checkWrapper[data-perm-type=" + className + "]:visible");
        var state        = !self.hasClass("active");
        $('input[type="checkbox"]', permElements).prop('checked', state).each(function() {
            $(this).parent().toggleClass("active", state);
        });

        // change root permission state
        setPermissionsRootState(className);
    }
    // init permission root state
    var initPermissionRoot = function(){
        $(this).find(".headTagWrapper").each(function(){
            setPermissionsRootState($(this).attr("id"));
            $(this).bind("click", toggleAllPermissions);
        });
    }
    $("#permissionsHead").one("load", initPermissionRoot).load();

    // toggle permission Event
    var togglePermission = function(){
        setPermissionsRootState($(this).attr("data-perm-type"));
    }
    $(document).on("click", ".permissionsTable .checkWrapper", togglePermission);

    var popup;

    // add user or groups
    $("#addPermission").bind("click", function(){
        var self    = $(this);
        var url     = self.attr("href");
        var content = self.attr('title');

        if(popup){
            popup = popup.destroy();
        }

        if(url.length > 0){
            popup = new Mapbender.Popup2({
                title: Mapbender.trans('fom.core.components.popup.add_user_group.title'),
                closeOnOutsideClick: true,
                height: 400,
                content: [
                    $.ajax({
                        url: url,
                        complete: function() {
                            var groupUserItem, roleName, me, groupUserType, groupName;

                            $("#listFilterGroupsAndUsers").find(".filterItem").each(function(i, e){
                                groupUserItem = $(e);
                                groupUserType = (groupUserItem.find(".tdContentWrapper")
                                                              .hasClass("iconGroup") ? "iconGroup"
                                                                                     : "iconUser");
                                $("#permissionsBody").find(".labelInput").each(function(i, e) {
                                    me = $(e);
                                    roleName = me.text().trim().toUpperCase();
                                    groupName = $(".labelInput", groupUserItem).text().toUpperCase();
                                    var isUserType = (me.parent().hasClass(groupUserType));

                                    if(roleName.indexOf("ROLE_GROUP_") === 0) {
                                        groupName = "ROLE_GROUP_" + groupName;
                                    }

                                    if(groupName == roleName && isUserType) {
                                        groupUserItem.remove();
                                    }
                                });
                            });
                        }
                    })
                ],
                buttons: {
                    'cancel': {
                        label: Mapbender.trans('fom.core.components.popup.add_user_group.btn.cancel'),
                        cssClass: 'button buttonCancel critical right',
                        callback: function() {
                            this.close();
                        }
                    },
                    'add': {
                        label: Mapbender.trans('fom.core.components.popup.add_user_group.btn.add'),
                        cssClass: 'button right',
                        callback: function() {
                            var proto = $("#permissionsHead").attr("data-prototype");

                            if(proto.length > 0){
                                var body  = $("#permissionsBody");
                                var count = body.find("tr").length;
                                var text, val, parent, newEl;

                                $("#listFilterGroupsAndUsers").find(".iconCheckboxActive").each(function(i, e){
                                    parent   = $(e).parent();
                                    text     = parent.find(".labelInput").text().trim();
                                    val      = parent.find(".hide").text().trim();
                                    userType = parent.hasClass("iconGroup") ? "iconGroup" : "iconUser";
                                    newEl = body.prepend(proto.replace(/__name__/g, count))
                                                .find("tr:first");

                                    newEl.addClass("new").find(".labelInput").text(text);
                                    newEl.find(".input").attr("value", val);
                                    newEl.find(".view.checkWrapper").trigger("click");
                                    newEl.find(".userType")
                                         .removeClass("iconGroup")
                                         .removeClass("iconUser")
                                         .addClass(userType);
                                    ++count;
                                });

                                this.close();
                                $(".permissionsTable").show();
                                $("#permissionsDescription").hide();
                            }
                        }
                    }
                }
            });
        }

        return false;
    });
    var popup;

    var deleteUserGroup = function(){
        var self = $(this);
        var parent = self.parent().parent();
        var userGroup = ((parent.find(".iconUser").length == 1) ? "user " : "group ") + parent.find(".labelInput").text();

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title: Mapbender.trans('fom.core.components.popup.delete_user_group.title'),
            closeOnOutsideClick: true,
            content: [ Mapbender.trans('fom.core.components.popup.delete_user_group.content',{'userGroup': userGroup}) ],
            buttons: {
                'cancel': {
                    label: Mapbender.trans('fom.core.components.popup.delete_user_group.btn.cancel'),
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: Mapbender.trans('fom.core.components.popup.delete_user_group.btn.ok'),
                    cssClass: 'button right',
                    callback: function() {
                        parent.remove();
                        this.close();
                    }
                }
            }
        });
        return false;
    }
    $("#permissionsBody").on("click", '.iconRemove', deleteUserGroup);






    // init open toggle trees ----------------------------------------------------------------
    var toggleTree = function(){
        var me     = $(this);
        var parent = me.parent();
        if(parent.hasClass("closed")){
            me.removeClass("iconExpandClosed").addClass("iconExpand");
            parent.removeClass("closed");
        }else{
            me.addClass("iconExpandClosed").removeClass("iconExpand");
            parent.addClass("closed");
        }
    }
    $(".openCloseTitle").bind("click", toggleTree);
    $('.regionProperties .radiobox').each(function() {
        $(this).parent(".radioWrapper").attr('data-icon')
        initRadioButton.call(this, false, $(this).parent(".radioWrapper").attr('data-icon') + $(this).val());
    });
});

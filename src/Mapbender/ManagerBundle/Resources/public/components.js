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
        var $this = $(this);
        var val = $.trim($this.val());
        var filterTargetId = $this.attr('data-filter-target');
        if (!filterTargetId) {
            filterTargetId = $this.attr('id').replace("input", "list");
        }
        var filterScope = filterTargetId && $('#' + filterTargetId);
        if (!filterTargetId || !filterScope.length) {
            console.error("Could not find target for list filter", this, filterTargetId);
            return;
        }
        var items = $("li, tr", filterScope).not('.doNotFilter');

        if(val.length > 0){
            $.each(items, function() {
                var $item = $(this);
                var containsInput = $item.text().toUpperCase().indexOf(val.toUpperCase()) !== -1;
                $item.toggle(containsInput);
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
    function setPermissionsRootState(className, scope){
        var root         = $("#" + className, scope);
        var permBody     = $("#permissionsBody", scope);
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
    var toggleAllPermissions = function(scope){
        var self           = $(this);
        var className    = self.attr("id");
        var permElements = $(".checkWrapper[data-perm-type=" + className + "]", scope);
        var state        = !self.hasClass("active");
        $('input[type="checkbox"]', permElements).prop('checked', state).each(function() {
            $(this).parent().toggleClass("active", state);
        });

        // change root permission state
        setPermissionsRootState(className, scope);
    }
    // init permission root state
    var initPermissionRoot = function(){
        var $head = $(this);
        var $table = $head.closest('table');
        $head.find(".headTagWrapper").each(function(){
            setPermissionsRootState($(this).attr("id"), $table);
            var self = this;
            $(this).on('click', function() {
                toggleAllPermissions.call(self, $table);
            });
        });
    }
    $("#permissionsHead").one("load", initPermissionRoot).load();

    // toggle permission Event
    var togglePermission = function(){
        var $this = $(this);
        var scope = $this.closest('table');
        setPermissionsRootState($this.attr("data-perm-type"), scope);
    }
    $(document).on("click", ".permissionsTable .checkWrapper", togglePermission);

    var popup;

    // add user or groups
    // Remaining FOM markup uses an anchor with a href, which allows undesirable "open in new tab" interactions and
    // also causes some CSS quirks
    // Modern markup uses a div with a data-href attribute
    // @todo: scoping; unscoped, there can only be one user list in the markup at any given time
    $(".-fn-add-permission, #addPermission").bind("click", function(event){
        event.preventDefault();
        event.stopPropagation();
        var $this = $(this);
        var url = $this.attr('data-url') || $this.attr("href");

        if(popup){
            popup = popup.destroy();
        }

        if (url.length > 0) {
            $.ajax({
                url: url
            }).then(function(response) {
                popup = new Mapbender.Popup({
                    title: Mapbender.trans('fom.core.components.popup.add_user_group.title'),
                    closeOnOutsideClick: true,
                    content: response,
                    buttons: [
                        {
                            label: Mapbender.trans('fom.core.components.popup.add_user_group.btn.add'),
                            cssClass: 'button',
                            callback: function() {
                                var body  = $("#permissionsBody");
                                var proto = $("#permissionsHead").attr("data-prototype");

                                if (proto) {
                                    var count = body.find("tr").length;
                                    var text, val, newEl;

                                    $('#listFilterGroupsAndUsers input[type="checkbox"]:checked', popup.$element).each(function() {
                                        var $row = $(this).closest('tr');
                                        var userType = $('.tdContentWrapper', $row).hasClass("iconGroup") ? "iconGroup" : "iconUser";
                                        text = $row.find(".labelInput").text().trim();
                                        val = $row.find(".hide").text().trim();
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
                        },
                        {
                            label: Mapbender.trans('fom.core.components.popup.add_user_group.btn.cancel'),
                            cssClass: 'button buttonCancel critical',
                            callback: function() {
                                this.close();
                            }
                        }
                    ]
                });

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

/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js
 */
$(function() {
    // init tabcontainers --------------------------------------------------------------------
    var tabs = $(".tabContainer").find(".tab");
    tabs.attr("tabindex", 0);
    tabs.bind("click keypress", function(e) {
        if (e.type === "keypress" && e.keyCode !== 13) {
            return;
        }

        var me = $(this);
        var tabcont = me.parent().parent();
        $('>.tabs >.tab, >.container', tabcont).removeClass('active');
        me.addClass("active");
        $("#" + me.attr("id").replace("tab", "container"), tabcont).addClass("active");
    });

    // List toggles used in source views (collapsible layer / matrix nodes)
    $(".openCloseTitle").on("click", function() {
        var $title = $(this);
        var $list = $title.parent();
        if ($list.hasClass("closed")) {
            $title.removeClass("iconExpandClosed").addClass("iconExpand");
            $list.removeClass("closed");
        }else{
            $title.addClass("iconExpandClosed").removeClass("iconExpand");
            $list.addClass("closed");
        }
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
        var root         = $('thead .tagbox[data-perm-type="' + className + '"]', scope);
        var permBody     = $("tbody", scope);
        var rowCount     = permBody.find("tr").length;
        var checkedCount = permBody.find(".tagbox." + className + ' input[type="checkbox"]:checked').length;
        root.toggleClass("active", !!rowCount && checkedCount === rowCount);
        root.toggleClass("multi", !!(rowCount && checkedCount) && checkedCount < rowCount);
    }
    // toggle all permissions
    var toggleAllPermissions = function(scope){
        var self           = $(this);
        var className    = self.attr("data-perm-type");
        var permElements = $("tbody .checkWrapper[data-perm-type=" + className + "]", scope);
        var state        = !self.hasClass("active");
        $('input[type="checkbox"]', permElements).prop('checked', state).each(function() {
            $(this).parent().toggleClass("active", state);
        });

        // change root permission state
        setPermissionsRootState(className, scope);
    };
    function appendAces($permissionsTable, $sidSelector, defaultPermissions) {
        var body = $("tbody", $permissionsTable);
        var proto = $("thead", $permissionsTable).attr("data-prototype");

        var count = body.find("tr").length;
        $('input[type="checkbox"]:checked', $sidSelector).each(function() {
            // see FOM/UserBundle/Resoruces/views/ACL/groups-and-users.html.twig
            var $row = $(this).closest('tr');
            var sid = $('span.hide', $row).text();
            var sidType = (sid.split(':')[0]).toUpperCase();
            var text = $row.find(".labelInput").text().trim();
            var newEl = $(proto.replace(/__name__/g, count++));
            newEl.addClass('new');
            newEl.attr('data-sid', sid);
            $('.labelInput', newEl).text(text);
            body.prepend(newEl);
            newEl.find(".input").attr("value", sid);
            (defaultPermissions || []).map(function(permissionName) {
                $('.tagbox[data-perm-type="' + permissionName + '"]', newEl).trigger('click');
            });
            $('.userType', newEl)
                .toggleClass('iconGroup', sidType === 'R')
                .toggleClass('iconUser', sidType === 'U')
            ;
        });
        // if table was previously empty, reveal it and hide placeholder text
        $permissionsTable.removeClass('hidePermissions');
        $('#permissionsDescription', $permissionsTable.parent()).addClass('hidden');
    }
    function filterSidContent(response, $permissionsTable) {
        var $content = $(response);
        $('tbody tr.filterItem', $content).each(function() {
            var groupUserItem = $(this);
            // see FOM/UserBundle/Resoruces/views/ACL/groups-and-users.html.twig
            var newItemSid = $('span.hide', groupUserItem).text();
            $('tbody .userType', $permissionsTable).each(function() {
                var existingRowSid = $(this).closest('tr').attr('data-sid');

                if (existingRowSid === newItemSid) {
                    groupUserItem.remove();
                }
            });
        });
        return $content;
    }

    var initPermissionRoot = function() {
        var $table = $(this);
        var $head = $('thead', this);

        $head.find(".tagbox").each(function() {
            setPermissionsRootState($(this).attr("data-perm-type"), $table);
        });
        $head.on('click', '.tagbox', function() {
            toggleAllPermissions.call(this, $table);
        });
    };
    // toggle permission Event
    var togglePermission = function(){
        var $this = $(this);
        var scope = $this.closest('table');
        setPermissionsRootState($this.attr("data-perm-type"), scope);
    };
    $(document).on("click", ".permissionsTable .checkWrapper", togglePermission);

    // add user or groups
    // Remaining FOM markup uses an anchor with a href, which allows undesirable "open in new tab" interactions and
    // also causes some CSS quirks
    // Modern markup uses a div with a data-href attribute
    // @todo: scoping; unscoped, there can only be one user list in the markup at any given time
    $(".-fn-add-permission, #addPermission").bind("click", function(event) {
        event.preventDefault();
        event.stopPropagation();
        var $this = $(this);
        var url = $this.attr('data-url') || $this.attr("href");
        var $targetTable = $('.permissionsTable', $this.closest('.tabContainer,.container,.popup'));

        if (url.length > 0) {
            $.ajax({
                url: url
            }).then(function(response) {
                var popup = new Mapbender.Popup({
                    title: Mapbender.trans('fom.core.components.popup.add_user_group.title'),
                    closeOnOutsideClick: true,
                    content: filterSidContent(response, $targetTable), //response,
                    buttons: [
                        {
                            label: Mapbender.trans('fom.core.components.popup.add_user_group.btn.add'),
                            cssClass: 'button',
                            callback: function() {
                                appendAces($targetTable, $('#listFilterGroupsAndUsers', popup.$element), ['view']);
                                this.close();
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
            });
        }

        return false;
    });
    $(".permissionsTable").on("click", '.iconRemove', function() {
        var $row = $(this).closest('tr');
        var userGroup = ($('.iconUser', $row).length  ? "user " : "group ") + $('.labelInput', $row).text();
        var content = [
            '<div>',
            Mapbender.trans('fom.core.components.popup.delete_user_group.content',{'userGroup': userGroup}),
            '</div>'
            ].join('');
        var labels = {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            cancel: "mb.manager.components.popup.delete_element.btn.cancel",
            confirm: "mb.manager.components.popup.delete_element.btn.ok"
        };
        Mapbender.Manager.confirmDelete(null, null, labels, content).then(function() {
            $row.remove();
        });
    }).each(initPermissionRoot);

    // Element security
    function initElementSecurity(response) {
        var popup;
        var $initialView, $permissionsTable;
        var isModified = false;
        var popupOptions = {
            title: "Secure element",
            closeOnOutsideClick: true,
            content: response,
            buttons: [
                {
                    // @todo: provide distinct label
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.back'),
                    cssClass: 'button buttonReset hidden left',
                    callback: function() {
                        // reload entire popup
                        initElementSecurity(response);
                    }
                },
                {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.back'),
                    cssClass: 'button buttonBack hidden left',
                    callback: function() {
                        $('.contentItem', popup.$element).not($initialView).remove();
                        $initialView.removeClass('hidden');

                        $(".buttonAdd,.buttonBack,.buttonRemove", popup.$element).addClass('hidden');
                        $(".buttonOk", popup.$element).removeClass('hidden');
                        $('.buttonReset', popup.$element).toggleClass('hidden', !isModified);
                    }
                },
                {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.remove'),
                    cssClass: 'button buttonRemove hidden',
                    callback: function(evt) {
                        var $button = $(evt.currentTarget);
                        $('.contentItem', popup.$element).not($initialView).remove();
                        $initialView.removeClass('hidden');
                        $button.data('target-row').remove();
                        $button.data('target-row', null);
                        isModified = true;

                        $(".buttonAdd,.buttonRemove,.buttonBack", popup.$element).addClass('hidden');
                        $(".buttonOk,.buttonReset", popup.$element).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.add'),
                    cssClass: 'button buttonAdd hidden',
                    callback: function() {
                        $(".contentItem:first", popup.$element).removeClass('hidden');
                        if ($(".contentItem", popup.$element).length > 1) {
                            appendAces($permissionsTable, $('#listFilterGroupsAndUsers', popup.$element), ['view']);
                            $(".contentItem:not(.contentItem:first)", popup.$element).remove();
                        }
                        isModified = true;
                        $(".buttonAdd,.buttonBack", popup.$element).addClass('hidden');
                        $(".buttonOk,.buttonReset", popup.$element).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.ok'),
                    cssClass: 'button buttonOk',
                    callback: function() {
                        $("#elementSecurity", popup.$element).submit();
                        window.setTimeout(function() {
                            window.location.reload();
                        }, 50);
                    }
                },
                {
                    label: Mapbender.trans('mb.manager.components.popup.element_acl.btn.cancel'),
                    cssClass: 'button buttonCancel critical',
                    callback: function() {
                        this.close();
                    }
                }
            ]
        };
        popup = new Mapbender.Popup(popupOptions);
        $initialView = $(".contentItem:first", popup.$element);
        $permissionsTable = $('.permissionsTable', $initialView);
        $permissionsTable.each(initPermissionRoot);

        $('#addElmPermission', popup.$element).on('click', function(e) {
            var $anchor = $(this);
            var url = $anchor.attr('data-href') || $anchor.attr('href');
            e.preventDefault();
            e.stopPropagation();
            $.ajax({
                url: url,
                type: "GET",
                success: function(data) {
                    $(".contentItem:first,.buttonOk,.buttonReset", popup.$element).addClass('hidden');
                    $(".buttonAdd,.buttonBack", popup.$element).removeClass('hidden');
                    popup.addContent(filterSidContent(data, $permissionsTable));
                }
            });
            return false;
        });
        $permissionsTable.on("click", 'tbody .iconRemove', function() {
            var $row = $(this).closest('tr');
            var userGroup =($row.find(".iconUser").length ? "user " : "group ") + $row.find(".labelInput").text();
            popup.addContent(Mapbender.trans('fom.core.components.popup.delete_user_group.content', {'userGroup': userGroup}));
            $(".contentItem:first,.buttonOk,.buttonReset", popup.$element).addClass('hidden');
            $('.buttonRemove', popup.$element).data('target-row', $row);
            $(".buttonRemove,.buttonBack", popup.$element).removeClass('hidden');
        });
    }

    $(".secureElement").on("click", function() {
        $.ajax({
            url: $(this).attr('data-url')
        }).then(function(response) {
            initElementSecurity(response);
        });
        return false;
    });


});

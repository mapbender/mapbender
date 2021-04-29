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
    var activeTab = (window.location.hash || '').substring(1);
    $(".tabContainer, .tabContainerAlt").on('click', '.tab', function() {
        var tabId = $(this).attr('id');
        // rewrite url fragment without scrolling page
        // see https://stackoverflow.com/questions/3870057/how-can-i-update-window-location-hash-without-jumping-the-document
        window.history.replaceState(null, null, '#' + tabId);
    });
    if (activeTab) {
        $('#' + activeTab, $('.tabContainer, .tabContainerAlt')).click();
    }

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

    $('#listFilterApplications, #listFilterGroups, #listFilterUsers').on("click", '.-fn-delete[data-url]', function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        });
        return false;
    });

    // init filter inputs --------------------------------------------------------------------
    $(document).on("keyup", ".listFilterInput[data-filter-target]", function(){
        var $this = $(this);
        var val = $.trim($this.val());
        var filterTargetId = $this.attr('data-filter-target');
        var filterScope = filterTargetId && $this.closest('#' + filterTargetId);
        if (filterTargetId && !filterScope.length) {
            filterScope = $(document.getElementById(filterTargetId));
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
            var $checkbox = $(this);
            var sid = $checkbox.val();
            var sidType = (sid.split(':')[0]).toUpperCase();
            var text = $checkbox.attr('data-label');
            var newEl = $(proto.replace(/__name__/g, count++));
            newEl.addClass('bg-success');
            newEl.attr('data-sid', sid);
            newEl.attr('data-sid-label', text);
            var $sidInput = $('input[type="hidden"]', newEl).first();
            $sidInput.attr('value', sid);
            $('.sid-label', newEl).text(text);
            body.prepend(newEl);

            (defaultPermissions || []).map(function(permissionName) {
                $('.tagbox[data-perm-type="' + permissionName + '"]', newEl).trigger('click');
            });
            $('.userType', newEl)
                .toggleClass('fa-group', sidType === 'R')
                .toggleClass('fa-user', sidType === 'U')
            ;
        });
        // if table was previously empty, reveal it and hide placeholder text
        $permissionsTable.removeClass('hidden');
        $('#permissionsDescription', $permissionsTable.closest('.ace-collection')).addClass('hidden');
    }
    function filterSidContent(response, $permissionsTable) {
        var $content = $(response);
        $('tbody tr.filterItem', $content).each(function() {
            var groupUserItem = $(this);
            // see FOM/UserBundle/Resoruces/views/ACL/groups-and-users.html.twig
            var newItemSid = $('input[type="checkbox"]', groupUserItem).first().val();
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

    $(document).on('click', '.ace-collection .-fn-add-permission[data-url]', function(event) {
        var $this = $(this);
        var url = $this.attr('data-url');
        var $targetTable = $('table', $this.closest('.ace-collection'));

        if (url.length > 0) {
            $.ajax({
                url: url
            }).then(function(response) {
                var popup = new Mapbender.Popup({
                    title: Mapbender.trans('mb.manager.managerbundle.add_user_group'),
                    content: filterSidContent(response, $targetTable), //response,
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.add'),
                            cssClass: 'btn btn-success btn-sm',
                            callback: function() {
                                appendAces($targetTable, $('#listFilterGroupsAndUsers', popup.$element), ['view']);
                                this.close();
                            }
                        },
                        {
                            label: Mapbender.trans('mb.actions.cancel'),
                            cssClass: 'btn btn-default btn-sm popupClose'
                        }
                    ]
                });
            });
        }

        return false;
    });
    $(".permissionsTable").on("click", '.-fn-delete', function() {
        var $row = $(this).closest('tr');
        var sidLabel = $row.attr('data-sid-label');
        var typePrefix = ($row.attr('data-sid') || '').slice(0, 1) === 'u' ? 'user' : 'group';

        var content = [
            '<div>',
            Mapbender.trans('mb.manager.components.popup.delete_user_group.content', {
                'userGroup': [typePrefix, sidLabel].join(" ")
            }),
            '</div>'
            ].join('');
        var labels = {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        };
        Mapbender.Manager.confirmDelete(null, null, labels, content).then(function() {
            $row.remove();
        });
    }).each(initPermissionRoot);

    // Element security
    function initElementSecurity(response, url) {
        var $content = $(response);
        // submit back to same url (would be automatic outside of popup scope)
        $content.filter('form').attr('action', url);

        var popup;
        var $initialView, $permissionsTable;
        var isModified = false;
        var popupOptions = {
            title: "Secure element",
            content: [$content],
            buttons: [
                {
                    // @todo: provide distinct label
                    label: Mapbender.trans('mb.actions.back'),
                    cssClass: 'btn btn-warning btn-sm buttonReset hidden pull-left',
                    callback: function() {
                        // reload entire popup
                        initElementSecurity(response, url);
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.back'),
                    cssClass: 'btn btn-default btn-sm buttonBack hidden pull-left',
                    callback: function() {
                        $('.contentItem', popup.$element).not($initialView).remove();
                        $initialView.removeClass('hidden');

                        $(".buttonAdd,.buttonBack,.buttonRemove", popup.$element).addClass('hidden');
                        $(".buttonOk", popup.$element).removeClass('hidden');
                        $('.buttonReset', popup.$element).toggleClass('hidden', !isModified);
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.remove'),
                    cssClass: 'btn btn-danger btn-sm buttonRemove hidden',
                    callback: function(evt) {
                        var $button = $(evt.currentTarget);
                        $('.contentItem', popup.$element).not($initialView).remove();
                        $initialView.removeClass('hidden');
                        $button.data('target-row').remove();
                        $button.data('target-row', null);
                        isModified = true;

                        $(".buttonAdd,.buttonRemove,.buttonBack", popup.$element).addClass('hidden');
                        $(".buttonOk,.buttonReset,.buttonCancel", popup.$element).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.add'),
                    cssClass: 'btn btn-success btn-sm buttonAdd hidden',
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
                    label: Mapbender.trans('mb.actions.save'),
                    cssClass: 'btn btn-success btn-sm buttonOk',
                    callback: function() {
                        $("form", popup.$element).submit();
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.cancel'),
                    cssClass: 'btn btn-default btn-sm buttonCancel popupClose'
                }
            ]
        };
        popup = new Mapbender.Popup(popupOptions);
        $initialView = $(".contentItem:first", popup.$element);
        $permissionsTable = $('.permissionsTable', $initialView);
        $permissionsTable.each(initPermissionRoot);

        $('.-fn-add-permission', popup.$element).on('click', function(e) {
            var url = $(this).attr('data-url');
            $.ajax({
                url: url,
                type: "GET",
                success: function(data) {
                    $(".contentItem:first,.buttonOk,.buttonReset", popup.$element).addClass('hidden');
                    $(".buttonAdd,.buttonBack", popup.$element).removeClass('hidden');
                    popup.addContent(filterSidContent(data, $permissionsTable));
                }
            });
            // Suppress call to global handler
            return false;
        });
        $permissionsTable.on("click", 'tbody .-fn-delete', function() {
            var $row = $(this).closest('tr');
            var sidLabel = $row.attr('data-sid-label');
            popup.addContent(Mapbender.trans('mb.manager.components.popup.delete_user_group.content', {
                'userGroup': sidLabel
            }));
            $(".contentItem:first,.buttonOk,.buttonReset,.buttonCancel", popup.$element).addClass('hidden');
            $('.buttonRemove', popup.$element).data('target-row', $row);
            $(".buttonRemove,.buttonBack", popup.$element).removeClass('hidden');
        });
    }

    $(".secureElement").on("click", function() {
        var url = $(this).attr('data-url');
        $.ajax({
            url: url
        }).then(function(response) {
            initElementSecurity(response, url);
        });
        return false;
    });
    $('.elementsTable').on('click', '.screentype-icon[data-screentype]', function() {
        var $target = $(this);
        var $group = $target.closest('.screentypes');
        var $other = $('.screentype-icon[data-screentype]', $group).not($target);
        var newScreenType;
        if (!$target.hasClass('disabled')) {
            newScreenType = $other.attr('data-screentype');
        } else {
            newScreenType = 'all';
        }
        $.ajax($group.attr('data-url'), {
            method: 'POST',
            data: {
                screenType: newScreenType
            }
        }).then(function() {
            $other.removeClass('disabled');
            $target.toggleClass('disabled');
        });
    });

    $(document).on('click', '.-fn-toggle-flag[data-url]', function() {
        var $this = $(this);
        $this.toggleClass('-js-on -js-off');
        var iconSet = $this.attr('data-toggle-flag-icons').split(':');
        var $icon = $('>i', this);
        var enabled = !!$this.hasClass('-js-on');
        $.ajax($this.attr('data-url'), {
            method: 'POST',
            dataType: 'json',
            data: {
                // Send string "true" or string "false"
                enabled: "" + enabled
            }
        }).then(function() {
            $icon
                .toggleClass(iconSet[1], enabled)
                .toggleClass(iconSet[0], !enabled)
            ;
        });
    });
});

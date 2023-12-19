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
    $(".tabContainer, .tabContainerAlt").on('click', '.tab, ul.nav>li[id]', function() {
        var tabId = $(this).attr('id');
        // rewrite url fragment without scrolling page
        // see https://stackoverflow.com/questions/3870057/how-can-i-update-window-location-hash-without-jumping-the-document
        window.history.replaceState(null, null, '#' + tabId);
    });
    if (activeTab) {
        var $activeTabHeader = $('#' + activeTab, $('.tabContainer, .tabContainerAlt'));
        var $navLink = $('>a', $activeTabHeader);
        ($navLink.length && $navLink || $activeTabHeader).click();
    }

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
        var items = $(">li, >tr, >tbody>tr", filterScope).not('.doNotFilter');

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
    };
    $(document).on('click', '.permissionsTable tbody .tagbox[data-perm-type]', function() {
        var $this = $(this);
        var $cb = $('input[type="checkbox"]', this);
        $cb.trigger('click');
        $this.toggleClass('active', !!$cb.prop('checked'));
        var scope = $this.closest('table');
        setPermissionsRootState($this.attr("data-perm-type"), scope);
    });
    $(document).on('click', '.permissionsTable thead .tagbox[data-perm-type]', function() {
        var $this = $(this);
        var $table = $(this).closest('table');
        var permType = $this.attr("data-perm-type");
        var permElements = $("tbody .tagbox[data-perm-type=" + permType + "]", $table);
        $this.removeClass('multi');
        $this.toggleClass('active');
        var state = $this.hasClass("active");
        $('input[type="checkbox"]', permElements).prop('checked', state).each(function() {
            $(this).parent().toggleClass("active", state);
        });
    });

    $(document).on('click', '.ace-collection .-fn-add-permission[data-url]', function(event) {
        var $this = $(this);
        var url = $this.attr('data-url');
        var $targetTable = $('table', $this.closest('.ace-collection'));

        if (url.length > 0) {
            $.ajax({
                url: url
            }).then(function(response) {
                var $modal = Mapbender.bootstrapModal(filterSidContent(response, $targetTable), {
                    title: Mapbender.trans('mb.manager.managerbundle.add_user_group'),
                    buttons: [
                        {
                            label: Mapbender.trans('mb.actions.add'),
                            cssClass: 'btn btn-success btn-sm',
                            callback: function() {
                                appendAces($targetTable, $('#listFilterGroupsAndUsers', $modal), ['view']);
                                $modal.modal('hide');
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
        var $initialView = $(document.createElement('div')).addClass('contentItem').append($content);

        var $modal;
        var $permissionsTable;
        var isModified = false;
        var popupOptions = {
            title: "Secure element",
            buttons: [
                {
                    label: Mapbender.trans('mb.actions.reset'),
                    cssClass: 'btn btn-warning btn-sm buttonReset hidden pull-left',
                    callback: function() {
                        // reload entire popup
                        $modal.modal('hide');
                        initElementSecurity(response, url);
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.back'),
                    cssClass: 'btn btn-default btn-sm buttonBack hidden pull-left',
                    callback: function() {
                        $('.contentItem', $modal).not($initialView).remove();
                        $initialView.removeClass('hidden');

                        $(".buttonAdd,.buttonBack,.buttonRemove", $modal).addClass('hidden');
                        $(".buttonOk", $modal).removeClass('hidden');
                        $('.buttonReset', $modal).toggleClass('hidden', !isModified);
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.remove'),
                    cssClass: 'btn btn-danger btn-sm buttonRemove hidden',
                    callback: function(evt) {
                        var $button = $(evt.currentTarget);
                        $('.contentItem', $modal).not($initialView).remove();
                        $initialView.removeClass('hidden');
                        $button.data('target-row').remove();
                        $button.data('target-row', null);
                        isModified = true;

                        $(".buttonAdd,.buttonRemove,.buttonBack", $modal).addClass('hidden');
                        $(".buttonOk,.buttonReset,.buttonCancel", $modal).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.add'),
                    cssClass: 'btn btn-success btn-sm buttonAdd hidden',
                    callback: function() {
                        $(".contentItem:first", $modal).removeClass('hidden');
                        if ($(".contentItem", $modal).length > 1) {
                            appendAces($permissionsTable, $('#listFilterGroupsAndUsers', $modal), ['view']);
                            $(".contentItem:not(.contentItem:first)", $modal).remove();
                        }
                        isModified = true;
                        $(".buttonAdd,.buttonBack", $modal).addClass('hidden');
                        $(".buttonOk,.buttonReset", $modal).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.save'),
                    cssClass: 'btn btn-success btn-sm buttonOk',
                    callback: function() {
                        $("form", $modal).submit();
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.cancel'),
                    cssClass: 'btn btn-default btn-sm buttonCancel popupClose'
                }
            ]
        };
        $modal = Mapbender.bootstrapModal($initialView, popupOptions);
        // HACK
        var addContent = function(content) {
            var $wrapper = $(document.createElement('div')).addClass('contentItem');
            $wrapper.append(content);
            $('.modal-body', $modal).append($wrapper);
        };
        $permissionsTable = $('.permissionsTable', $initialView);
        $permissionsTable.each(initPermissionRoot);

        $('.-fn-add-permission', $initialView).on('click', function(e) {
            var url = $(this).attr('data-url');
            $.ajax({
                url: url,
                type: "GET",
                success: function(data) {
                    $(".contentItem:first,.buttonOk,.buttonReset", $modal).addClass('hidden');
                    $(".buttonAdd,.buttonBack", $modal).removeClass('hidden');
                    addContent(filterSidContent(data, $permissionsTable));
                }
            });
            // Suppress call to global handler
            return false;
        });
        $permissionsTable.on("click", 'tbody .-fn-delete', function() {
            var $row = $(this).closest('tr');
            var sidLabel = $row.attr('data-sid-label');
            addContent(Mapbender.trans('mb.manager.components.popup.delete_user_group.content', {
                'userGroup': sidLabel
            }));
            $(".contentItem:first,.buttonOk,.buttonReset,.buttonCancel", $modal).addClass('hidden');
            $('.buttonRemove', $modal).data('target-row', $row);
            $(".buttonRemove,.buttonBack", $modal).removeClass('hidden');
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
                screenType: newScreenType,
                token: $group.attr('data-token'),
            }
        }).then(function() {
            $other.removeClass('disabled');
            $target.toggleClass('disabled');
        });
    });

    $(document).on('click', '.-fn-toggle-flag[data-url]', function() {
        if (this.type === 'checkbox') {
            return true;
        }
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
                enabled: "" + enabled,
                token: $this.attr('data-token'),
            }
        }).then(function() {
            $icon
                .toggleClass(iconSet[1], enabled)
                .toggleClass(iconSet[0], !enabled)
            ;
        });
    });
    $(document).on('change', 'input[type="checkbox"][data-url].-fn-toggle-flag', function() {
        var $this = $(this);
        $.ajax($this.attr('data-url'), {
            method: 'POST',
            dataType: 'json',
            data: {
                // Send string "true" or string "false"
                enabled: "" + $this.prop('checked'),
                token: $this.attr('data-token'),
            }
        })
    });
});

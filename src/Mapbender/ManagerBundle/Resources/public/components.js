$(function() {
    // init tabcontainers --------------------------------------------------------------------
    const $tabContainer = $(".tabContainer, .tabContainerAlt");
    var activeTab = (window.location.hash || '').substring(1);
    $tabContainer.on('click', '.nav-link[id]', function() {
        var tabId = $(this).attr('id');
        // rewrite url fragment without scrolling page
        // see https://stackoverflow.com/questions/3870057/how-can-i-update-window-location-hash-without-jumping-the-document
        window.history.replaceState(null, null, '#' + tabId);
    });
    if (activeTab) {
        $tabContainer.find('#' + activeTab).tab('show');
    }

    $('#listFilterApplications, #listFilterGroups, #listFilterUsers').on("click", '.-fn-delete[data-url]', function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        });
        return false;
    });

    $(document).on('click', ".sortByColumn", function () {
        var $this = $(this);
        var asc = true;

        $this.siblings().find('span').removeClass().addClass('fa fa-sort').prop('title', Mapbender.trans('mb.actions.sort_ascending'));

        if ($this.find('span').hasClass('fa fa-sort-amount-asc')) {
            asc = false;
            $this.find('span').removeClass().addClass('fa fa-sort-amount-desc').prop('title', Mapbender.trans('mb.actions.sort_ascending'));
        } else {
            $this.find('span').removeClass().addClass('fa fa-sort-amount-asc').prop('title', Mapbender.trans('mb.actions.sort_descending'));
        }

        var table = $this.parents('table').eq(0);
        var rows = table.find('tr:gt(0)').toArray().sort(comparer($this.index()));

        if (!asc) {
            rows = rows.reverse();
        }
        for (var i = 0; i < rows.length; i++) {
            table.append(rows[i]);
        }
    });

    function comparer(index) {
        return function (a, b) {
            var valA = $(a).children('td').eq(index).text();
            var valB = $(b).children('td').eq(index).text();
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
        };
    }


    // init filter inputs --------------------------------------------------------------------
    $(document).on("keyup", ".listFilterInput[data-filter-target]", function(){
        var $this = $(this);
        var val = $.trim($this.val());
        var filterTargetId = $this.attr('data-filter-target');
        var filterScope = filterTargetId && $this.closest('#' + filterTargetId);
        if (filterTargetId && !filterScope.length) {
            filterScope = $(document.getElementById(filterTargetId));
        }
        var items = $(">li, >tr, >tbody>tr", filterScope);

        if (val.length <= 0) {
            items.show();
            items.find('tr').find('td').removeClass('filter-matches');
            return;
        }

        // check if a jQuery item contains the input text, ignoring case and soft hyphens
        const containsInput = ($item, text) => $item.text().replace('­', '').toUpperCase().indexOf(text.toUpperCase()) !== -1;

        $.each(items, function () {
            var $item = $(this);
            if (filterScope.hasClass('listFilterBoxes')) {
                $item.toggle(containsInput($item, val));
            } else {
                var $allItemRows = $item.find('tr');
                var $filterItemRows = $allItemRows.not('.doNotFilter');
                if ($filterItemRows.length > 0) {
                    let itemContainsInput = -1;
                    $.each($filterItemRows, function () {
                        var $row = $(this);
                        var $filterText = $row.find('td').not('.doNotFilter');
                        if (containsInput($filterText, val)) {
                            $row.find('td').addClass('filter-matches');
                            itemContainsInput = 1;
                        } else {
                            $row.find('td').removeClass('filter-matches');
                        }
                    });
                    if (itemContainsInput === 1) {
                        $item.show();
                    } else {
                        $item.hide();
                    }
                } else if ($allItemRows.length > 0) {
                    $item.hide();
                } else {
                    $item.toggle(containsInput($item, val));
                }
            }
        });
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
    function initHierarchicalPermissions($table) {
        $table.find("tbody tr").each(function (index, tr) {
            const tagboxes = $(tr).find(".tagbox[data-action-name]").get().reverse();
            let hasActivePermission = false;
            for (let tagbox of tagboxes) {
                const $tagbox = $(tagbox);
                if ($tagbox.hasClass("active")) {
                    hasActivePermission = true;
                } else if (hasActivePermission) {
                    $tagbox.addClass("active-inherited");
                }
            }
        });
    }

    function appendPermissionSubjects($targetTable, $subjectList) {
        const $tbody = $("tbody", $targetTable);
        const $permissionTrProto = $("thead", $targetTable).attr("data-prototype");

        let existingPermissionsCount = $tbody.find("tr").length;
        $subjectList.find('input[type="checkbox"]:checked').each(function(index, element) {
            // see FOM/UserBundle/Resoruces/views/Permission/groups-and-users.html.twig
            const $checkbox = $(element);
            const subjectJson = $checkbox.val();
            const text = $checkbox.attr('data-label');
            const iconClass = $checkbox.attr('data-icon');

            const $newEl = $($permissionTrProto.replace(/__name__/g, existingPermissionsCount++));
            $newEl.find("i.userType").addClass(iconClass);
            $newEl.find('.-js-subject-label').text(text);
            $newEl.find('.-js-subject-json input').val(subjectJson);
            $tbody.prepend($newEl);
            // select first permission column per default
            $newEl.find('.tagbox').first().trigger('click');
        });

        // if table was previously empty, reveal it and hide placeholder text
        $targetTable.removeClass('hidden');
        $targetTable.closest('.permission-collection').find('.-js-table-empty').addClass('hidden');
    }

    $(document).on('click', '.permissionsTable tbody .tagbox[data-action-name]', function() {
        const $target = $(this);
        const $cb = $('input[type="checkbox"]', this);
        const $collection = $target.closest('.permission-collection');
        const isHierarchical = $collection.attr('data-hierarchical');

        if (isHierarchical) {
            const permType = $target.attr('data-action-name');
            const wasActive = $target.hasClass('active');
            let permTypeFound = wasActive;

            $target.closest('tr').find('.tagbox[data-action-name]').each(function(index, element) {
                const $element = $(element);
                const isCurrentPermType = $element.attr('data-action-name') === permType;

                $element.find('input[type="checkbox"]').prop('checked', isCurrentPermType && !wasActive);
                $element.toggleClass('active', isCurrentPermType && !wasActive);
                $element.toggleClass('active-inherited', !permTypeFound && !isCurrentPermType);
                if (!permTypeFound && !wasActive) permTypeFound = isCurrentPermType;
            });

        } else {
            const wasChecked = $cb.prop('checked');
            $cb.prop('checked', !wasChecked);
            $target.toggleClass('active', !wasChecked);
        }
    });

    $(document).on('click', '.permission-collection .-fn-add-permission[data-url]', function(event) {
        const $addButton = $(event.target).closest('.-fn-add-permission');
        const url = $addButton.attr('data-url');
        if (!url || !url.length) return false;

        const $targetTable = $addButton.closest('.permission-collection').find('table');
        const subjects = [];
        $targetTable.find('tbody tr').each((index, element) => subjects.push($(element).find('.-js-subject-json input').val()));

        const subjectURIComponent = encodeURIComponent(JSON.stringify(subjects));
        const fullUrl = url + (url.includes('?') ? '&' : '?') + 'subjects=' + subjectURIComponent;

        $.ajax({
            url: fullUrl,
        }).then(function(response) {
            var $modal = Mapbender.bootstrapModal(response, {
                title: Mapbender.trans('mb.manager.managerbundle.add_user_group'),
                buttons: [
                    {
                        label: Mapbender.trans('mb.actions.add'),
                        cssClass: 'btn btn-primary btn-sm',
                        callback: function() {
                            appendPermissionSubjects($targetTable, $('#listFilterPermissionSubjects', $modal));
                            $modal.modal('hide');
                        }
                    },
                    {
                        label: Mapbender.trans('mb.actions.cancel'),
                        cssClass: 'btn btn-light btn-sm popupClose'
                    }
                ]
            });
            $modal.modal('show');
        });

        return false;
    });

    // not for element security (there, the popup content is replaced with the check)
    $(".permissionsTable").on("click", '.-fn-delete', function(e) {
        const $row = $(e.target).closest('tr');
        const $table = $row.closest('table');
        const title = $row.find('.-js-subject-label').text();

        const translationKey = 'mb.manager.components.popup.delete_user_group.content';
        var content = '<div>' + Mapbender.trans(translationKey, {'subject': title}) + '</div>';
        var labels = {
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        };
        Mapbender.Manager.confirmDelete(null, null, labels, content).then(function() {
            $row.remove();
            if ($table.find('tbody tr').length === 0) {
                $table.closest('.permission-collection').find('.-js-table-empty').removeClass('hidden');
                $table.addClass('hidden');
            }
        });

    }).each(function (index, element) {
        const $table = $(element);
        if ($table.closest('.permission-collection').attr("data-hierarchical")) {
            initHierarchicalPermissions($table);
        }

        const popoverTriggerList = $table.find('[data-bs-toggle="popover"]');
        [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            placement: 'left',
            trigger: 'hover'
        }));
    });



    // Element security
    function initElementSecurity(response, url) {
        const $content = $(response);
        // submit back to same url (would be automatic outside of popup scope)
        $content.filter('form').attr('action', url);
        var $initialView = $(document.createElement('div')).addClass('contentItem').append($content);

        var $modal;
        var $permissionsTable;
        var isModified = false;
        var popupOptions = {
            title: Mapbender.trans('mb.actions.secureelement'),
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
                    label: Mapbender.trans('mb.actions.remove'),
                    cssClass: 'btn btn-danger btn-sm buttonRemove hidden',
                    callback: function(evt) {
                        var $button = $(evt.currentTarget);
                        $('.contentItem', $modal).not($initialView).remove();
                        $initialView.removeClass('hidden');
                        $button.data('target-row').remove();
                        $button.data('target-row', null);
                        if ($permissionsTable.find('tbody tr').length === 0) {
                            $permissionsTable.closest('.permission-collection').find('.-js-table-empty').removeClass('hidden');
                            $permissionsTable.addClass('hidden');
                        }
                        isModified = true;

                        $(".buttonAdd,.buttonRemove,.buttonBack", $modal).addClass('hidden');
                        $(".buttonOk,.buttonReset,.buttonCancel", $modal).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.back'),
                    cssClass: 'btn btn-light btn-sm buttonBack hidden pull-left',
                    callback: function() {
                        $('.contentItem', $modal).not($initialView).remove();
                        $initialView.removeClass('hidden');

                        $(".buttonAdd,.buttonBack,.buttonRemove", $modal).addClass('hidden');
                        $(".buttonOk", $modal).removeClass('hidden');
                        $(".buttonCancel", $modal).removeClass('hidden');
                        $('.buttonReset', $modal).toggleClass('hidden', !isModified);
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.add'),
                    cssClass: 'btn btn-primary btn-sm buttonAdd hidden',
                    callback: function() {
                        $(".contentItem:first", $modal).removeClass('hidden');
                        if ($(".contentItem", $modal).length > 1) {
                            appendPermissionSubjects($permissionsTable, $('#listFilterPermissionSubjects', $modal));
                            $(".contentItem:not(.contentItem:first)", $modal).remove();
                        }
                        isModified = true;
                        $(".buttonAdd,.buttonBack", $modal).addClass('hidden');
                        $(".buttonOk,.buttonReset,.buttonCancel", $modal).removeClass('hidden');
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.save'),
                    cssClass: 'btn btn-primary btn-sm buttonOk',
                    callback: function() {
                        $("form", $modal).submit();
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.cancel'),
                    cssClass: 'btn btn-light btn-sm buttonCancel popupClose'
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
        $permissionsTable.each(function(index, element) {
            if ($(element).closest('.permission-collection').attr("data-hierarchical")) {
                initHierarchicalPermissions($(element));
            }
        });

        $('.-fn-add-permission', $initialView).on('click', function(e) {
            var url = $(this).attr('data-url');
            $.ajax({
                url: url,
                type: "GET",
                success: function(data) {
                    $(".contentItem:first,.buttonOk,.buttonReset,.buttonCancel", $modal).addClass('hidden');
                    $(".buttonAdd,.buttonBack", $modal).removeClass('hidden');
                    addContent(data);
                }
            });
            // Suppress call to global handler
            return false;
        });

        $permissionsTable.on("click", 'tbody .-fn-delete', function() {
            const $row = $(this).closest('tr');
            const title = $row.find('.-js-subject-label').text();
            addContent(Mapbender.trans('mb.manager.components.popup.delete_user_group.content', {
                'subject': title
            }));
            $(".contentItem:first,.buttonOk,.buttonReset,.buttonCancel", $modal).addClass('hidden');
            $('.buttonRemove', $modal).data('target-row', $row);
            $(".buttonRemove,.buttonBack", $modal).removeClass('hidden');
        });
        $modal.modal('show');
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

    $('.elementsTable').on('click', '.duplicateElement[data-url]', function() {
        var $el = $(this);
        var content = $el.data('title');
        var $modal = Mapbender.bootstrapModal(content, {
            title: Mapbender.trans('mb.manager.components.popup.duplicate_element.title'),
            buttons: [
                {
                    label: Mapbender.trans('mb.actions.duplicate'),
                    cssClass: 'btn btn-primary btn-sm',
                    callback: function() {
                        $.ajax({
                            url: $el.data('url'),
                            type: 'POST',
                            data: {
                                token: $el.data('token')
                            },
                            success: function() {
                                window.location.reload();
                            },
                            error: function() {
                                Mapbender.error(Mapbender.trans('mb.application.save.failure.general'));
                                $modal.modal('hide');
                            }
                        });
                    }
                },
                {
                    label: Mapbender.trans('mb.actions.cancel'),
                    cssClass: 'btn btn-light btn-sm popupClose'
                }
            ]
        });
        $modal.modal('show');
        return false;
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

    $(document).on('keydown', '.clickable', function (event) {
        if (event.key === 'Enter') {
            $(this).click();
        }
    });
});

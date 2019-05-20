$(function() {
    var showInfoBox = function(e) {
        var me = $(this);
        if (e.currentTarget == e.target) {
            var infoBox = me.find(".infoMsgBox");
            if (infoBox.hasClass("hide")) {
                $(".infoMsgBox").addClass("hide");
                infoBox.removeClass("hide");
            } else {
                infoBox.addClass("hide");
            }
            e.stopPropagation();
            return false;
        }
    };

    function setRootState(groupId) {
        var root = $("#" + groupId);
        var column = $("#instanceTableCheckBody").find("[data-check-identifier=" + groupId + "]");
        var checkboxes = $('input[type="checkbox"]:not(:disabled)', column);
        var rowCount = checkboxes.length;
        var checkedCount = checkboxes.filter(':checked').length;

        if (rowCount === checkedCount) {
            root.removeClass("iconCheckbox iconCheckboxHalf");
            root.addClass("iconCheckboxActive");
        } else if (checkedCount === 0) {
            root.removeClass("iconCheckboxActive iconCheckboxHalf");
            root.addClass("iconCheckbox");
        } else {
            root.removeClass("iconCheckbox iconCheckboxActive");
            root.addClass("iconCheckboxHalf");
        }
    }
    // toggle all permissions
    var toggleAllStates = function() {
        var self = $(this);
        var groupId = self.attr("id");
        var $chkScope = $(".checkboxColumn[data-check-identifier=" + groupId + "]");
        var state = !self.hasClass("iconCheckboxActive");

        // change all tagboxes with the same permission type
        $chkScope.find('input[type="checkbox"]:not(:disabled)').each(function() {
            var $chk = $(this);
            $chk.prop('checked', state);
            initCheckbox.call($chk);
        });

        // change root permission state
        setRootState(groupId);
    };
    $("#instanceTableCheckHead .iconCheckbox").each(function() {
        setRootState($(this).attr("id"));
        $(this).bind("click", toggleAllStates);
    });

    $('#instanceTable tbody').on("change", '[data-check-identifier] input[type="checkbox"]', function() {
        var groupId = $(this).closest('[data-check-identifier]').attr('data-check-identifier');
        setRootState(groupId);
    });
    function resetLayerPriority() {
        $('tr:not(.dummy) .layer-priority input[type="hidden"]', $('.instanceTable tbody')).each(function(idx, item) {
            $(item).val(idx);
        });
    }
    resetLayerPriority();
    $('.instanceTable').each(function() {
        var children = [];
        $('tbody', this).sortable({
            cursor: 'move',
            axis: 'y',
            items: 'tr:not(.root):not(.dummy)',
            distance: 6,
            containment: 'parent',
            start: function(event, ui) {
                var subs = $('.instanceTable tbody tr[data-parent="' + $(ui.item).attr('data-id') + '"]');
                children = [];
                if (subs.length > 0) {
                    var nextAll = $(ui.item).nextAll('[data-id]');
                    for (var i = 0; i < nextAll.length; i++) {
                        var tmp = $(nextAll.get(i));
                        if (tmp.attr('data-parent') === $(ui.item).attr('data-parent')) {
                            break;
                        }
                        children.push(tmp.attr('id'));
                    }
                }
            },
            stop: function(event, ui) {
                var $dragItem = $(ui.item);
                var $prev = $dragItem.prev();
                var $next = $dragItem.next();
                var allowMove = $prev.length && $prev.attr("data-parent") === $dragItem.attr("data-parent");
                if (allowMove) {
                    allowMove = allowMove && !($next.length && $next.attr("data-parent") === $prev.attr("data-id"));
                } else {
                    allowMove = $next.length && $next.attr("data-parent") === $dragItem.attr("data-parent");
                }
                if (allowMove) {
                    if (children.length) {
                        var elm = $dragItem;
                        $.each(children, function(idx, item) {
                            var mel = $('#' + item).remove();
                            mel.insertAfter(elm);
                            elm = mel;
                        });
                    }
                    resetLayerPriority();
                    return true;
                } else {
                    return !!allowMove;
                }
            }
        });
    });

    $("#instanceTable").on("click", ".iconMore", showInfoBox);
});
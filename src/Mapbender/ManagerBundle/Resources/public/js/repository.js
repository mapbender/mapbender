$(function() {
    function setRootState(groupId) {
        var root = $("#" + groupId);
        if (!root.length) {
            root = $('#instanceTableCheckHead th[data-check-identifier="' + groupId + '"] .checkWrapper');
        }
        var column = $("#instanceTableCheckBody").find("[data-check-identifier=" + groupId + "]");
        var checkboxes = $('input[type="checkbox"]:not(:disabled)', column);
        var rowCount = checkboxes.length;
        var checkedCount = checkboxes.filter(':checked').length;

        if (rowCount === checkedCount) {
            root.removeClass("iconCheckbox iconCheckboxHalf");
            root.addClass("iconCheckboxActive");
            $('input[type="checkbox"]', root).prop('checked', true);
        } else if (checkedCount === 0) {
            root.removeClass("iconCheckboxActive iconCheckboxHalf");
            root.addClass("iconCheckbox");
            $('input[type="checkbox"]', root).prop('checked', false);
        } else {
            root.removeClass("iconCheckbox iconCheckboxActive");
            root.addClass("iconCheckboxHalf");
            $('input[type="checkbox"]', root).prop('checked', false);
        }
    }
    // toggle all permissions
    function toggleAllStates(groupId, state, $scope) {
        var $chkScope = $("tbody .checkboxColumn[data-check-identifier=" + groupId + "]", $scope);
        // change all tagboxes with the same permission type
        $chkScope.find('input[type="checkbox"]:not(:disabled)').each(function() {
            var $chk = $(this);
            $chk.prop('checked', state);
            initCheckbox.call($chk);
        });

        // change root permission state
        setRootState(groupId);
    }
    // old template
    $("#instanceTableCheckHead .iconCheckbox[id]").each(function() {
        var $this = $(this);
        var $table = $this.closest('table');
        var groupId = $this.attr('id');
        setRootState(groupId);
        $this.on('click', function() {
           var newState = !$(this).hasClass("iconCheckboxActive");
           toggleAllStates(groupId, newState, $table);
        });

    });
    // new template
    $("#instanceTableCheckHead .checkboxColumn[data-check-identifier]").each(function() {
        var $this = $(this);
        var $table = $this.closest('table');
        var groupId = $this.attr("data-check-identifier");
        setRootState(groupId);
        var $cb = $('input[type="checkbox"]', this);
        $cb.on('change', function() {
            var state = $(this).prop('checked');
            toggleAllStates(groupId, state, $table);
        });
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

    $("#instanceTable").on("click", ".iconMore", function(e) {
        var $target = $(e.target).filter('.iconMore');
        if (!$target.length) {
            return;
        }
        var $row = $target.closest('tr');
        var $table = $row.closest('table');
        var $targetBox = $('.infoMsgBox', $row);
        if ($targetBox.length) {
            $('.infoMsgBox', $table).not($targetBox).addClass('hidden');
        }
        $targetBox.toggleClass('hidden');
    });
});

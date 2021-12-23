$(function() {
    function getRootCheckbox($table, groupId) {
        var root = $('thead th[data-check-identifier="' + groupId + '"]', $table);
        return root.is('input[type="checkbox"]') && root || $('input[type="checkbox"]', root);
    }
    function setRootState($table, groupId) {
        var rootCb = getRootCheckbox($table, groupId);
        var column = $('tbody [data-check-identifier="' + groupId + '"]', $table);
        var checkboxes = $('input[type="checkbox"]:not(:disabled)', column);
        var rowCount = checkboxes.length;
        var checkedCount = checkboxes.filter(':checked').length;
        rootCb.prop('checked', rowCount && rowCount === checkedCount);
        rootCb.prop('indeterminate', rowCount && checkedCount && rowCount !== checkedCount);
    }
    // toggle all permissions
    function toggleAllStates(groupId, state, $scope) {
        var $chkScope = $("tbody .checkboxColumn[data-check-identifier=" + groupId + "]", $scope);
        // change all tagboxes with the same permission type
        $chkScope.find('input[type="checkbox"]:not(:disabled)').each(function() {
            var $chk = $(this);
            $chk.prop('checked', state);
        });

        // change root permission state
        setRootState($scope.closest('table'), groupId);
    }
    $(".instanceTable thead .checkboxColumn[data-check-identifier]").each(function() {
        var $this = $(this);
        var $table = $this.closest('table');
        var groupId = $this.attr("data-check-identifier");
        var rowCbs =  $('tbody [data-check-identifier="' + groupId + '"] input[type="checkbox"]:not(:disabled)', $table);
        if (!rowCbs.length) {
            getRootCheckbox($table, groupId).prop('checked', false).prop('disabled', true);
        }

        setRootState($table, groupId);
        var $cb = $('input[type="checkbox"]', this);
        $cb.on('change', function() {
            var state = $(this).prop('checked');
            toggleAllStates(groupId, state, $table);
        });
    });

    $('.instanceTable tbody').on("change", '[data-check-identifier] input[type="checkbox"]', function() {
        var $cb = $(this);
        var $table = $cb.closest('table');
        var groupId = $cb.closest('[data-check-identifier]').attr('data-check-identifier');
        setRootState($table, groupId);
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

    $(".instanceTable").on("click", ".-fn-toggle-layer-detail", function(e) {
        var $target = $(this);
        var $row = $target.closest('tr');
        var $table = $row.closest('table');
        var $targetBox = $('.infoMsgBox', $row);
        if ($targetBox.length) {
            $('.infoMsgBox', $table).not($targetBox).addClass('hidden');
        }
        $targetBox.toggleClass('hidden');
    });
});

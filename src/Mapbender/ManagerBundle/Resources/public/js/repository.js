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

    function setRootState(className) {
        var root = $("#" + className);
        var column = $("#instanceTableCheckBody").find("[data-check-identifier=" + className + "]");
        var rowCount = column.find(".checkWrapper:not(.checkboxDisabled)").length;
        var checkedCount = column.find(".iconCheckboxActive").length;

        root.removeClass("iconCheckboxActive").removeClass("iconCheckboxHalf");

        if (rowCount === checkedCount) {
            root.addClass("iconCheckboxActive");
        } else if (checkedCount === 0) {
            // do nothing!
        } else {
            root.addClass("iconCheckboxHalf");
        }
    }
    // toggle all permissions
    var toggleAllStates = function() {
        var self = $(this);
        var className = self.attr("id");
        var checkElements = $(".checkboxColumn[data-check-identifier=" + className + "]");
        var state = !self.hasClass("iconCheckboxActive");
        var me;

        // change all tagboxes with the same permission type
        checkElements.find(".checkbox:not(:disabled)").each(function(i, e) {
            me = $(e);
            me.get(0).checked = state;

            if (state) {
                me.parent().addClass("iconCheckboxActive");
            } else {
                me.parent().removeClass("iconCheckboxActive");
            }
        });

        // change root permission state
        setRootState(className);
    }
    // init permission root state
    var initRootState = function() {
        $(this).find(".iconCheckbox").each(function() {
            setRootState($(this).attr("id"));
            $(this).bind("click", toggleAllStates);
        });
    };
    $("#instanceTableCheckHead").one("load", initRootState).load();

    // toggle permission Event
    var toggleState = function() {
        setRootState($(this).parent().attr("data-check-identifier"));
    };
    function resetLayerPriority() {
        $('tr:not(.dummy) .layer-priority input[type="hidden"]', $('.instanceTable tbody')).each(function(idx, item) {
            $(item).val(idx).parents('tr:first').attr("data-priority", idx);
        });
    }
    resetLayerPriority();
    $('tr[data-type="root"], tr[data-type="node"]', $('.instanceTable tbody')).each(function() {
        var id = $(this).attr("data-id");
        var children = [];
        $('.instanceTable tbody').sortable({
            cursor: 'move',
            axis: 'y',
            items: 'tr:not(.root)',
            distance: 6,
            containment: 'parent',
            start: function(event, ui) {
                if ($(ui.item).hasClass('root') || $(ui.item).hasClass('dummy'))
                    return false;
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
                var item = $(ui.item);
                if (item.prev().length > 0 && $(item.prev().get(0)).attr("data-parent") === item.attr("data-parent")) {
                    if (item.next().length > 0 && $(item.next().get(0)).attr("data-parent") === $(item.prev().get(0)).attr("data-id")) {
                        return false;
                    }
                    if (children.length > 0) {
                        var elm = item;
                        $.each(children, function(idx, item) {
                            var mel = $('#' + item).remove();
                            mel.insertAfter(elm);
                            elm = mel;
                        });
                    }
                    resetLayerPriority();
                    return true;
                } else if (item.next().length > 0 && $(item.next().get(0)).attr("data-parent") === item.attr("data-parent")) {
                    if (children.length > 0) {
                        var elm = item;
                        $.each(children, function(idx, item) {
                            var mel = $('#' + item).remove();
                            mel.insertAfter(elm);
                            elm = mel;
                        });
                    }
                    resetLayerPriority();
                    return true;
                } else {
                    return false;
                }
            }
        });
    });
    $('.dimensionGroup').on("click", '.on-off', function(e) {
        var $this = $(e.target);
        if ($this.hasClass('active')) {
            $this.removeClass('active');
            $this.parent().find('#' + $this.attr('id') + '-content').addClass('hidden');
        } else {
            $this.addClass('active');
            $this.parent().find('#' + $this.attr('id') + '-content').removeClass('hidden');
        }
    });
    $('.dimensionGroup select').each(function() {
        if ($(this).attr('name').indexOf('[extentEdit]') > 0) {
            $(this).on('change', function(e) {
                var item = $(e.target);
                var extentId = item.attr('id').substr(0, item.attr('id').indexOf('extentEdit')) + 'extent';
                $('.dimensionGroup #' + extentId).val(item.val());
            });
        }
    });
    $("#instanceTable").on("click", ".iconMore", showInfoBox);
    $(document).on("click", "#instanceTable .checkWrapper", toggleState);
});
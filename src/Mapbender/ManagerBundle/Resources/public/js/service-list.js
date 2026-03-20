$(function() {
    $('#listFilterServices, .-fn-instance-list, .dropdown-menu').on('click', '.-fn-delete[data-url]', function(e) {
        e.preventDefault();
        var $el = $(this);
        var url = $el.attr('data-url');
        $.ajax(url, {
            method: 'GET'
        }).then(function(response) {
            var stringMap = {
                title: "mb.manager.components.popup.delete_element.title",
                confirm: "mb.actions.delete",
                cancel: "mb.actions.cancel"
            };
            Mapbender.Manager.confirmDelete($el, url, stringMap, response);
        });
        return false;
    });

    // Source type filter dropdown
    $('#filter-source-type').on('change', function() {
        var selectedType = $(this).val();
        var $items = $('#listFilterServices > li');
        if (!selectedType) {
            $items.show();
        } else {
            var types = selectedType.split(',');
            $items.each(function() {
                var $item = $(this);
                var itemType = $item.find('[data-source-type]').attr('data-source-type');
                $item.toggle(types.indexOf(itemType) !== -1);
            });
        }
        // re-apply text filter if present
        var textVal = $.trim($('.listFilterInput[data-filter-target="listFilterServices"]').val());
        if (textVal.length > 0) {
            $items.filter(':visible').each(function() {
                var $item = $(this);
                if ($item.text().toUpperCase().indexOf(textVal.toUpperCase()) === -1) {
                    $item.hide();
                }
            });
        }
    });

    // re-apply type filter after text filter keyup
    $(document).on('keyup', '.listFilterInput[data-filter-target="listFilterServices"]', function() {
        var selectedType = $('#filter-source-type').val();
        if (selectedType) {
            var types = selectedType.split(',');
            setTimeout(function() {
                $('#listFilterServices > li:visible').each(function() {
                    var $item = $(this);
                    var itemType = $item.find('[data-source-type]').attr('data-source-type');
                    if (types.indexOf(itemType) === -1) {
                        $item.hide();
                    }
                });
            }, 10);
        }
    });
});

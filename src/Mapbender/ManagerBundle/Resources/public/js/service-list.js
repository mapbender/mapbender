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

    function applyFilters() {
        var selectedType = $('#filter-source-type').val();
        var textVal = ($('.listFilterInput[data-filter-target="listFilterServices"]').val() || '').trim();
        var $items = $('#listFilterServices > li');

        $items.each(function() {
            var $item = $(this);
            var visible = true;

            if (selectedType) {
                var types = selectedType.split(',');
                var itemType = ($item.find('[data-source-type]').attr('data-source-type') || '').toLowerCase();
                if (types.indexOf(itemType) === -1) {
                    visible = false;
                }
            }

            if (visible && textVal.length > 0) {
                if ($item.text().toUpperCase().indexOf(textVal.toUpperCase()) === -1) {
                    visible = false;
                }
            }

            $item.toggle(visible);
        });
    }

    $('#filter-source-type').on('change', applyFilters);
    $(document).on('keyup', '.listFilterInput[data-filter-target="listFilterServices"]', applyFilters);
});

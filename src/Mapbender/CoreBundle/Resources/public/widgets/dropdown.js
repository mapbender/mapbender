/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
$(function () {
    function fixOptions(scope) {
        // Update (potentially runtime generated) dropdown markup,
        // replace correlating opt-... and item-... classes with
        // a 'data-value' attribute and a 'choice' class on the display item
        // matching requires an implicit hyphen, see https://api.jquery.com/attribute-contains-prefix-selector/
        $('select option[class|="opt"]', scope).each(function() {
            var $opt = $(this);
            var optClass = ($opt.attr('class').match(/opt-\d+/) || [])[0];

            if (optClass) {
                $opt.removeClass(optClass);
                var itemClass = optClass.replace('opt-', 'item-');
                var $displayItem = $('.' + itemClass, scope);
                $displayItem.attr('data-value', $opt.attr('value'));
                $displayItem.addClass('choice');
                $displayItem.removeClass(itemClass);
            }
        });
    }
    function updateValueDisplay(wrapper) {
        var $select = $('select', wrapper).first();
        var $valueDisplay = $('>.dropdownValue', wrapper);
        var $option = $('option:selected', $select).first();
        $valueDisplay.text($option.text());
    }
    function installFormEvents(form) {
        form.addEventListener('reset', function() {
            $('.dropdown > .dropdownValue', form).each(function() {
                var $wrapper = $(this).parent('.dropdown');
                // reset html select
                $('select', $wrapper).prop('selectedIndex',0);
                if ($('select', $wrapper).length) {
                    fixOptions($wrapper);
                    updateValueDisplay($wrapper);
                }
            });
        });
    }

    function initDropdown() {
        fixOptions(this);
        var $select = $('select', this);
        var $form = $select.closest('form');
        if ($form.length && !$form.data('mb-dropdown-events-installed')) {
            installFormEvents($form.get(0));
            $form.data('mb-dropdown-events-installed', true);
        }

        var dropdownList = $(".dropdownList", this);
        if (dropdownList.children().length === 0) {
            $('option', $select).each(function (i, e) {
                var node = $('<li>');
                node.addClass('choice');
                node.attr('data-value', $(e).attr('value'));
                node.text($(e).text());
                dropdownList.append(node);
            });
        }
        updateValueDisplay(this);
    }
    // init dropdown list --------------------------------------------------------------------

    function toggleList() {
        fixOptions(this);
        var me = $(this);
        var list = $('.dropdownList', this);
        var opts = $('.hiddenDropdown', this);
        if (list.css("display") === "block") {
            list.hide();
        } else {
            list.one('click', 'li.choice', function (event) {
                var $target = $(this);
                var val = $target.attr('data-value');
                event.stopPropagation();
                var opt = $('option[value="' + val.replace(/"/g, '\\"').replace(/\\/g, '\\\\') + '"]', opts);
                me.find(".dropdownValue").text(opt.text());
                opts.val(opt.val());
                opts.trigger('change');
                list.hide();
            });
            list.show();
        }

        $(document).one("click", function () {
            list.hide().find("li").off("click", 'li.choice');
        });
        return false;
    }
    $('.dropdown').each(function () {
        initDropdown.call(this);
    });
    $(document).on('change', '.dropdown > select.hiddenDropdown', function() {
        updateValueDisplay($(this).parent('.dropdown'));
    });
    window.initDropdown = initDropdown;
    $(document).on("click", ".dropdown", toggleList);
    $(document).on('mousewheel scroll DOMMouseScroll', '.dropdownList', function(e) {
        var delta = e.originalEvent.detail;
        var atTop = this.scrollTop === 0;
        var atBottom = this.scrollTop === this.scrollTopMax;

        if (!this.scrollTopMax) {
            atBottom = this.scrollHeight === this.clientHeight + this.scrollTop;
        }
        if (e.originalEvent.deltaY) {
            delta = e.originalEvent.deltaY;
        }
        if (e.originalEvent.wheelDelta) {
            delta = -e.originalEvent.wheelDelta;
        }
        if (atTop && delta < 0 || atBottom && delta > 0) {
            return false;
        }
        return undefined;
    });

});

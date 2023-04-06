/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
$(function () {
    function isMbDropdown(element) {
        var $element = $(element);
        if ($element.is('select')) {
            return $element.parent().is('.dropdown');
        } else {
            return $element.is('.dropdown') && $('> select', $element).length;
        }
    }
    function fixOptions(scope) {
        var $selects = $('select', scope).filter(function() {
            return isMbDropdown(this);
        });
        // Update (potentially runtime generated) dropdown markup,
        // replace correlating opt-... and item-... classes with
        // a 'data-value' attribute and a 'choice' class on the display item
        // matching requires an implicit hyphen, see https://api.jquery.com/attribute-contains-prefix-selector/
        $('option[class|="opt"]', $selects).each(function() {
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
        const $wrapper = $(wrapper);
        var $select = $('select', $wrapper).first();
        var $valueDisplay = $('>.dropdownValue', $wrapper);
        if ($valueDisplay.hasClass('hide-value')) return;
        var $option = $('option:selected', $select).first();
        let text = $option.html();
        if ($wrapper.attr('data-html')) {
            const parser = (new DOMParser()).parseFromString(text, 'text/html');
            text = parser.documentElement.textContent;
        }
        if (text || !$wrapper.attr('data-prevent-empty')) {
            $valueDisplay.html(text);
        }
    }
    function installFormEvents(form) {
        var handler = function() {
            $('.dropdown > .dropdownValue', form).each(function() {
                var $wrapper = $(this).parent('.dropdown');
                if ($('select', $wrapper).length) {
                    fixOptions($wrapper);
                    updateValueDisplay($wrapper);
                }
            });
        };
        form.addEventListener('reset', function() {
            // defer execution until after reset event has executed and values are restored
            // (select values are still pre-reset when event is first received)
            window.setTimeout(handler, 0);
        });
    }

    function initDropdown() {
        if (!isMbDropdown(this)) {
            console.warn("Ignoring not-mapbender-dropdown", this);
            return;
        }

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
                const value = $(e).attr('value');
                node.addClass('choice');
                if ($select.val() === value) node.addClass('choice-selected');
                node.attr('data-value', value);
                node.text($(e).text());
                dropdownList.append(node);
            });
        }
        updateValueDisplay(this);
    }
    // init dropdown list --------------------------------------------------------------------

    function toggleList() {
        if (isMbDropdown(this)) {
            fixOptions(this);
            var $list = $('.dropdownList', this);
            $list.toggle();
            if ($list.is(':visible')) {
                $(document).one("click", function (evt) {
                    // List may have already been hidden by click on choice
                    if ($list.is(':visible')) {
                        evt.stopImmediatePropagation();
                        $list.hide();
                        return false;
                    }
                });
            }
            return false;
        }
    }
    function handleChoiceClick() {
        var $choice = $(this);
        var $list = $choice.closest('.dropdownList');
        var $dropdown = $list.closest('.dropdown');
        var $select = $('>select', $dropdown);
        var val = $choice.attr('data-value');
        var opt = $('option[value="' + val.replace(/"/g, '\\"').replace(/\\/g, '\\\\') + '"]', $select);
        var $valueDisplay = $('>.dropdownValue', $dropdown);
        if (!$valueDisplay.hasClass('hide-value')) $valueDisplay.html(opt.html());
        $select.val(opt.val());
        $select.trigger('change');
        $list.hide();
        $list.find('.choice').removeClass('choice-selected');
        $choice.addClass('choice-selected');
        return false;
    }
    $('.dropdown').each(function () {
        initDropdown.call(this);
    });
    $(document).on('change dropdown.changevisual', '.dropdown > select', function() {
        updateValueDisplay($(this).parent('.dropdown'));
    });
    window.initDropdown = initDropdown;
    $(document).on("click", ".dropdown", toggleList);
    $(document).on('click', '.dropdown > .dropdownList .choice', handleChoiceClick);
});

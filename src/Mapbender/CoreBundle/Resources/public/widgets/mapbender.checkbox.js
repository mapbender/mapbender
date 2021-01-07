(function($) {
    var iconOnDefault = "iconCheckboxActive";
    var iconOffDefault = "iconCheckbox";

    /**
     * Replacement for fom/src/FOM/CoreBundle/Resources/public/js/widgets/checkbox.js:initCheckbox.
     * Forwards checkbox state <=> display BOTH WAYS.
     * * display wrapper reacts to checkbox change event and updates itself visually
     * * click on wrapper triggers click on checkbox (which the browser converts into a change => automatic visual update)
     *
     * Changes in 'disabled' proper require manual rerendering. Just "initialize" the mbCheckbox again on a selector
     * matching the updated checkboxes.
     *
     * Usage (init / manual update):
     * $('input[type="checkbox"]', myScope).mbCheckbox()
     */
    function propagateToWrapper() {
        var $cb = $(this);
        var data = $cb.data('mbCheckbox');
        var $wrapper = $cb.parents('.checkWrapper');
        $wrapper.toggleClass('checkboxDisabled', $cb.prop('disabled'));
        if ($cb.prop('checked')) {
            $wrapper.addClass(data.iconOn);
            $wrapper.removeClass([data.iconOff, iconOffDefault].join(' '));
        } else {
            $wrapper.addClass(data.iconOff);
            $wrapper.removeClass([data.iconOn, iconOnDefault].join(' '));
        }
    }
    $.fn.mbCheckbox = function() {
        this.filter('.checkWrapper input[type="checkbox"]').each(function() {
            var $this = $(this);
            // Skip already initialized nodes, avoids binding events more than once
            if (!$this.data('mbCheckbox')) {
                var $wrapper = $this.parent(".checkWrapper");
                var iconOnAttrib = $wrapper.attr('data-icon-on');
                var iconOffAttrib = $wrapper.attr('data-icon-off');
                var iconOn = (typeof iconOnAttrib !== 'undefined') ? iconOnAttrib : iconOnDefault;
                var iconOff = (typeof iconOffAttrib !== 'undefined') ? iconOffAttrib : iconOffDefault;
                $this.data('mbCheckbox', {
                    initialized: true,
                    iconOn: iconOn,
                    iconOff: iconOff
                });
                $this.on('change', function() {
                    propagateToWrapper.call(this);
                });
                $this.closest('.checkWrapper').on('click', function(e) {
                    $('input[type="checkbox"]', this).trigger('click');
                    // prevent bubbling to globally document-bound FOM initCheckbox binding
                    e.stopPropagation();
                });
            }
            // Always rerender. This allows calling $(selector).mbCheckbox again to visually update for prop('disabled').
            propagateToWrapper.call(this);
        });
        return this;
    }
})(jQuery);

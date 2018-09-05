(function($) {
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
    $.fn.mbCheckbox = function() {
        var propagateToWrapper = function propagateToWrapper() {
            var $cb = $(this);
            var $wrapper = $cb.parents('.checkWrapper');
            if ($cb.is(":disabled")){
                $wrapper.addClass("checkboxDisabled");
            } else {
                $wrapper.removeClass("checkboxDisabled");
            }
            if ($cb.is(":checked")){
                $wrapper.addClass("iconCheckboxActive");
            } else {
                $wrapper.removeClass("iconCheckboxActive");
            }
        };
        this.filter('.checkWrapper input[type="checkbox"]').each(function() {
            var $this = $(this);
            // Skip already initialized nodes, avoids binding events more than once
            if (!$this.data('mbCheckbox')) {
                $this.data('mbCheckbox', {
                    initialized: true,
                    rerender: propagateToWrapper.bind(this)
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

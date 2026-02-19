class MapbenderElement {

    constructor(configuration, $element) {
        this.options = configuration || {};
        this.$element = $element;
        this.$element.data(this.constructor.name, this);
        this.popup = null;
    }

    getPopupOptions() {
        return {
            title: this.$element.attr('data-title'),
            draggable: true,
            resizable: true,
            modal: false,
            closeOnESC: false,
            detachOnClose: false,
            content: this.$element,
            width: 450,
            height: 400,
            scrollable: false,
            buttons: {}
        };
    }

    activateByButton(callback, mbButton) {
        this.callback = callback ? callback : null;
        let $mbButton = mbButton;
        if (!this.popup || !this.popup.$element) {
            const options = this.getPopupOptions();
            if($mbButton) {
                options.icon = $mbButton.find('i').attr('class') || $mbButton.find('span.mb-glyphicon').attr('class') || '';
            }
            this.popup = new Mapbender.Popup(options);
            this.popup.$element.on('close', () => this.closeByButton());
        }
        this.popup.$element.show();
        this.popup.focus();
    }

    closeByButton() {
        if (this.popup && this.popup.$element) {
            this.popup.$element.hide();
        }
        if (this.callback) {
            this.callback.call();
            this.callback = null;
        }
    }

    /**
     * Called when a widget is becoming visible through user action.
     * This is (currently only) used by sidepane machinery.
     * This is never called before the widget has sent its 'ready' event.
     * This SHOULD NOT open a popup dialog (though opening a popup dialog MAY implicitly also
     * call this method, if it performs important state changes).
     * @see element-sidepane.js
     *
     * This method doesn't receive any arguments and doesn't need to return anything.
     */
    reveal() {
        // nothing to do in base implementation
    }

    /**
     * Called when a widget is getting hidden through user action.
     * This is (currently only) used by sidepane machinery.
     * NOTE: This method MAY be called even if the widget never sent a 'ready' event.
     * Overridden versions MAY close any popup dialogs owned by the widget, though elements in the
     * side pane should usually never reside inside a popup themselves.
     * @see element-sidepane.js
     *
     * This method doesn't receive any arguments and doesn't need to return anything.
     */
    hide() {
        // nothing to do in base implementation
    }

    /**
     * Checks if element should open a popup immediately on application
     * initialization.
     *
     * Returns true only if all of the following:
     * 1) Widget configuration option "autoOpen" is set to true
     * 2) Containing region is appropriate (see checkDialogMode)
     * 3) Responsive element controls allow the element to be shown
     *
     * @param {jQuery|HTMLElement} [element]
     * @param {Object} [options]
     * @returns boolean
     */
    checkAutoOpen(element, options) {
        const options_ = options || this.options;
        return options_.autoOpen && this.checkDialogMode(element) && this.checkResponsiveVisibility(element);
    }

    /**
     * Checks the markup region containing the element for reasonable
     * dialog mode behaviour.
     * I.e. returns true if element is placed in "content" region
     * in a fullscreen template; returns false if element is placed
     * in a sidepane or mobile pane.
     *
     * @param {jQuery|HTMLElement} [element]
     * @returns boolean
     */
    checkDialogMode(element) {
        return Mapbender.ElementUtil.checkDialogMode(element || this.$element);
    }

    /**
     * @param {jQuery|HTMLElement} [element]
     * @returns boolean
     */
    checkResponsiveVisibility(element) {
        return Mapbender.ElementUtil.checkResponsiveVisibility(element || this.$element);
    }

    notifyWidgetDeactivated(){
        this.$element.trigger('mapbender.elementdeactivated', {
            widget: this,
            sender: this,
            active: false
        });
    }

    notifyWidgetActivated(){
        this.$element.trigger('mapbender.elementactivated', {
            widget: this,
            sender: this,
            active: true
        });
    }
}

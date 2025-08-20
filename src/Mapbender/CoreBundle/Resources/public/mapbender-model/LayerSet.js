(function () {
    /**
     * The most basic LayerGroup definition that is shown in the layer tree but does not display anything itself
     */
    Mapbender.Layerset = class Layerset extends Mapbender.LayerGroup {
        constructor(title, id, selected) {
            super(title, null);
            /**
             * @type {string|number}
             */
            this.id = id;
            /**
             * @type Boolean
             */
            this.selected = selected;
        }

        getId() {
            return this.id;
        }

        getSelected() {
            return this.selected;
        }

        setSelected(state) {
            this.selected = !!state;
        }

        getSettings() {
            return {
                selected: this.getSelected()
            };
        }

        /**
         * Changes all given layer settings
         * @see getSettings
         * @param {{selected?: Boolean}} settings
         * @returns {boolean} true if at least one attribute has been changed
         */
        applySettings(settings) {
            let dirty = false;
            if ("selected" in settings) {
                dirty = settings.selected !== this.selected;
                this.setSelected(settings.selected);
            }
            return dirty;
        }
    }
}());


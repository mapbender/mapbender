(function () {
    Mapbender.LayerGroup = class LayerGroup {
        constructor(title, parent) {
            /**
             * @type string
             * @private
             */
            this.title_ = title;
            /**
             * @type {null|Mapbender.LayerGroup}
             */
            this.parent = parent || null;
            /**
             *
             * @type {Mapbender.LayerGroup[]}
             */
            this.children = [];
            /**
             * @type {Mapbender.LayerGroup[]}
             */
            this.siblings = [this];
        }

        getTitle() {
            return this.title_;
        }

        /**
         * returnes whether this layergroup and all its parents are selected
         * @see getSelected
         * @returns {Boolean}
         */
        getActive() {
            let active = this.getSelected();
            let parent = this.parent;
            while (parent && active) {
                active = active && parent.getSelected();
                parent = parent.parent;
            }
            return active;
        }

        /**
         * returns whether this layer is currently selected.
         * @see getActive
         * @return Boolean
         * @abstract
         */
        getSelected() {
            throw new Error("Invoked abstract LayerGroup.getSelected");
        }

        getParent() {
            return this.parent;
        }

        /**
         * removes the given child layer from this LayerGroup
         * @param {Mapbender.LayerGroup} child
         */
        removeChild(child) {
            [this.children, this.siblings].forEach(function (list) {
                const index = list.indexOf(child);
                if (-1 !== index) {
                    list.splice(index, 1);
                }
            });
        }
    }
}());

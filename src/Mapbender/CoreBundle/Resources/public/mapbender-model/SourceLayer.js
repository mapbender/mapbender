(function () {
    /**
     * @abstract
     */
    Mapbender.SourceLayer = class SourceLayer extends Mapbender.LayerGroup {
        constructor(definition, source, parent) {
            super(((definition || {}).options || {}).title || '', parent);
            this.options = definition.options || {};
            this.options.treeOptions = this.options.treeOptions || {
                selected: true,
                info: false,
                toggle: true,
                allow: {selected: true, info: false, toggle: true,}
            };

            this.state = definition.state || {
                info: null,
                outOfBounds: false,
                outOfScale: false,
                visibility: true,
                unsupportedProjection: false,
            };

            this.source = source;
            var childDefs = definition.children || [];
            var i, child, childDef;
            for (i = 0; i < childDefs.length; ++i) {
                childDef = childDefs[i];
                child = Mapbender.SourceLayer.factory(childDef, source, this);
                child.siblings = this.children;
                this.children.push(child);
            }
            this.siblings = [this];
        }

        static factory(definition, source, parent) {
            let typeClass = SourceLayer.typeMap[source.type];
            if (!typeClass) {
                typeClass = Mapbender.SourceLayer;
            }
            return new typeClass(definition, source, parent);
        }

        /**
         * is this layer selected in the layertree
         * Caution: This does not mean it's visible, parent layers might be unselected
         * @returns {boolean}
         */
        getSelected() {
            return this.options.treeOptions.selected;
        }

        setSelected(state) {
            this.options.treeOptions.selected = !!state;
        }

        getId() {
            return this.options.id;
        }

        getName() {
            return this.options.name;
        }

        /**
         * Should the layer be displayed at this scale level?
         * @param {number} scale
         * @returns {boolean}
         */
        isInScale(scale) {
            // NOTE: undefined / "open" limits are null, but it's safe to treat zero and null
            //       equivalently
            const min = this.options.minScale;
            const max = this.options.maxScale;
            if (min && min > scale) {
                return false;
            } else {
                return !(max && max < scale);
            }
        }

        /**
         * Does the layer has contents in this extent?
         * @param {number[]} extent
         * @param {string} srsName
         * @returns {boolean}
         */
        intersectsExtent(extent, srsName) {
            return true;
        }

        /**
         * need custom toJSON for getMapState call
         */
        toJSON() {
            // Skip the circular-ref inducing properties 'siblings', 'parent' and 'source'
            const r = {
                options: this.options,
                state: this.state
            };
            if (this.children && this.children.length) {
                r.children = this.children;
            }
            return r;
        }

        /**
         * removes this layer from the source tree
         * @returns {string|null} the if of the removed layer or null of the layer had no parent
         */
        remove() {
            const index = this.siblings.indexOf(this);
            if (index === -1) {
                return null;
            }

            this.siblings.splice(index, 1);
            if (!this.siblings.length && this.parent && this.parent.remove) {
                return this.parent.remove();
            }

            return this.options.id;
        }

        /**
         * @param {Mapbender.SourceLayer} child
         */
        addChild(child) {
            this.children.push(child);
            this.children.forEach((child) => child.siblings = this.children);
            Mapbender.Model.updateSource(this.source);
        }

        /**
         * @param {Mapbender.SourceLayer[]} children
         */
        addChildren(children) {
            for (const child of children) {
                this.children.push(child);
                this.children.forEach((child) => child.siblings = this.children);
            }
            Mapbender.Model.updateSource(this.source);
        }

        /**
         * @param {string} projCode
         * @param {boolean} inheritFromParent
         * @returns {number[]|boolean} false if bounds could not be calculated
         */
        getBounds(projCode, inheritFromParent) {
            var bboxMap = this.options.bbox;
            var srsOrder = [projCode].concat(Object.keys(bboxMap));
            for (var i = 0; i < srsOrder.length; ++i) {
                var srsName = srsOrder[i];
                var bboxArray = bboxMap[srsName];
                if (bboxArray) {
                    var bounds = this.source._bboxArrayToBounds(bboxArray, srsName);
                    return Mapbender.mapEngine.transformBounds(bounds, srsName, projCode);
                }
            }
            var inheritParent_ = inheritFromParent || (typeof inheritFromParent === 'undefined');
            if (inheritParent_ && this.parent) {
                return this.parent.getBounds(projCode, true);
            }
            return null;
        }

        /**
         * Returns the legend for this layer. The legend can be either an external
         * url (e.g. for WMS services) or a style definition that is rendered on a canvas
         *
         * @param {boolean} forPrint true if the legend is exported for print (false if it's for display)
         * @return {null|{type: 'url', url: string, topLevel: boolean, isDynamic: boolean}|LegendDefinition}
         */
        getLegend(forPrint) {
            return null;
        }

        /**
         * is this layer restricted to spatial bbox?
         * @returns {boolean}
         */
        hasBounds() {
            var layer = this;
            do {
                if (Object.keys(layer.options.bbox).length) {
                    return true;
                }
                layer = layer.parent;
            } while (layer);
            return false;
        }

        /**
         * Can the layer be displayed in the given projection?
         * @param {string} srsName in the format 'EPSG:xxxx'
         * @returns {boolean}
         */
        supportsProjection(srsName) {
            return true;
        }

        /**
         * Returns a list of menu options supported by this layer.
         * Core mapbender menu options:
         * - layerremove: Deletes this layer
         * - metadata: Opens metadata in a new window. options.medataUrl should be defined
         * - opacity: Opacity slider between 0 and 1. See {Mapbender.Source.setOpacity}
         * - dimension: selection slider for dimensions like e.g. time
         * - zoomtolayer: Changes the map's view to fit the layer
         * @returns {string[]}
         */
        getSupportedMenuOptions() {
            const supported = ['layerremove'];
            if (this.options.metadataUrl) {
                supported.push('metadata');
            }
            // opacity + dimension are only available on root layer
            if (!this.getParent()) {
                supported.push('opacity');
                if ((this.source.options.dimensions || []).length) {
                    supported.push('dimension');
                }
            }
            if (this.hasBounds()) {
                supported.push('zoomtolayer');
            }
            if (this.options.availableStyles && this.options.availableStyles.length > 1 && !this.children.length) {
                supported.push('select_style');
            }
            return supported;
        }
    }

    Mapbender.SourceLayer.typeMap = {};
}());

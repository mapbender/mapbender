(function () {
    class MbButton extends MapbenderElement {

        active = false;
        $toolBarItem = null;

        constructor(configuration, $element) {
            super(configuration, $element);
            this.$toolBarItem = $(this.$element).closest('.toolBarItem');
            $(this.$element)
                .on('click', $.proxy(this._onClick, this))
                .on('mbButtonDeactivate', $.proxy(this.deactivate, this));
            // child widget may have initialized highlight state to a non-default
            this._setActive(this.isActive());
        }

        /**
         * Toggles the button state active / inactive.
         * Deactivates same-group siblings, if any, when activated.
         * Updates own visual highlighting state.
         * @private
         */
        _onClick() {
            if (this.isActive()) {
                this.deactivate();
            } else {
                // If we're part of a group, deactivate all other buttons in the same group
                // Do this BEFORE updating
                if (this.options.group) {
                    const $others = $('.mb-button[data-group="' + this.options.group + '"]')
                        .not(this);
                    $others.each(() => {
                        try {
                            $(this).trigger('mbButtonDeactivate');
                        } catch (e) {
                            console.error('Suppressing error from sibling button deactivate handler', e);
                        }
                    });
                }
                this.activate();
            }
        }

        /**
         * Turns on highlight, remembers active state for next click.
         * Override this if you need more stuff to happen
         */
        activate() {
            this._setActive(true);
        }

        /**
         * Turns off highlight, remembers inactive state for next click.
         * Override this if you need more stuff to happen
         */
        deactivate() {
            this._setActive(false);
        }

        /**
         * Alias of deactivate in base implementation. Commonly used as an on-close callback for popups,
         * invoked when they close, thus informing the button to clear its highlight, and that the next
         * click should be another activation.
         *
         * For this reason, you should not override this to trigger external cleanup logic. Override
         * deactivate instead.
         */
        reset() {
            this._setActive(false);
        }

        /**
         * Stores a new .active value and updates the highlighting with no further side effects
         * (no events etc).
         *
         * @param isActive
         * @private
         */
        _setActive(isActive) {
            this.active = !!isActive;
            this.$toolBarItem.toggleClass('toolBarItemActive', this.active);
        }

        /**
         * Trivial base implementation. Override this if you're not really sure ahead of time
         * what the button state is. Evaluated in click event handler.
         *
         * @return {boolean}
         */
        isActive() {
            return this.active;
        }
    }

    class MbControlButton extends MbButton {

        // null = not yet initialised
        // false = no target configured or target not found
        targetWidget = null;
        // when target is found, allow one retry, it might be possible that, depending on order,
        // the button widget is created before the target widget
        allowRetryFindingTarget = true;
        actionMethods = null;

        constructor(configuration, $element) {
            super(configuration, $element);
            if (this.options.click) {
                // this widget instance is superfluous, we rendered a link
                // we can't really deactivate mapbender.initElement machinery
                // so we still need to load the JS asset, and we still end up right here
                return;
            }
            const self = this;

            if (this.options.target) {
                $(document).on('mapbender.setupfinished', () => {
                    self._initializeHighlightState();
                });
                $(document).on('mapbender.elementactivated mapbender.elementdeactivated', (e, data) => {
                    const widgetId = data.widget && data.widget.element && parseInt(data.widget.element.attr('id'));
                    self._initializeTarget();
                    if (data.sender !== self && widgetId === self.options.target) {
                        // Our target element has been activated or deactivated, but not by us
                        // Remember new target state and update our own highlighting
                        self._setActive(data.active);
                    }
                });
            }

            // Amenity for special snowflake mobile.js, which expects to see us under
            // the 'mapbenderMbButton' data key
            this.$element.data('mapbenderMbButton', this);
        }

        /**
         *
         * @param {*} object
         * @param {string[]} names
         * @return {Array<function>}
         * @private
         */
        _extractCallableMethods(object, names) {
            return names.map((name) => {
                const method = name && object[name];
                return typeof method === 'function' ? method : null;
            }).filter((x) => {
                // throw out anything emptyish (including the nulls just produced)
                return !!x;
            });
        }

        /**
         *
         * @param targetWidget
         * @return {{activate: function|null, deactivate: function|null}}
         * @private
         */
        _extractActionMethods(targetWidget) {
            let methodPair = {
                activate: null,
                deactivate: null
            };
            const activateCandidateNames = [this.options.action, 'defaultAction', 'open', 'activate'];
            const deactivateCandidateNames = [this.options.deactivate, 'close', 'deactivate'];
            const activateCandidates = this._extractCallableMethods(
                targetWidget, activateCandidateNames);
            const deactivateCandidates = this._extractCallableMethods(
                targetWidget, deactivateCandidateNames);
            if (activateCandidates.length) {
                methodPair.activate = activateCandidates[0]
                    .bind(targetWidget, this.reset.bind(this));
            } else {
                console.error('Target widget', targetWidget,
                    'does not seem to have any potential activation method.',
                    'Tried: ', activateCandidateNames);
            }
            if (deactivateCandidates.length) {
                methodPair.deactivate = deactivateCandidates[0]
                    .bind(targetWidget, this.reset.bind(this));
            } else {
                console.error('Target widget', targetWidget,
                    'does not seem to have any potential deactivation method.',
                    'Tried: ', deactivateCandidateNames);
            }
            return methodPair;
        }

        /**
         * @returns {null|object} the target widget object (NOT the DOM node; NOT a jQuery selection)
         * @private
         */
        _initializeTarget() {
            // Initialize only once, remember the result forever.
            // This makes elements work that move around in / completely out of the DOM, either
            // by themselves, or because they let certain popups mangle their DOM nodes.
            if (this.targetWidget === null && this.options.target) {
                const targetConf = Mapbender.configuration.elements[this.options.target];
                if (!targetConf || !targetConf.init) {
                    console.error('Button target element not found in element configuration', targetConf, this.options);
                    this.targetWidget = false;
                    return null;
                }
                const targetInit = targetConf.init;
                const $target = $('#' + this.options.target);
                const nameParts = targetInit.split('.');
                if (nameParts.length === 1) {
                    // This is a BC construct currently without known or conceivable use cases
                    this.targetWidget = $target[nameParts];
                } else {
                    const namespace = nameParts[0];
                    let innerName = nameParts[1];
                    // widget data ends up in a key composed of
                    // namespace, no dot, innerName with upper-cased first letter
                    const dataKey = [namespace, innerName.charAt(0).toUpperCase(), innerName.slice(1)].join('');
                    this.targetWidget = $target.data(dataKey);
                    if (dataKey === 'mapbenderMbLegend') {
                        this.targetWidget = new Mapbender.Element.MbLegend(targetConf.configuration, $target);
                    }
                    if (dataKey === 'mapbenderMbPOI') {
                        this.targetWidget = new Mapbender.Element.MbPoi(targetConf.configuration, $target);
                    }
                }
                if (!this.targetWidget) {
                    if (this.allowRetryFindingTarget) {
                        this.targetWidget = null;
                        this.allowRetryFindingTarget = false;
                    } else {
                        console.warn('Could not identify target element', this.options.target, targetInit);
                        // Avoid attempting this again
                        this.targetWidget = false;
                    }
                }
            }

            return this.targetWidget || null;
        }

        _initializeActionMethods() {
            if (this.actionMethods === null) {
                if (this._initializeTarget()) {
                    this.actionMethods = this._extractActionMethods(this.targetWidget);
                } else {
                    this.actionMethods = {};
                }
            }
        }

        _initializeHighlightState() {
            // skip logic if already active
            if (this.active) {
                return;
            }
            if (this._initializeTarget() && this.targetWidget.options) {
                const targetOptions = this.targetWidget.options;
                let state = targetOptions.autoActivate   // FeatureInfo style
                    || targetOptions.autoStart     // GpsPosition style
                    || targetOptions.autoOpen      // Copyright / Legend / Layertree / WmsLoader style
                    || targetOptions.auto_activate // Sketch / Redlining style
                ;
                if (state) {
                    const isDialog = this.targetWidget.element.closest('.contentPane').length
                        || this.targetWidget.element.closest('.popup').length
                        || this.targetWidget.element.closest('.mobilePane').length;
                    state = state && isDialog;
                }
                this._setActive(!!state);
            }
        }

        /**
         * Calls 'activate' method on target if defined, and if in group, sets a visual highlight
         */
        activate() {
            // defensive activation to prevent redundant state transitions
            if (this.active) {
                return;
            }
            this._initializeActionMethods();
            if (this.actionMethods.activate) {
                (this.actionMethods.activate)();
                this.notifyActivation_(this.targetWidget, true);
            }
            super.activate();
        }

        /**
         * Clears visual highlighting, marks inactive state and
         * calls 'deactivate' method on target (if defined)
         */
        deactivate() {
            if (!this.active) {
                // defensive deactivation to prevent unneeded state transitions
                return;
            }
            this._initializeActionMethods();
            if (this.actionMethods.deactivate) {
                (this.actionMethods.deactivate)();
            }
            super.deactivate();
        }

        reset() {
            const targetWidget = this._initializeTarget();
            super.reset();
            if (targetWidget) {
                this.notifyActivation_(targetWidget, false);
            }
        }

        notifyActivation_(targetWidget, state) {
            const name = state ? 'mapbender.elementactivated' : 'mapbender.elementdeactivated';
            $(document).trigger(name, {
                widget: targetWidget,
                sender: this,
                active: !!state
            });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbButton = MbButton;
    window.Mapbender.Element.MbControlButton = MbControlButton;
})();

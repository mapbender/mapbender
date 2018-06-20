window.Mapbender = Mapbender || {};
window.Mapbender.Model = Mapbender.Model || {};
window.Mapbender.Model.SourceLayerState = (function() {
    'use strict';

    /**
     * Instantiate a layer state
     *
     * @param {bool} active
     * @param {bool} queryActive
     * @constructor
     */
    function SourceLayerState(active, queryActive) {
        this.active = !!active;
        this.queryActive = !!queryActive;
    }

    /**
     * Compare this state to given other state.
     *
     * @param {SourceLayerState} other
     * @returns {boolean} true if any attribute differs
     */
    SourceLayerState.prototype.compare = function compare(other) {
        var equal = 1;
        equal &= other.active === this.active;
        equal &= other.queryActive === this.active;
        // cast back to boolean
        return !!equal;
    };

    return SourceLayerState;
})();

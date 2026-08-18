window.Mapbender.SidePaneUnformatted = class SidepaneUnformatted extends Mapbender.SidePaneHandler {

    getType() {
        return 'unformatted';
    }

    setup() {
        //check if all items are invisible and hide/show sidepane accordingly
        const $elements = $(node).find('.mb-element');
        window.addEventListener('resize', () => {
            this.sidePane._updateResponsiveSidepaneVisibility($elements);
        });
        this.sidePane._updateResponsiveSidepaneVisibility($elements);
    }

}

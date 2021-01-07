window.Mapbender = Mapbender || {};
window.Mapbender.Model = Mapbender.Model || {};
/**
 *
 * @param $markup jQuery DoucmentFragment
 * @param model
 * @constructor
 */
window.Mapbender.Model.MapPopup = function($markup, model) {
    if(!$markup) {
        $markup = this.createPopupMarkup()
    }
    this.model = model;

    $markup.appendTo('#templateWrapper');

    this.$markup = $markup;

    this.overlay = new ol.Overlay({
        element : $markup[0],
        autoPan:          true,
        autoPanAnimation: {
            duration: 250
        }
    });
    this.model.map.addOverlay(this.overlay);
    window.$markup = $markup;
    this.$markup.find('ol-popup-closer').onclick = function(event) {
        this.overlay.setPosition(undefined);
        event.target.blur();
        return false;
    }.bind(this);




};

Mapbender.Model.MapPopup.prototype.createPopupMarkup = function createPopupMarkup() {
    var $popupContainer = $('<div />').addClass('ol-popup');
    var $closeButton = $('<a />').attr('href', 'javascript:void(0)').addClass('ol-popup-closer');
    var $popupContent = $('<div />').addClass('popup-content');
    return $popupContainer.append($closeButton).append($popupContent);

};

Mapbender.Model.MapPopup.prototype.openPopupOnXY = function openPopupOnXY(coord, content) {
    //console.log(this.overlay.setElement(this.$markup));
    this.$markup.find('.popup-content')[0].innerHTML = content(coord);
    this.overlay.setPosition(coord);
};

/**
 * Open popup on given coordinates with provided content
 *
 * @TODO May be it needs to merge this function and openPopupOnXY([x,y], callback)
 *
 * @param [x,y] coordinates
 * @param {string} content
 */
Mapbender.Model.MapPopup.prototype.openPopupOnXYWithCustomContent = function openPopupOnXY(coordinates, content) {
    this.$markup.find('.popup-content')[0].innerHTML = content;
    this.overlay.setPosition(coordinates);
};
window.Mapbender = Mapbender || {};
window.Mapbender.Model = Mapbender.Model || {};
window.Mapbender.Model.MapPopup = function($markup) {
    if(!$markup) {
        $markup = this.createPopupMarkup()
    }

    $markup.appendTo('body');
    this.$markup = $markup;

    this.overlay = new ol.Overlay({
        element : $markup[0],
        autoPan:          true,
        autoPanAnimation: {
            duration: 250
        }
    });

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

    this.$markup.find('popup-content').innerHTML = content(coord);
    this.overlay.setPosition(coord);
};
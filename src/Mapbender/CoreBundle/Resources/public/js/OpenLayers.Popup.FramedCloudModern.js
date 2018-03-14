/**
 * @see https://github.com/openlayers/ol2/blob/master/lib/OpenLayers/Popup/FramedCloud.js
 */

OpenLayers.Popup.FramedCloudModern = OpenLayers.Popup.FramedCloudModern || OpenLayers.Class(OpenLayers.Popup.FramedCloud, {
    CLASS_NAME: "OpenLayers.Popup.FramedCloudModern",
    initialize: function(id, lonlat, contentSize, contentHTML, anchor, closeBox,
                        closeBoxCallback) {
        // HACK: working back the base url of the application
        var overrideImageLocation = baseUrlStatic + 'bundles/mapbendercore/image/openlayers2/modern/';
        this.imageSrc = overrideImageLocation + 'cloud-popup-relative.png';
        OpenLayers.Popup.Framed.prototype.initialize.apply(this, arguments);
        this.contentDiv.className = this.contentDisplayClass;
    }
});

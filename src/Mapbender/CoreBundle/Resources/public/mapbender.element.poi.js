(function($) {

    $.widget('mapbender.mbPOI', {
        options: {
            target: undefined,
            useMailto: false,
            gps: undefined
        },
        map: null,
        mbMap: null,
        mapClickProxy: null,
        popup: null,
        point: null,
        poiMarkerLayer: null,
        poi: null,
        gpsElement: null,

        _create: function() {
            if(!Mapbender.checkTarget("mbPOI", this.options.target)){
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function() {
            this.map = $('#' + this.options.target);
            this.mbMap = this.map.data('mapbenderMbMap');
            this.mapClickProxy = $.proxy(this._mapClickHandler, this);

            if(this.options.gps) {
                this.gpsElement = $('#' + this.options.gps);
            }
        },

        defaultAction: function() {
            return this.activate();
        },

        /**
         * On activation, bind the onClick function to handle map click events.
         * For the call to be made in the right context, the onClickProxy must
         * be used.
         */
        activate: function() {
            if (!this.popup && this.map.length !== 0) {
                this._createDialog();
                this.map.on('click', this.mapClickProxy);
            }
        },

        /**
         * The actual click event handler. Here Pixel and World coordinates
         * are extracted and then send to the mapClickWorker
         */
        _mapClickHandler: function(event) {
            if(event && event.pageX && event.pageY) {
                var x, y;
                x = event.pageX - this.map.offset().left;
                y = event.pageY - this.map.offset().top;

                var mbMap = this.map.data('mapbenderMbMap'),
                olMap = mbMap.map.olMap,
                ll = olMap.getLonLatFromPixel(new OpenLayers.Pixel(x, y)),
                coordinates = {
                    pixel: {
                        x: x,
                        y: y
                    },
                    world: {
                        x: ll.lon,
                        y: ll.lat
                    }
                };

                this._setPoiMarkerLayer(mbMap, coordinates, ll);
            }
        },

        _setPoiMarkerLayer: function(mbMap, coordinates, latLon) {
            var proj = this.mbMap.map.olMap.getProjectionObject();
            var deci = 0;

            if (!this.poiMarkerLayer) {
                this.poiMarkerLayer = new OpenLayers.Layer.Markers();
                this.mbMap.map.olMap.addLayer(this.poiMarkerLayer);
            }

            this.poiMarkerLayer.clearMarkers();

            var poiMarker = new OpenLayers.Marker(latLon, new OpenLayers.Icon(
                Mapbender.configuration.application.urls.asset +
                this.mbMap.options.poiIcon.image, {
                    w: this.mbMap.options.poiIcon.width,
                    h: this.mbMap.options.poiIcon.height
                }, {
                    x: this.mbMap.options.poiIcon.xoffset,
                    y: this.mbMap.options.poiIcon.yoffset
                })
            );

            this.poiMarkerLayer.addMarker(poiMarker);

            if (proj.proj.units === 'degrees' || proj.proj.units === 'dd') {
                deci = 5;
            }

            this.popup.subtitle(
                '<b>' + coordinates.world.x.toFixed(deci) + ',' + coordinates.world.y.toFixed(deci) + ' @ 1:' + mbMap.model.getScale() + '</b>'
            );

            this.poi = {
                point: coordinates.world.x.toFixed(deci) + ',' + coordinates.world.y.toFixed(deci),
                scale: mbMap.model.getScale(),
                srs: proj.projCode
            };
        },

        _createDialog: function(){
            var self = this;
            this.popup = new Mapbender.Popup2({
                draggable: true,
                cssClass: 'mb-poi-popup',
                destroyOnClose: true,
                modal: false,
                title: this.element.attr('title'),
                content: $('.input', this.element).html(),
                buttons: {
                    'cancel': {
                        label: Mapbender.trans('mb.core.poi.popup.btn.cancel'),
                        cssClass: 'button buttonCancel critical right',
                        callback: function () {
                            self._reset();
                            if(self.gpsElement) {
                                self.gpsElement.mbGpsPosition('deactivate');
                            }
                            self.close();
                        }
                    },
                    'ok': {
                        label: Mapbender.trans('mb.core.poi.popup.btn.ok'),
                        cssClass: 'button right',
                        callback: function () {
                            self._sendPoi(this.$element);
                        }
                    }
                }
            });

            if(self.gpsElement) {
                this.popup.addButtons({'position':{
                    label: Mapbender.trans('mb.core.poi.popup.btn.position'),
                    cssClass: 'button right',
                    callback: function() {
                        self.gpsElement.mbGpsPosition('getGPSPosition', function(){
                            var loc = self.mbMap.map.olMap.getCenter();
                            var plox = self.mbMap.map.olMap.getPixelFromLonLat(loc);

                            var coordinates = {
                                pixel: {
                                    x: plox.x,
                                    y: plox.y
                                },
                                world: {
                                    x: loc.lon,
                                    y: loc.lat
                                }
                            };

                            self._setPoiMarkerLayer(self.mbMap, coordinates, loc);
                        });
                    }
                }});
            }
            // For close button top right on Popup...
            this.popup.$element.on('close', this.close.bind(this));
        },

        close: function() {
            if (this.poiMarkerLayer) {
                this.poiMarkerLayer.clearMarkers();
                this.mbMap.map.olMap.removeLayer(this.poiMarkerLayer);
                this.poiMarkerLayer.destroy();
                this.poiMarkerLayer = null;
            }
            if (this.popup) {
                // To disable infinitely recursing event barrage...
                this.popup.$element.off('close');
                this.popup.close();
            }
            if (this.gpsElement) {
                this.gpsElement.mbGpsPosition('deactivate');
            }
            this.popup = null;
            this.map.off('click', self.mapClickProxy);
        },

        _sendPoi: function(content) {
            var form = $('form', content);
            var body = $('#body', form).val();

            if(!this.poi) {
                return;
            }

            var poi = $.extend({}, this.poi, {
                label: body.replace(/\n|\r/g, '<br />')
            });
            var params = $.param({ poi: poi });
            var poiURL = window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + params;
            body += '\n\n' + poiURL;
            /*
             * @ TODO use MapbenderCoreBundle/Resources/public/mapbender.social_media_connector.js
             * to call social networks
             */
            if(this.options.useMailto) {
                var mailto_link = 'mailto:?body=' + escape(body);
                win = window.open(mailto_link,'emailWindow');
                window.setTimeout(function() {if (win && win.open &&!win.closed) win.close();}, 100);
            } else {
                var ta = $('<div/>', {
                    html: $('.output', this.element).html()
                });
                ta.addClass("poi-link");
                $('textarea', ta).val(body);
                new Mapbender.Popup2({
                    destroyOnClose: true,
                    modal: true,
                    title: this.element.attr('title'),
                    height: 350,
                    content: ta,
                    buttons: {}
                });
            }

            this._reset();
            this.popup.close();

        },

        _reset: function() {
            this.poi = null;
        }
    });

})(jQuery);

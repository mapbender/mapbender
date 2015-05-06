/**
 * Digitizing tool set
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @author Stefan Winkelmann <stefan.winkelmann@wheregroup.com>
 *
 * @copyright 20.04.2015 by WhereGroup GmbH & Co. KG
 */
(function($) {

    $.widget("mapbender.digitizingToolSet", {

        options:           {layer: null},
        controls:          null,
        _activeControls:   [],
        currentController: null,

        /**
         * Init controls
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var mapElement = widget.getMapElement();
            var layer = widget.getLayer();

            widget.controls = {
                drawPoint:      {
                    infoText: "Draw point",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Point, {
                        featureAdded: function(feature) {
                            widget._trigger("featureAdded", null, feature);
                        }
                    })
                },
                drawLine:       {
                    infoText: "Draw line",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Path, {
                        featureAdded: function(feature) {
                            widget._trigger("featureAdded", null, feature);
                        }
                    })
                },
                drawPolygon:    {
                    infoText: "Draw polygone",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon, {
                        featureAdded: function(feature) {
                            widget._trigger("featureAdded", null, feature);
                        }
                    })
                },
                drawRectangle:  {
                    infoText: "Draw rectangle",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                        handlerOptions: {
                            sides:     4,
                            irregular: true
                        }
                    })
                },
                drawCircle:     {
                    infoText: "Draw circle",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                        handlerOptions: {
                            sides: 40
                        }
                    })
                },
                drawEllipse:    {
                    infoText: "Draw ellipse",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                        handlerOptions: {
                            sides:     40,
                            irregular: true
                        }
                    })
                },
                drawDonut:          {
                    infoText: "Draw donut",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon, {
                        handlerOptions: {
                            holeModifier: 'element'
                        }
                    })
                },
                modifyFeature:           {
                    infoText: "Select and edit geometry position/size",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'crosshair'});
                        } else {
                            mapElement.css({cursor: 'default'});
                        }

                    },
                    control:  new OpenLayers.Control.ModifyFeature(layer)
                },
                moveFeature:           {
                    infoText: "Move geometry",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        if(widget.setController(el.data('control'))) {
                            mapElement.css({cursor: 'default'});
                        }
                        mapElement.css({cursor: 'default'});
                    },
                    control:  new OpenLayers.Control.DragFeature(layer, {
                        onStart:    function(feature) {
                            feature.renderIntent = 'select';
                        },
                        onComplete: function(feature) {
                            feature.renderIntent = 'default';
                            feature.layer.redraw();
                        }
                    })
                },
                selectFeature:         {
                    infoText: "Select geometry",
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        widget.setController(el.data('control'));
                        mapElement.css({cursor: 'default'});

                    },
                    control:  new OpenLayers.Control.SelectFeature(layer, {
                        clickout:    true,
                        toggle:      true,
                        multiple:    true,
                        hover:       false,
                        box:         true,
                        toggleKey:   "ctrlKey", // ctrl key removes from selection
                        multipleKey: "shiftKey" // shift key adds to selection
                    })
                },
                removeSelected: {
                    infoText: "Remove selected geometries",
                    cssClass: 'critical',
                    listener: function() {
                        layer.removeFeatures(layer.selectedFeatures);
                    }
                },
                removeAll:      {
                    infoText: "Remove all geometries",
                    cssClass: 'critical',
                    listener: function() {
                        layer.removeAllFeatures();
                    }
                }
            };
            widget.element.addClass('digitizing-tool-set');
            widget.refresh();
        },

        /**
         * Refresh widget
         */
        refresh: function() {
            var widget = this;
            var element = $(widget.element);
            var children = widget.options.children;
            var layer = widget.getLayer();
            var map = layer.map;

            if(widget.options.hasOwnProperty('onFeatureAdded')) {
                widget.element.bind('mbdigitizertoolsetfeatureadded', widget.options.onFeatureAdded);
            }

            // clean controllers
            widget.cleanUp();

            // clean navigation
            element.empty();

            widget.buildNavigation(children);

            // Init map controllers
            for (var k in widget._activeControls) {
                map.addControl(widget._activeControls[k]);
            }

            widget._trigger('ready', null, this);
        },

        _setOptions: function(options) {
            this._super(options);
            this.refresh();
        },

        /**
         * Switch between current and element controller.
         * Returns true if last controller was different to given one
         *
         * @param controller
         * @returns {boolean}
         */
        setController: function(controller) {
            var widget = this;

            if(controller) {
                controller.activate();
            }

            if(widget.currentController) {
                if(widget.currentController instanceof OpenLayers.Control.SelectFeature) {
                    widget.currentController.unselectAll();
                }

                widget.currentController.deactivate();

                if(controller === widget.currentController) {
                    widget.currentController = null;
                    return false;
                }
            }

            widget.currentController = controller;
            return true;
        },

        /**
         * Build Navigation
         *
         * @param buttons
         */
        buildNavigation: function(buttons) {
            var widget = this;
            var element = $(widget.element);
            var controls = widget.controls;

            $.each(buttons, function(i, item) {
                //var item = this;
                if(!item || !item.hasOwnProperty('type')){
                    return;
                }
                var button = $("<button class='button' type='button'/>");
                var type = item.type;

                button.addClass(item.type);
                button.data(item);

                if(controls.hasOwnProperty(type)) {
                    var controlDefinition = controls[type];

                    if(controlDefinition.hasOwnProperty('infoText')){
                        button.attr('title',controlDefinition.infoText)
                    }

                    // add icon css class
                    button.addClass("icon-" + type.replace(/([A-Z])+/g,'-$1').toLowerCase());

                    if(controlDefinition.hasOwnProperty('cssClass')){
                        button.addClass(controlDefinition.cssClass)
                    }

                    button.on('click', controlDefinition.listener);

                    if(controlDefinition.hasOwnProperty('control')) {
                        button.data('control', controlDefinition.control);
                        widget._activeControls.push(controlDefinition.control);

                        var drawControlEvents = controlDefinition.control.events;
                        drawControlEvents.register('activate', button, function() {
                            button.addClass('active');
                        });
                        drawControlEvents.register('deactivate', button, function() {
                            button.removeClass('active');
                        });
                    }
                }

                element.append(button);
            });
        },

        /**
         * Clean up
         */
        cleanUp: function() {
            var widget = this;
            if(!widget.hasLayer()){
                return;
            }
            var layer = widget.getLayer();
            var mapElement = widget.getMapElement();
            var map = layer.map;
            var activeControls = widget._activeControls;
            for (var k in  activeControls) {
                var control = activeControls[k];
                control.deactivate();
                mapElement.css({cursor: 'default'});
                map.removeControl(control);
            }

            widget._activeControls = [];
        },

        /**
         * Get OpenLayer Layer
         *
         * @return OpenLayers.Map.OpenLayers.Class.initialize
         */
        getLayer: function() {
            return this.options.layer;
        },

        /**
         * Get map jQuery HTML element
         *
         * @return HTMLElement jquery HTML element
         */
        getMapElement: function() {
            var layer = this.getLayer();
            return layer?$(layer.map.div):null;
        },

        /**
         * Has layer?
         * @return {boolean}
         */
        hasLayer: function(){
            return !!this.getLayer();
        }
    });
})(jQuery);

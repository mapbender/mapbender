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

        options:           {},
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
                donut:          {
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
                edit:           {
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
                drag:           {
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
                select:         {
                    listener: function(e) {
                        var el = $(e.currentTarget);
                        setController(el.data('control'));
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
                    listener: function(e) {
                        layer.removeFeatures(layer.selectedFeatures);
                    }
                },
                removeAll:      {
                    listener: function(e) {
                        layer.removeAllFeatures();
                    }
                }
            };

            widget.refresh();
        },

        /**
         * Refresh widget
         */
        refresh: function() {
            var widget = this;
            var element = $(widget.element);
            var items = widget.options.items;
            var layer = widget.getLayer();
            var map = layer.map;

            // clean controllers
            widget.cleanUp();

            // clean navigation
            element.empty();

            widget.buildNavigation(items);

            // Init map controllers
            for (var key in widget._activeControls) {
                map.addControl(widget._activeControls[key]);
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
        setController:   function(controller) {
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

            $.each(buttons, function(idx, _button) {
                var item = this;
                var button = $("<button class='btn'/>");
                var type = item.type;
                button.html(item.type);
                button.data(item);

                if(controls.hasOwnProperty(type)) {
                    var controlDefiniton = controls[type];
                    button.on('click', controlDefiniton.listener);

                    if(controlDefiniton.hasOwnProperty('control')) {
                        button.data('control', controlDefiniton.control);
                        widget._activeControls.push(controlDefiniton.control);

                        var drawControlEvents = controlDefiniton.control.events;
                        drawControlEvents.register('activate', button, function(e, obj) {
                            button.addClass('active');
                        });
                        drawControlEvents.register('deactivate', button, function(e, obj) {
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
            var mapElement = widget.getMapElement();
            var map = widget.getLayer().map;
            var key;

            for (key in widget._activeControls) {
                var control = widget._activeControls[key];
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
         * @param map
         * @return HTMLElement jquery HTML element
         */
        getMapElement: function(map) {
            return $(this.getLayer().map.div);
        }
    });
})(jQuery);

Feature Info
************

This element provides feature info capabilties to Mapbender 3. It works for WMS services.

Class, Widget & Style
==============

* Class: Mapbender\CoreBundle\Element\FeatureInfo
* Widget: mapbender.element.featureInfo.js, subclasses mapbender.element.button.js
* Style: mapbender.elements.css

Configuration
=============

Also see :doc:`button` for inherited configuration options.

.. code-block:: yaml

   target: ~ # Id of Map element to query


HTTP Callbacks
==============

None.

JavaScript API
==============

activate
--------

Activates the widget which then waits for mouse click on the map and starts the feature info queries.

deactivate
----------
Deactivates the widget.

JavaScript Signals
==================

None.

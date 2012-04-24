The Map Element
===============

Configuration
-------------

* dpi: Screen resolution to assume, defaults to 72
* imgPath: Path to image folder for OpenLayers
* srs: Projection to use
* extents
  - max: Maximum extent as array of xmin, ymin, xmax, ymax
  - start: Start extent as array of xmin, ymin, xmax, ymax
* units: Projection units (degrees, m, ...)
* layerset: Layerset id to use

Map Controls
* controls: ...

The following options determine zoom levels:
* maxResolution: See OpenLayers documentation
* numZoomLevels: See OpenLayers documentation
* scales: Easiest way to define zoom levels, give array of scale denominators
* overview
  - layerset: Layerset id to use in overview
  - div: id of div to display overview in. If not given, standard OpenLayers
    overview is used
  - fixed: Try not to zoom in overview

API
---
goto
~~~~
center
~~~~~~
highlight
~~~~~~~~~
layer
~~~~~
appendLayer
~~~~~~~~~~~
insert
~~~~~~
rebuildStacking
~~~~~~~~~~~~~~~
move
~~~~
zoomIn
~~~~~~
zoomOut
~~~~~~~
zoomToFullExtent
~~~~~~~~~~~~~~~~
zoomToExtent
~~~~~~~~~~~~
zoomToScale
~~~~~~~~~~~
panMode
~~~~~~~
addPopup
~~~~~~~~
removePopup
~~~~~~~~~~~
removeById
~~~~~~~~~~
layerById
~~~~~~~~~
scales
~~~~~~


Configuration of WMS Layers
===========================

* class: Mapbender\WmsBundle\WmsLayer
* title: The title to display, in the table of contents for example
* url: The URL for the GetMap request
* layers: An array of WMS layer definitions:

  - name: Name of the WMS layer
  - title: The title to display, in the table of contents for example
  - visible: Should this layer be loaded on start, defaults to true
  - queryable: Is the layer queryable, defaults to false
  - maxScale: The denominator of the maximum scale at which to display this
    layer
  - minScale: The denominator of the minimum scale at which to display this
    layer
* tiled: Should the layer be requested in several tiled requests, defaults to
  false
* baselayer: Is this an baselayer? Defaults to false
* transparent: Should the transparent flag be included in the GetMap request?
* format: Image format to request, defaults to image/png
* opacity: Opacity in percent, defaults to 100
* visible: Should the layer be loaded on start, defaults to true
* attribution: Attribution text
* proxy: Should the layer be loaded trough the Mapbender3 proxy, in which case
  the url parameter must be given as the proxy server shall request it.

Example::

    wms1:
        class: Mapbender\WmsBundle\WmsLayer
        title: World
        url: http://myinteralserver/wms
        layers:
            -{ }
        tiled: true
        baselayer: true
        format: image/jpg
        visible: true
        attribution: (c) The magic WMS company
        proxy: true


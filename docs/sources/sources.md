# Data sources

A data source describes the content that is displayed on a map. The core mapbender supports the following data sources 
(where the "YAML key" should be used as "type" when defining a YAML-based application):

| source type                                      | YAML key     |
|--------------------------------------------------|--------------|
| Web Map Service (WMS)                            | wms          |
| Web Map Tile Service (WMTS)                      | wmts         |
| Tile Map Service (TMS)                           | tms          |
| Web Map Service Time (WMS-T)                     | wms          |
| Vector Tiles using the Mapbox Vector Tile format | vector_tiles |

This tutorial describes how to create a new source that can be configured in the backoffice and displayed in the frontend.

Note that a new configurable data source is a complex addition to Mapbender that requires deep knowledge of
the mapbender core. Creating a new data source solely in the frontend without configurability is simpler, 
but might be overkill already. If you just want to display  data, you can create a [custom element](../elements/elements.md) 
and use the method `Mapbender.vectorLayerPool.getElementLayer(this, 0)` to obtain a layer you can display features on.

If you want your new source to appear in the layer tree in a hierarchical way where the layers should be sortable, toggleable
and integrated into the legend, creating a custom data source is the right choice for the task. 

If you need backend configurability, start with the [Custom Source Backend](custom-source-backend.md) tutorial. Otherwise, 
you can proceed directly with the [Custom Source Frontend](custom-source-frontend.md) tutorial.

[↑ Back to top](#custom-data-sources)

[← Back to README](../README.md)

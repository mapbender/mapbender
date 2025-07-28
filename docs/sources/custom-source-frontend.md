# Custom Sources - Frontend
If you [configured the backend](custom-source-backend.md) for your custom data source or don't need backend configurability, 
you can proceed to the frontend implementation of your custom source.



## Extending from `Mapbender.Source` and `Mapbender.SourceLayer`
Both `Source` and `SourceLayer` are JavaScript native classes that you can extend to create your own custom source:

```js
class MySource extends Mapbender.Source {
    //...
}
```

The `Source` represents the data source itself and is responsible for the data rendering and managing the layers.  
Within a `Source`, there can be multiple `SourceLayer`s. Each SourceLayer gets an individual entry in the LayerTree and 
can be toggled individually. For example, in a WMS service within one url there can be multiple layers.

### `Mapbender.Source`
When extending from `Mapbender.Source` you need to implement the following methods:

- `createNativeLayers(srsName, mapOptions)`: Eventually your data will be rendered on an [OpenLayers map](https://openlayers.org/).
  Create and return the native layers you need here. For example, for a single vector layer:

```js
createNativeLayers(srsName, mapOptions) {
    this.nativeLayers = [new ol.layer.Vector({
        source: new ol.source.Vector()
    })];
    return this.nativeLayers;
}
```

- `getSelected()`: Is the source selected in the layer tree. In most cases, 
  you can delegate this to the root layer (`return this.getRootLayer().getSelected()`)

- `updateEngine()`: this method is called when the content should be updated, e.g. because a layer's visibility has
  been toggled, a new layer has been added, the layer order has changed etc.    
  Use `this.getNativeLayer()` to access the OL native layer and perform operations on it (refer to the open layers docs)  
  Note that new layers are hidden by default, so make sure to call `Mapbender.mapEngine.setLayerVisibility(olLayer, true)`  
  Also, you need to check the individual layer's `layer.state.visibility` property, this will be updated when a layer
  is toggled in the layer tree.

- `setLayerOrder(newLayerIdOrder)`: The layers within this source have been reordered in the layer tree. The argument
  list all the ids in the new order. There is a default implementation available that might work for your use case already.


Also, you can override the following methods:

- `featureInfoEnabled()`: Indicates whether this source supports feature info requests. Default: false. Note that for
   sources that support a `GetFeatureInfo` request, there is an intermediate abstract class `GetFeatureInfoSource` that
   handles generating the url and downloading the result
- `loadFeatureInfo(mapModel, x, y, options): [?string, Promise<string>]`: Called when a feature info request is triggered for this source.
   The `mapModel` is the current map model, `x` and `y` are the pixel coordinates of the click event, and `options` contains
   the maxCount or the iframe injection script for feature info highlighting. You need to return an array with the (optional)
   url for the "open in new window" feature and a Promise that resolves with the HTML content to be displayed in the popup or sidepane.
- `getSettings()`, `applySettings(settings)`, `applySettingsDiff(settings)`, `diffSettings(from, to)`: 
  modifies runtime settings you might need for your source. By default, this is only opacity.
- `getConfiguredSettings()`: returns the initial settings set during initialisation
- `checkRecreateOnSrsSwitch(oldProj, newProj)`: indicates whether this source should be recreated when a srs change occurs
- `getPrintConfigs(bounds, scale, srsName)`: Returns information that is passed to the printing service when printing or exporting a map

The following methods are also available to be used which you probably don't need to override:

- `getLayerById(id)`: Gets the Mapbender.SourceLayer with the given id
- `getRootLayer()`: Every source should have exactly one root layer which is returned by this method
- `getNativeLayer(index)`: Gets the native layer with the specified index (or the first one if index is undefined)
- `getActive()`: Is the source and all its parents selected, e.g. is it visible

### `Mapbender.SourceLayer`

For a SourceLayer no methods are required to be overridden. The following overrides might be useful:

- `hasBounds()`: is this layer restricted to spatial bbox? (default: true if options.bbox exists, false otherwise)
- `getBounds(projCode, inheritFromParent)`: if `hasBounds()` returns true, calculate and return the bbox in the given SRS (default: options.bbox transformed to the given projection)
- `isInScale(scale)`: Should the layer be displayed at this scale level? (default: calculation based on options.minScale and options.maxScale, true if the options are not set)
- `supportsProjection(srsName)`: Can the layer be displayed in the given projection? (default true)
- `intersectsExtent(extent, srsName)`: Does the layer have features in this extent? (default true)
- `getSupportedMenuOptions()`: Returns a list of menu options supported by this layer. See [below](#custom-layer-tree-menu-item) for details
- `getLegend()`: Returns the legend for this layer. The legend can be either an external url to an image (e.g. for WMS services) 
   or a style definition that is rendered on a canvas. See [below](#legend-entries-for-custom-sources) for details

The following methods are also available to be used which you probably don't need to override:

- `getSelected()` / `setSelected(state)`: Gets/sets the visibility of this layer. Caution: This does not mean it's visible on the map, if parent layers are not selected.
- `getActive()`: Checks if the layer is actually visible on the map (i.e. whether this layer and all parents `getSelected` return true)
- `remove()`: Deletes this layer
- `addChild(newSourceLayer)`: Adds a new sublayer
- `getId()` / `getName()`

## Registering your new source

In order for the source factory to find your new source and layer classes, you need to register them in the type map:

```js
Mapbender.SourceLayer.typeMap['my-identifier'] = MySourceLayer;
Mapbender.Source.typeMap['my-identifier'] = MySource;
```

If your data source is configurable in the backoffice, make sure to use the same identifier as returned by `DataSource::getName()`.

## Instantiating a new source
If you want your custom source to be configurable in the backoffice, you can skip this section, since the source definition
will be created and added to the LayerTree automatically by the ConfigController.

If you do not need or want backend configurability, you need to instantiate the source somewhere, e.g. in a custom element.

This works with the following code:

```js
const source = Mapbender.Source.factory(sourceDef);
Mapbender.model.addSource(source);

// shortcut
const source2 = Mapbender.model.addSourceFromConfig(sourceDef);
```

The sourceDef needs to be an JS object with the following properties:
```js
const sourceDef = {
    type: "my-identifier", // should match the Source identifier from section 'Registering your new source'
    id: "some-unique-source-id",
    children: [{ // source should have exactly one child: the root layer
        options: {
            id: "some-unique-root-layer-id",
            title: "Root Layer title for the layer tree",
            opacity: 1.0, // only required for root layer
        },
        children: [subLayer1, subLayer2],
    }]
}
```

Like the root layer, the sublayers need to have an `options` property where id and title are required (add as many more
properties as you need, you can access them in your `updateEngine` method) and have an optional `children` array for
deeper hierarchies.

To add layers to an existing custom source later, use the following code:

```js
const rootLayer = mySourceLayer.getRootLayer();
const newLayerSource = Mapbender.SourceLayer.factory(newLayerConfig, mySourceLayer, rootLayer)
rootLayer.addChild(newLayerSource);
```


## Legend entries for custom sources
If your source provides a legend, override the method `getLegend` in your SourceLayer. It will be rendered
both on in the legend element and in pdf exports if legends are enabled there.

If you want to refer to an external image url, return the following object:

```js
{
    type: 'url',
    url: 'https.//...',
}
```

You can also provide a style definition. It looks like this:

```js
{
    type: 'style',
    title: 'Heading for the legend graphic',
    layers: [
        {
            title: 'Sublayer 1',
            style: styleDefinitionSubLayer1
        },
        {
            title: 'Sublayer 2',
            style: styleDefinitionSubLayer2
        }
    ]
}
```

The style definition can contain the following all optional properties:

- fillColor
- fillOpacity (0-1).
- strokeColor
- strokeOpacity (0-1)
- strokeWidth (in pixels)
- fontFamily
- fontColor
- fontWeight
- labelOutlineWidth (in pixels)
- labelOutlineColor


The third option is to return a canvas in your source:

```js
{
    type: 'canvas',
    title: 'Heading for the legend graphic',
    layers: [
        {
            title: 'Sublayer 1',
            canvas: canvas1 // reference to HTMLCanvasElement
        },
        {
            title: 'Sublayer 2',
            canvas: canvas2 // reference to HTMLCanvasElement
        }
    ]
}
```

For all definitions, there is the additional property `topLevel`. Set this to true, if you manage
your legend on the root layer level.

## Custom layer tree menu item
The core mapbender supports the following menu options in the layer tree:

- **layerremove**: Deletes this layer
- **metadata**: Opens metadata in a new window. options.medataUrl should be defined
- **opacity**: Opacity slider between 0 and 1
- **dimension**: selection slider for dimensions like e.g. time
- **zoomtolayer**: Changes the map's view to fit the layer

If you want to add another layer tree menu item, perform the following steps:

Add a new element extending the core LayerTree:

```php
<?php

use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Mapbender\CoreBundle\Element\Layertree as CoreLayerTree;

#[AutoconfigureTag('mapbender.element', ['replaces' => CoreLayerTree::class])]
class LayerTree extends CoreLayerTree
{
    public function getWidgetName(Element $element)
    {
        return 'custom.layertree';
    }

    public static function getType()
    {
        return CustomLayerTreeAdminType::class;
    }

    public function getTwigTemplatePath(): string
    {
        return '@Custom/Element/layertree.html.twig';
    }

    public function getRequiredAssets(Element $element)
    {
        $assets = parent::getRequiredAssets($element);
        $assets['js'][] = '@CustomBundle/Resources/public/js/layertree.js';
        return $assets;
    }
}
```

If you want to have your new menu item selectable in the Layer Tree configuration backend, add two more PHP classes:

```php
class CustomLayerTreeAdminType extends LayertreeAdminType
{
    public function getMenuCollectionType(): string
    {
        return CustomLayerTreeMenuType::class;
    }
}


class CustomLayerTreeMenuType extends LayerTreeMenuType
{
    public function getChoices(): array
    {
        $choices = parent::getChoices();
        $choices[] = "new-menu-item";
        return $choices;
    }

}
```

In the twig template, extend from the core layertree template. You can override the block `layertree_menu_custom` if you
have a more complex menu item, or `layertree_menu_custom_textend` for simple icons. ALways use the `data-menu-action` 
attribute, then enabling/disabling according to the setting in the backoffice will happen automatically.

```html
{% extends "@MapbenderCore/Element/layertree.html.twig" %}

{% block layertree_menu_custom %}
   <div data-menu-action="new-menu-item">
       Arbitrary complex structure goes here
   </div>
{% endblock %}

{% block layertree_menu_custom_textend %}
   <span data-menu-action="new-menu-item" class="fa fas fa-my-icon hover-highlight-effect clickable" title="..."></span>
{% endblock %}
```


In the overridden JavaScript widget, initialise the new functionality:

```js
(function ($) {
    $.widget("custom.layertree", $.mapbender.mbLayertree, {
        _setup: function (mbMap) {
            this._super(mbMap);
            // use the following line if the menu item should always be enabled. Then you don't need to override 
            // the admin type
            this.options.menu.push('searchremove');
        },
        // override this if your menu item needs initialisation (not necessary for buttons)
        _initMenuAction(action, $actionElement, $layerNode, layer) {
            switch(action) {
                case 'new-menu-item':
                    // do something for initialisation
                    break;
                default:
                    return this._super(action, $actionElement, $layerNode, layer);
            }
        },
        _createEvents: function () {
            this._super();
            // add a click (or whatever you need) listener here. 
            this.element.on('click', '[data-menu-action=new-menu-item]', () => ...);
        },
    });
})(jQuery);

```

[↑ Back to top](#custom-sources---frontend)

[← Back to README](../README.md)

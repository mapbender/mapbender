# Overriding elements

In many cases, a customer needs an element that already exists in the Mapbender Core, but wants a different behaviour in details.
In this case, the existing element can be overwritten and only the parts that differ can be changed. Here we overwrite the measuring tool (Ruler) for demonstration purposes.

First step: Create a new class in the `Element` subfolder of the bundle. Inherit directly from the element to be edited:

```php
<?php

namespace MyCustomer\MyCustomBundle\Element;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Mapbender\CoreBundle\Element\Ruler;

#[AutoconfigureTag('mapbender.element')]
class NonPopupRuler extends Ruler
{

}
```

If the standard ruler should no longer to be available in the application and should to be automatically replaced by the new element whenever it would otherwise appear, e.g. also in the demo applications, a `replaces` attribute can be added to the AutoConfigureTag:

```php
#[AutoconfigureTag('mapbender.element', ['replaces' => Ruler::class])]
```

:warning: If you don't use Autowiring, instead of adding the AutoconfigureTag attribute, follow [this guide](../architecture/bundles-without-autoconfiguration.md#registering-an-element)

In the simplest case, only one additional JavaScript file needs to be included in the element class. To do this, `getRequiredAssets` must be overwritten.

```php
public function getRequiredAssets(Element $element)
{
    $assets = parent::getRequiredAssets($element);
    $assets['js'][] = '@MyCustomBundle/Resources/public/custom-ruler.js';
    return $assets;
}
```

You can also add additional css or translation (key: `trans`) files using the same logic.

Furthermore, the widget name must be adapted.

```php
public function getWidgetName(Element $element)
{
    return 'MbCustomRuler';
}
```

The file just referenced can now be created in the *Resources/public/elements* folder. It is important that not the entire file has to be copied from the core! This would make updates very time-consuming. Instead, you can extend the javascript class.

Methods do not have to be completely overwritten if only something is to be added. The method of the same name of the parent widget can be called via `super.functionName()`. The example here works exactly like the mapbender core ruler, except that it also outputs a message on the console when it is activated and deactivated.

```js
(function () {
    class MbCustomRuler extends Mapbender.Element.MbRuler {
        constructor(configuration, $element) {
            super(configuration, $element);
            ...
        }
        activate (callback) {
            console.log('Hello world');
            super.activate(callback);
        }
        deactivate () {
            console.log('Bye bye world');
            super.deactivate();
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCustomRuler = MbCustomRuler;
})();
```

For a more thorough documentation of the widgets, see [elements.md](elements.md)

[↑ Back to top](#overriding-elements)

[← Back to README](../README.md)

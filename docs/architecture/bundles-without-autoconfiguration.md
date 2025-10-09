# Bundles without Autowiring

It is recommended that you use Symfony's [Autowiring and Autoconfiguration](https://symfony.com/doc/6.4/service_container/autowiring.html). If you don't want to use this feature for your mapbender project, follow this guide in addition to the [regular Project Setup Guide](../getting_started/setup.md).

## Disabling Autowiring
Autowiring and Autoconfiguration are enabled by default. To disable them, edit config/services.yaml and change the defaults for these values:

```yaml
services:
    _defaults:
        autowire: false
        autoconfigure: false
```


## Setting up a custom bundle

Instead of creating an empty bundle file, add the following code:

```php
<?php

namespace MyCustomer\MyCustomBundle

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyTreatBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $xmlLoader = new XmlFileLoader($container, $configLocator);
        $xmlLoader->load('elements.xml');
        // Auto-rebuild on config change
        $container->addResource(new FileResource($xmlLoader->getLocator()->locate('elements.xml')));
    }
}
```

Also, create the file `elements.xml` at `Resources/config`:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        
    </services>
</container>
```

All elements, templates, form types etc. will be declared here later. You can also add several files (e.g. an elements.xml, templates.xml, services.xml etc.), just make sure to tell the xmlLoader about them in your bundle's `build` function.


## Registering an element

A new element must be registered in `elements.xml`. Insert the following as a child tag within `services`:

```xml
<service id="mycustomer.nonpopupruler"
         class="MyCustomer\MyCustomBundle\Element\NonPopupRuler">
    <tag name="mapbender.element" />
</service>
```

The ID can be chosen arbitrarily, what is important is the class (the FQCN of the class just created) and the tag `mapbender.element`. If the new element replaces a core element that should no longer to be available in the application whenever it would otherwise appear, e.g. also in the demo applications, a `replaces` attribute can be added.

```xml
<service id="mycustomer.nonpopupruler"
         class="MyCustomer\MyCustomBundle\Element\NonPopupRuler">
    <tag name="mapbender.element" replaces="Mapbender\CoreBundle\Element\Ruler"/>
</service>
```


[↑ Back to top](#bundles-without-autowiring)

[← Back to README](../README.md)


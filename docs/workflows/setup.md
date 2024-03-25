# Project Setup Guide

This guide is intended to help you set up new Mapbender projects as painlessly as possible. It starts from scratch, Mapbender knowledge is not necessarily required.

## Initialising the repository

Customer projects are usually managed in our Gitlab (repo.wheregroup.com). First, a new repository should be created there in the customer group and cloned locally.

```bash
- git clone git@repo.wheregroup.com:.....
- git remote add mapbender git@github.com:mapbender/mapbender-starter.git
- git fetch --all
- git fetch --tags
- git merge v3.3.4 # a tagged release as base -> recommended 
- git merge mapbender/master # the master as base -> not recommended
```

Merge conflicts in *README.md* can be ignored for now, just use your own version, the default *README.md* in the customer projects makes no sense.

After initialisation, the mapbender-remote can be removed again:

```bash
git remote remove mapbender
```

> [!IMPORTANT]
> For updates, add it again and then merge it into the local branch using the same merge command. In principle, the remote can also be left in, but then make sure that the customer project is NEVER pushed to the mapbender remote!

Bootstrapping then follows as described in [Mapbender Starter README](https://github.com/mapbender/mapbender-starter/blob/master/README.md#bootstrapping).

## Configuration

The following initial configurations are necessary, all in `application/app/config`:

- `applications`: Demo applications. Can all be deleted.
- `config.yml`: If several databases are used, the connections can be added under `doctrine.dbal.connections`, analogue to the default connection. The actual passwords are set in `parameters.yml`.
- `parameters.yml`: not checked in, should have been created by bootstrapping. If the built-in sqlite database is to be used, nothing needs to be entered here, otherwise the DB connection(s) can be configured here. (Postgres has `pdo_pgsql` as driver, Post 5432)
- `routing.yml`: If you want to add your own routes (independent pages) for the project, you can add them here later. However, the bundle must be created first.
- `security.yml`: If you want to add your own roles for the project, you can add them here later. User-defined routes can also be secured here.

## Create your own bundle

The customer-specific code (elements, templates etc.) is located under `application/src`. By convention, one (or several for larger projects) bundle is created for the customer.

- Create folder: According to convention: Name of the customer directly in src, followed by the name of the project followed by "Bundle".

Create the main bundle file in it:

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
    public function build(ContainerBuilder $container)
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

The content of the bundle file does not usually change.

Create the file `elements.xml` under Resources/config:

```xml
<?xml version="1.0" ?>
<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">
    <services>
        
    </services>
</container>
```

All elements, templates, form types etc. will be declared here later.

Congratulations for achieving the first big step! Now you just have to let Symfony know that the bundle exists. To do this, the `application/app/AppKernel.php` must be edited. Add the newly created bundle file to the `registerBundles` function in the `$bundles` array.

## Add elements 1 - Change the behaviour of an existing element

In many cases, the customer basically needs an element that already exists in the Mapbender Core, but wants a different behaviour in details.
In this case, the existing element can be overwritten and only the parts that differ can be changed. Here we overwrite the measuring tool (Ruler) for demonstration purposes.

First step: Create a new class in the `Element` subfolder of the bundle. Inherit directly from the element to be edited:

```php
<?php

namespace MyCustomer\MyCoreBundle\Element;

use Mapbender\CoreBundle\Element\Ruler;

class NonPopupRuler extends Ruler
{

}
```

The element must now be registered in `elements.xml`. Insert the following as a child tag within `services`:

```xml
<service id="mycustomer.nonpopupruler"
         class="MyCustomer\MyCustomBundle\Element\NonPopupRuler">
    <tag name="mapbender.element" />
</service>
```

The ID can be chosen arbitrarily, what is important is the class (the FQCN of the class just created) and the tag `mapbender.element`. If the standard ruler is no longer to be available in the application and is to be automatically replaced by the new element whenever it would otherwise appear, e.g. also in the demo applications, a `replaces` attribute can be added.

```xml
<service id="mycustomer.nonpopupruler"
         class="MyCustomer\MyCustomBundle\Element\NonPopupRuler">
    <tag name="mapbender.element" replaces="Mapbender\CoreBundle\Element\Ruler"/>
</service>
```

In the simplest case, only one additional JavaScript file needs to be included in the element class. To do this, `getRequiredAssets` must be overwritten.

```php
public function getRequiredAssets(Element $element)
{
    $assets = parent::getRequiredAssets($element);
    $assets['js'][] = '@MeinTollesBundle/Resources/public/meinollesbundle.ruler.js';
    return $assets;
}
```

Furthermore, the widget name must be adapted.

```php
public function getWidgetName(Element $element)
{
    return 'meintollesbundle.ruler';
}
```

The file just referenced can now be created in the *Resources/public* folder. It is important that the entire file does not have to be copied from the core! This makes updates very time-consuming. Instead, jQuery-UI - each element is a jQuery-UI widget at JavaScript level - has an inheritance functionality.

Widgets are defined with `$.widget`. The arguments of this function are

- `widgetName`: Must match the PHP file,
- `parentWidget`: This is where the superclass goes. Already defined widgets are available directly under the jQuery $.
- `widgetDefinition`: Object with all variables and methods.

Even methods do not have to be completely overwritten if only something is to be added. The method of the same name of the parent widget can be called via `this._super()`. The example here works exactly like the mapbender core ruler, except that it also outputs a message on the console when it is activated and deactivated.

```js
(function ($) {
    $.widget("meintollesbundle.ruler", $.mapbender.mbRuler, {
        activate: function (callback) {
            console.log('Hello world');
            this._super(callback);
        },
        deactivate: function () {
            console.log('Bye bye world');
            this._super();
        }
    })
})(jQuery);
```

That's it! Now you can already do the basics. More information on how to add templates, handle requests, extend templates and create completely new elements without parent will follow later or are already described in the other documentation pages.

[↑ Back to top](#project-setup-guide)

[← Back to README](../README.md)

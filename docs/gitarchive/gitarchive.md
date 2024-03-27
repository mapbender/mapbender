# Git Archive

## Good to know: Details on technical pull requests

### [1538](https://github.com/mapbender/mapbender/pull/1538)

New console command `mapbender:normalize-translations` to quickly find and complement missing translations.
It does three things:

- sort all translation keys in all bundles in the *Mapbender* and *FOM* namespace in the same order as in the original translation file (usually English)
- show keys that are present in the translated file but not in the original translation file
- show (option `-p`) or automatically add (`-a`) keys that are not yet translated. Automatically added keys use the original translation marked with a configurable prefix (`--missing-key-prefix`, default: "TRANSLATE")

#### Sample usage

Show (print to console) all missing German translations:

```bash
bin/console mapbender:normalize-translations -p de
```

Automatically add all missing Spanish translations to the yaml files:

```bash
bin/console mapbender:normalize-translations -a es
```

#### Sample output

```bash
Processing translation for Mapbender\CoreBundle  …
Found unexpected key "mb.core.simplesearch.admin.title.help" in translated file not present in original
Translations keys normalized for bundle Mapbender\CoreBundle. No keys were missing.
```

### [1521](https://github.com/mapbender/mapbender/pull/1521)

- Refer to the [UPGRADING.md](../UPGRADING.md#fontawesome:-updated-from-v4-to-v6) document to learn about the FontAwesome upgrade implemented in this pull request.

### [1517](https://github.com/mapbender/mapbender/pull/1517)

- Refer to the [UPGRADING.md](../UPGRADING.md#removed-underscore.js) document to learn about the removal of underscore.js implemented in this pull request.

### [1512](https://github.com/mapbender/mapbender/pull/1512)

#### Overriding JavaScript and CSS/Sass Resources

##### Option 1: use the ApplicationAssetService class

- Inject the Service `mapbender.application_asset.service` into your class, e.g. within your bundle file's boot method using `$this->container->get('mapbender.application_asset.service')` or using constructor injection in any PHP file: `<argument type="service" id="mapbender.application_asset.service" />`. If using the latter approach, make sure you use a file that will be called, e.g. the Template
- Call `ApplicationAssetService::registerAssetOverride` or `ApplicationAssetService::registerAssetOverrides` to mark assets for replacement. For example:

```php
class MyBundle extends Bundle
{
    [ ... ]

    public function boot(): void
    {
        parent::boot();
        $assetService = $this->container->get('mapbender.application_asset.service');
        $assetService->registerAssetOverride('@MapbenderCoreBundle/Resources/public/sass/element/button.scss', '@MyBundle/Resources/public/element/my_button.scss');

        $assetService->registerAssetOverrides([
            '@MapbenderCoreBundle/Resources/public/sass/element/button.scss' => '@MyBundle/Resources/public/sass/element/my_button.scss',
            '@MapbenderCoreBundle/Resources/public/js/element/button.js' => '@MyBundle/Resources/public/js/element/my_button.js',
        ]);
    }
}
```

##### Option 2: use the configuration

Within your `parameters.yaml` file, add the following:

```yml
mapbender.asset_overrides:
    "@MapbenderCoreBundle/Resources/public/sass/element/featureinfo.scss": "@@MyBundle/Resources/public/sass/element/custom_featureinfo.scss"
```

> [NOTE!]
> Note that the `@` sign in the replacement key needs to be escaped by another `@@` sign, otherwise Symfony tries (and fails) to resolve the file as a service.

#### Overriding Templates

Templates within bundles can be overridden by placing a twig file with the same name in `templates/bundles/<bundlename>`. If, for example, you want to customise the coordinates display (original source: *Resources/views/Element/coordinatesdisplay.html.twig* within the Mapbender CoreBundle, place a replacement file in *templates/bundles/MapbenderCoreBundle/Element/coordinatesdisplay.html.twig*. The new file will be used instead of the original one. See the [Symfony documentation](https://symfony.com/doc/5.4/bundles/override.html) for more details.

#### Misc

- Removed deprecated automatic bundle inference. Assets now always have to be imported using a bundle qualifier (e.g. `@MyBundle/Resources/public/file.js`)
- Smaller improvements like adding type hints within ApplicationAssetService

### [1504](https://github.com/mapbender/mapbender/pull/1504)

- Refer to the [UPGRADING.md](../UPGRADING.md#symfony-updated-to-version-5.4-lts) document to learn about the Symfony update implemented in this pull request.

### [1483](https://github.com/mapbender/mapbender/pull/1483)

- Refer to the [UPGRADING.md](../UPGRADING.md#removed-openlayers-2-support) document to learn about the removal of OpenLayers 2 and method renaming.

### [1481](https://github.com/mapbender/mapbender/pull/1481)

#### Added new parameter `mapbender.markup_cache.class`

Default: `Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupCache`.
FQCN of the *MarkupCache* class. Change if you want to customise the class that is responsible for caching the markup of frontend applications

#### Added new parameter `mapbender.markup_cache.include_session_id`

`default:false`: The default markup cache caches an application based on application slug, locale, map engine code and element id that are visible to the user. This means however, that two people with the same rights will be delivdered the same markup. Usually that's fine, if however you display user-specific information, like their email address, in the frontend, set this new parameter to true to avoid them receiving the same markup. Note that for each user and application a file will be created on the server. Consider your application logic if you have a lot of users.

### [1468](https://github.com/mapbender/mapbender/pull/1468)

To greatly improve debugging experience in Mapbender, the generated js and css files in dev mode will now provide a source map. The debug markers are removed in the process, finding them in a 5 MB, >100.000 lines file was not convenient anyway.

Limitations:

- does only work in local installations since the source files are not publicly exposed
- works in Chrome, not flawlessly in Firefox though. The file protocol is weakly supported there.

### [1461](https://github.com/mapbender/mapbender/pull/1461)

Mapbender 3.2.5 removed properties from *WmsBundle/Component/Style* ([see commit](https://github.com/mapbender/mapbender/commit/f318cc6611ecfdfa6a036e6ba76d77d512ba3b2e)). The style is stored serialised in the database. PHP 8.2 does not ignore unknown properties like previous version but issues a deprecated message that Symfony converts into an error (see [PHP changelog](https://www.php.net/manual/en/migration82.deprecated.php#migration82.deprecated.core.dynamic-properties)).

As a workaround, overwrite `__unserialize` and check for known properties ignoring the old ones.  

### [1458](https://github.com/mapbender/mapbender/pull/1458)

- Add CSRF Protection tokens to forms and calls that modify content and were not yet protected by the Symfony forms system
- Add access control checks to various calls in *ElementController*
- Correctly show/hide `No instance added` notice in layerset configuration

> [!NOTE]
> Also modified all files where there were still windows style line endings (`\r\n` instead of `\n`). Select the `hide whitespace changes` option in the *Files changed* tab to ignore those changed from being displayed.

### [1457](https://github.com/mapbender/mapbender/pull/1457)

- Add *jquery-ui-touch-punch* library for touch support

### [1453](https://github.com/mapbender/mapbender/pull/1453)

- Add thousand separators in all places within the two elements (using JS's [toLocaleString](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/toLocaleString) and PHP's [NumberFormatter](https://www.php.net/manual/de/numberformatter.create.php))

### [1450](https://github.com/mapbender/mapbender/pull/1450)

- Add twig template option `dropdown_elements_html` which, when set, won't encode dropdown elements

### [1436](https://github.com/mapbender/mapbender/pull/1436)

Adds a global *userinfo.json* url to help with user-specific client-side logic.

Adds a client-side `Mapbender.loadUserInfo` method (returns a Promise-like) that will reuse the response if already fetched.

Default implementation returns a `name` (string or null for anons only), `roles` (list of strings) and a convenient `isAnonymous` boolean.

```js
Mapbender.loadUserInfo().then(function(info) {
  console.log("User info received", info);
});
```

For a fully authenticated user, the promise resolves with something like this:

```json
{
    "name": "root",
    "roles": [
        "ROLE_GROUP_TESTING-GROUP",
        "ROLE_USER"
    ],
    "isAnonymous": false
}
```

For anons, expect this:

```json
{
    "name": null,
    "roles": [],
    "isAnonymous": true
}
```

### [1434](https://github.com/mapbender/mapbender/pull/1434)

Adds infrastructure to extend/override markup for icons assignable to buttons.

Icon choice for control buttons no longer appear 1:1 in markup as the (single) css class on a `<span>` tag. Instead the icon choice is interpreted by handling package, which generates the markup via the new twig extension function `icon_markup`.

This allows project-level icon customizations to generate arbitrary markup for any icon (especially, but not limited to, proper `<img src="...">` tags, and tags with more than just one css class).

Decouples a few rermaining misc other element icon usages (layer tree, navigation bar) from button-assignable icons. These have been replaced with native Font Awesome markup.

Predefines two packages: one for Font Awesome 4 glyphs (mostly forward compatible with Font Awesome 5) and one for the [mapbender/icons package](https://github.com/mapbender/icons) glyphs. These two together support all preexisting button-assignable icons, so existing applications should see no apparent change.

Icon packages are defined by tagging a service with `mapbender.icon_package`.

```xml
<service id="mapbender.icon_package_fa4" class="Mapbender\CoreBundle\Component\IconPackageFa4">
    <tag name="mapbender.icon_package" priority="-1" />
</service>
```

The `priority` attribute on the service tag determines icon handling order. The included packages have priority below zero, so any project-defined icon package will be first in line to produce icons by default, regardless of bundle registration order.

Packages may list extra stylesheets. These must be plain CSS (no compilation) and can use stylesheet-relative font or image references freely. Stylesheets for all defined packages are loaded into every page.

The markup produced by icon packages is expected to be inline friendly, i.e. it might appear inside any piece of text and respect the local font size and line height, just like e.g. native Font Awesome markup.

### [1424](https://github.com/mapbender/mapbender/pull/1424)

Application template classes in Mapbender are PHP classes determining the region structure, region-specific form types, twig skins for frontend rendering and so forth.

Changes in this pull replace and extend the (circa 2013) template registration mechanism.

Template classes can now be declared as services tagged with `mapbender.application_template`.
The tag can optionally carry a `replaces` attribute. This allows replacing a shipping mapbender template completely with a project-specific class. A `priority` attribute can also be set to resolve bundle loading order conflicts.

```xml
<service id="project.application_template.custom_fullscreen"
         class="ProjectBundle\CustomFullscreen">
    <tag name="mapbender.application_template" replaces="Mapbender\CoreBundle\Template\Fullscreen" />
</services>
```

Previously, replacing the template class required manual database manipulation. After altering the database contents, the affected applications would no longer work if the bundle defining the newly set template class was deactivated for testing or any other purpose.

After these changes, developers can

1) modify the design of many applications at once only by registering a new template tagged to replace another
2) somewhat safely deactivate a bundle declaring a custom PHP template class, to make live comparisons with the pre-customization state

Finally, if a currently undefined template class is loaded from the application table, Mapbender will now quietly fall back to the default fullscreen template instead of throwing a server error.

### [1393](https://github.com/mapbender/mapbender/pull/1393)

Adds a new frontend Template method `getSassVariablesAssets` to simplify customization of colors / fonts etc in project specific templates.

Replaces Sass-level `@import` of the files defining these variables.

With this change, it is sufficient to declare new Sass variables to e.g. customize button colors. It is no longer necessary to re-include or copy&paste the entire button css again.

### [1368](https://github.com/mapbender/mapbender/pull/1368)

First round of Element rewrites onto [new Symfony 4-compatible Element infrastructure](https://github.com/mapbender/mapbender/pull/1367).

This touches the following Element classes on the PHP side:

- CoreBundle\Element\Map
- CoreBundle\Element\HTMLElement
- CoreBundle\Element\ActivityIndicator
- CoreBundle\Element\CoordinatesDisplay
- CoreBundle\Element\ZoomBar
- CoreBundle\Element\ScaleSelector
- CoreBundle\Element\ScaleDisplay
- CoreBundle\Element\ScaleBar
- CoreBundle\Element\FeatureInfo
- CoreBundle\Element\POI
- CoreBundle\Element\ApplicationSwitcher
- CoreBundle\Element\ViewManager
- All ~button-likes:
  - CoreBundle\Element\ControlButton
  - CoreBundle\Element\LinkButton
  - CoreBundle\Element\AboutDialog
  - CoreBundle\Element\GpsPosition
  - CoreBundle\Element\ShareUrl
- WmsBundle\WmsLoader
- WmsBundle\DimensionsHandler

This will necessarily break any project-level customizations of these Elements, due to the change in base class. This is unavoidable and must be gotten out of the way.

Refer to the pull request below for pointers on adapting to the new infrastructure.

### [1367](https://github.com/mapbender/mapbender/pull/1367)

Mapbender Elements inheriting from *Mapbender\CoreBundle\Component\Element* will inherently be incompatible with Symfony 4.

This pull adds new infrastructure to allow writing Elements that will work on Symfony 4.

Conformant Element classes must implement Mapbender\Component\Element\ElementServiceInterface (alternatively extend Mapbender\Component\Element\AbstractElementService, which implements the interface already).

Conformant Element classes must be registered as a service and tagged with `mapbender.element`. Use [standard Symfony DI](https://symfony.com/doc/4.4/service_container.html#service-parameters) to pass services / global configuration parameters into the constructor. E.g.:

```xml
        <service id="mapbender.element.main_map" class="Mapbender\CoreBundle\Element\Map">
            <tag name="mapbender.element" />
            <argument type="service" id="doctrine" />
        </service>
```

> [!NOTE]
> Omitting the id is an error on Symfony 4.  
> Also, do not attempt injecting the full container as `service.container`. This is also an error.

Redundantly naming the class name of a tagged Element service in the return value of a MapbenderBundle::getElements method is discouraged.

#### API comparison

Element services retain some of the static API from legacy Component\Element. Namely the static methods `getClassTitle`, `getClassDescription`, `getDefaultConfiguration`, `getFormTemplate`, `getType` (=backend form type FQCN).

Non-static method `getWidgetName` now receives the Element entity as the first argument (same name).

Non-static method `getRequiredAssets` (renamed for clarity / signature sanity) receives the Element entity as the first argument, and functionally replaces both Component\Element::getAssets and (super legacy) static listAssets.

> [!NOTE]
> Service-type elements do not support legacy automatic bundle name amending ('file.js' => '@MagicallyInflectedBundle/Resources/public/file.js') for their asset requirements. References to required assets must be returned in properly qualified form. Magic bundle scope inflection of assets has been deprecated since Mapbender v3.0.8-beta1.

Non-static method `getClientConfiguration` (renamed for clarity / signature sanity) receives the Element entity as the first argument, and functionally replaces Component\Element::getPublicConfiguration

Non-static method `getView` receives the Element entity as the first argument, and must return either a StaticView ([empty or trivially prerendered content](https://github.com/mapbender/mapbender/pull/1368/commits/b394d233d03b8c770e5a66268845d38feadcb401#diff-1e17ace4a0eefe8ede2f71066a44444dec3fcaf3ad85d43a8d889a354dc9ad9bR77)) *or* a TemplateView, *or* a falsy PHP value. This functionally replaces Component\Element's `getFrontendTemplatePath`, `getFrontendTemplateVars` and `render` methods.

There are no longer any "utility methods" (getTitle, getId, getEntity, getConfiguration, getMapEngineCode). The Element entity is universally available as an argument.

#### Frontend markup rendering changes

Service-type Elements are rendered by the system, according to what they return from `getView`. Accessing the (twig) templating engine from inside the Element implementation is discouraged.

Service-type Element frontend templates *should* drop wrapping `<div id="..." class="mb-element ..." ...` tags. The outer tag is generated as appropriate for the enclosing region. E.g. `<li id="..." class="mb-element toolBarItem">` is generated in footers / headers; divs elsewhere.
ElementView has a public `$attributes` array property that *should* be used to add any additional required attributes (e.g. `title` for tooltips, `class` to tie in extra CSS rules).  

> [!NOTE]
> Note that the `id` attribute and class="mb-element" are added automatically, and *should* *not* be respecified.

There is no longer any predetermined set of variables injected into templates. Any variables required to render the Element markup via (twig) template must be explicitly placed into the TemplateView's `$variables` (public array property).

The `getView` method *may* return false to suppress frontend markup entirely. This is useful for Elements that, after dynamically inspecting configuration / Application circumstances, cannot reasonably function and should not render at all (e.g. control buttons with disabled target Elements; ViewManager with grants settings that disallow any interaction for the current user).

#### Http handling changes

Http request handling is frequently the most complex part of any Element, with many service / parameter dependencies. To reduce common initialization overhead, http handling is now inflected via the `getHttpHandler` method. `getHttpHandler` should return the (DI'ed) http handling service or a falsy PHP value if no requests are handled.

The handler must implement `ElementHttpHandlerInterface`, requiring a `handleRequest` method. `handleRequest` receives the Element entity and the (Symfony) Request object as arguments.

NOTE that the passed Request object is the canonical current request passed down from the Symfony controller. Element services *should* *not* attempt accessing the `request_stack` themselves.
NOTE that (in simple scenarios) an Element service *may* `return $this;` from `getHttpHandler`, as long as it implements the `ElementHttpHandlerInterface` itself (example: [AboutDialog rewrite](https://github.com/mapbender/mapbender/pull/1368/commits/7895fd1165e5dbd63eab7e3b79656be01d588f88#diff-7e3c0b3b64f57eaa69e894c85141690e673bf93bf80076bf01a0a2f3f2b347f8R88)).

There is no longer any default http handler implementation. AbstractElementService returns null from `getHttpHandler`.

This functionally replaces the `handleHttpRequest` and (super legacy) `httpAction` methods on Component\Element.

#### Additional import processing

When cloning applications, or importing exported Applications, Elements that reference database objects in their configuration by id must adjust those ids (e.g. "layersets" are id references in the main map configuration; see [main Map rewrite](https://github.com/mapbender/mapbender/pull/1368/commits/f4811aee9cad1bb4a31d4b0644312106208250cd#diff-e39fd59ab999b73f9b81d43fb84521ca3f4e743a83bdbe1fab52edac782cbd81R190)).

Affected Element services must implement the new ImportAware interface. This requires a method `onImport`. `onImport` receives the Element entity and a Mapper implementation as arguments.

This functionally replaces Component\Element's `denormalizeConfiguration` method.

#### Declaring Element replacements

The `<tag name="mapbender.element" />` allows a `replaces` property, which must, if present, contain (a comma-separated list of) previous Element class FQCN(s).

This will indicate that the Element service will handle those other class names. This allows replacing Element legacy / undesired default, or even no longer existant Element implementations, that will only take effect if the defining configuration (=most likely the bundle containing it) is currently loaded.

E.g.

```xml
        <service id="project.element.print" class="ProjectBundle\Element\Print">
            <tag name="mapbender.element" 
              replaces="Mapbender\PrintBundle\Element\PrintClient,Mapbender\CoreBundle\Element\PrintClient">
            <!--- arguments ... -->
        </service>
```

### Explicitly declaring canonical class name

To maintain compatibility with existing db contents ("class" column in `mb_core_element`), a "canonical" class name is set when adding or editing Elements inside an Application. This avoids hard errors after deactivating a bundle with a reimplemented Element class that changes the handling class name.

By default, the canonical class name is the first entry in the `mapbender.element` tag's `replaces` attribute if `replaces` is specified, otherwise it is the FQCN of the handling service class itself.

You *may* explicitly specify the canonical content of the `mb_core_element` table's "class" column. Canonical can be any string value (must not necessarily name an existing PHP class).

```xml
        <service id="mapbender.element.digitizer_testing" class="Mapbender\Digitizer\Element\DigitizerService">
            <tag name="mapbender.element" 
              canonical="Mapbender\DigitizerBundle\Element\Digitizer">
            <!--- arguments ... -->
        </service>
```

IOW, "replaces" maps the value in the `mb_core_element` "class" column to the PHP class implementing the Element logic, while "canonical" controls what is written into the `mb_core_element` "class" column.

[↑ Back to top](#git-archive)

[← Back to README](../README.md)

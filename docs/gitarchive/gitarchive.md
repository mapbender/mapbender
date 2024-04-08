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

> [!NOTE]
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

Adds a new frontend Template method `getSassVariablesAssets` to simplify customization of colors/fonts etc in project specific templates.

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

Conformant Element classes must be registered as a service and tagged with `mapbender.element`. Use [standard Symfony DI](https://symfony.com/doc/4.4/service_container.html#service-parameters) to pass services/global configuration parameters into the constructor. E.g.:

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

Non-static method `getRequiredAssets` (renamed for clarity/signature sanity) receives the Element entity as the first argument, and functionally replaces both Component\Element::getAssets and (super legacy) static listAssets.

> [!NOTE]
> Service-type elements do not support legacy automatic bundle name amending ('file.js' => '@MagicallyInflectedBundle/Resources/public/file.js') for their asset requirements. References to required assets must be returned in properly qualified form. Magic bundle scope inflection of assets has been deprecated since Mapbender v3.0.8-beta1.

Non-static method `getClientConfiguration` (renamed for clarity/signature sanity) receives the Element entity as the first argument, and functionally replaces Component\Element::getPublicConfiguration

Non-static method `getView` receives the Element entity as the first argument, and must return either a StaticView ([empty or trivially prerendered content](https://github.com/mapbender/mapbender/pull/1368/commits/b394d233d03b8c770e5a66268845d38feadcb401#diff-1e17ace4a0eefe8ede2f71066a44444dec3fcaf3ad85d43a8d889a354dc9ad9bR77)) *or* a TemplateView, *or* a falsy PHP value. This functionally replaces Component\Element's `getFrontendTemplatePath`, `getFrontendTemplateVars` and `render` methods.

There are no longer any "utility methods" (getTitle, getId, getEntity, getConfiguration, getMapEngineCode). The Element entity is universally available as an argument.

#### Frontend markup rendering changes

Service-type Elements are rendered by the system, according to what they return from `getView`. Accessing the (twig) templating engine from inside the Element implementation is discouraged.

Service-type Element frontend templates *should* drop wrapping `<div id="..." class="mb-element ..." ...` tags. The outer tag is generated as appropriate for the enclosing region. E.g. `<li id="..." class="mb-element toolBarItem">` is generated in footers/headers; divs elsewhere.
ElementView has a public `$attributes` array property that *should* be used to add any additional required attributes (e.g. `title` for tooltips, `class` to tie in extra CSS rules).  

> [!NOTE]
> Note that the `id` attribute and class="mb-element" are added automatically, and *should* *not* be respecified.

There is no longer any predetermined set of variables injected into templates. Any variables required to render the Element markup via (twig) template must be explicitly placed into the TemplateView's `$variables` (public array property).

The `getView` method *may* return false to suppress frontend markup entirely. This is useful for Elements that, after dynamically inspecting configuration/Application circumstances, cannot reasonably function and should not render at all (e.g. control buttons with disabled target Elements; ViewManager with grants settings that disallow any interaction for the current user).

#### Http handling changes

Http request handling is frequently the most complex part of any Element, with many service/parameter dependencies. To reduce common initialization overhead, http handling is now inflected via the `getHttpHandler` method. `getHttpHandler` should return the (DI'ed) http handling service or a falsy PHP value if no requests are handled.

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

This will indicate that the Element service will handle those other class names. This allows replacing Element legacy/undesired default, or even no longer existant Element implementations, that will only take effect if the defining configuration (=most likely the bundle containing it) is currently loaded.

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

### [1352](https://github.com/mapbender/mapbender/pull/1352)

Adds a Controller to serve `/components/` urls, to allow safe removal of e.g. (abandoned) [robloach/component-installer](https://packagist.org/packages/robloach/component-installer). Note that if a file actually exists inside `web/components`, the web server will handle the request internally. PHP will not be invoked, and this Controller will not be invoked.  
Only files not present in web/components will invoke this Controller. Contents will be served from an appropriate location inside `/vendor/`. Browser caching is supported.

Currently targets 5 packages of common interest for delivery:

- debugteam/bootstrap-colorpicker
- wheregroup/open-sans
- mapbender/mapbender-icons
- components/font-awesome
- components/bootstrap

Other packages with a "component" vendor may or may not work. This depends on if/how the file structure inside vendor is altered when a "component installer" copies files into `web/components`.

> [!TIP]
> Though not strictly necessary, for optimal performance the firewall should be bypassed on the `/components/` url prefix. This requires an extra url exclusion configuration in Mapbender Starter. See [4c362e3](https://github.com/mapbender/mapbender-starter/commit/4c362e35520bb0774a16d31c7727052cd6de2655).

### [1343](https://github.com/mapbender/mapbender/pull/1343)

Replaces all checkboxes generated via form theme with the appropriate Bootstrap markup (template blocks imported from original Symfony Bootstrap form theme). Legacy custom checkbox widget remains in proper use in Layertree frontend only.

Default form layout is now block style, i.e. the label and widget are displayed on separate rows, both on full width. Layouts where labels and widgets should appear side by side must now wrap themselves inside a [Bootstrap `.form-horizontal`](https://getbootstrap.com/docs/3.4/css/#forms-horizontal) container.

### [1317](https://github.com/mapbender/mapbender/pull/1317)

Allows suppressing Element types from showing up in frontend and backend via configuration.

This mechanism can fix errors when rendering or editing an application that holds references to Elements that are outdated, unsafe, or completely gone from the code base. In turn, it allows to safely remove types of Elements from the code entirely without causing hard errors. This is also useful to prevent operators from adding Elements that are no longer safe to use, or that have been superseded by project-level solutions.

Adds a new configuration parameter `mapbender.disabled_elements` (list of strings, default empty). Contained element types are fully qualified class names of the element component.

Example usage (suppresses all Layertree Elements in all applications).

```yaml
parameters:
    mapbender.disabled_elements:
        - Mapbender\CoreBundle\Element\Layertree
```

This suppression via configuration has no permanent effects on the persisted structure of the application. I.e., if you later change your mind and remove the `Layertree` entry again, all previously suppressed Layertree Elements will pop back into existence.

Button/ControlButton Elements targetting a disabled type of Element will also be suppressed.

### [1306](https://github.com/mapbender/mapbender/pull/1306)

Resolves [vis-ui.js](https://github.com/mapbender/vis-ui.js) dependency on abandoned robloach/component-installer transparently via Mapbender asset integration.

Extends previous asset reference rewriting infrastructure from hard 1:1 remapping to allow expansion of single-file references to lists of individual files.
This is then used to expand vis-ui.js-built.js reference to a list of references to its individual source files. All vis-ui.js source files are read directly from vendor/mapbender/vis-ui.js/src.

Deduplication is extended to fully support mixed references to individual files + reference(s) to the ...-built.js version.

Adds lenience to ignore missing asset file references that resolved to the /vendor/ path. Exceptions for missing files in /vendor/ are suppressed.

As a result,

1) vis-ui script can now be required by Element and Template classes with or without robloach/component-installer present
2) vis-ui script dependencies now properly deduplicate even when total application requirements reference a mix of individual files and the legacy ...-built.js
3) vis-ui script dependencies from (custom) Template and Element classes can now safely be changed to reference individual vis-ui.js source files, without requiring a synchronized multi-repository effort
4) Highly deprecated individual vis-ui.js script components (StringHelper, DataUtil) can gradually be removed without breaking Mapbender asset infrastructure

As a collateral, the informational comments produced in /assets/js and /assets/css routes in the dev environment have been extended to include more detailed deduplication and expansion information, and will notify about ignored missing files.

### [1297](https://github.com/mapbender/mapbender/pull/1297)

Extracts view access control checks for elements into two new [voter](https://symfony.com/doc/3.4/security/voters.html#the-voter-interface) services, `mb_core.security.voter.db_application_element` and `mb_core.security.voter.yaml_application_element`, making grants checks globally reusable and customizable via DI.

Grants checks produce the same results as before, but are significantly faster due to the separation between Yaml-defined and database applications. Grants checks in database applications now use bulk-prefetching of element ACLs, which has been observed to improve overall Element VIEW grants check time by up to 10x for a typical application.

Yaml-defined applications completely skip the attempt to look up (unassignable) object ACLs, which also saves some performance.

### [1296](https://github.com/mapbender/mapbender/pull/1296)

Extracts two new voter services `mb_core.security.voter.yaml_application` and `mb_core.security.voter.db_application`. This extraction

1) makes the application view grants checks available throughout the system (previously directly built into certain controllers) and
2) makes it customizable on a project level via DI.

### [1294](https://github.com/mapbender/mapbender/pull/1294)

Splits Button Element into LinkButton and ControlButton. Leaves Button class in place for existing project-level child classes, but no longer allows it to be used directly in applications. Existing applications will have their button elements automatically ported to either LinkButton (if they have a "click" setting) or ControlButton.

When adding a new Button element to a database application, the "Button" element can no longer accept a "click" setting. Select the "Link" element instead to create links.

As a collateral the nondescript element description texts have been updated from "Button" to "Controls another element" (ControlButton) and "Link to external URL" (LinkButton) respectively.
The field label for "click" has also been updated to read "Target URL".

It is no longer possible to assign deprecated and unnecessary `action` and `deactivate` settings. All controllable JavaScript widgets must implement deactivate and activate methods. Legacy aliasing support of activate to defaultAction remains in place. All controllable elements currently shipped with Mapbender implement this API correctly.

This fixes [Issue 571](https://github.com/mapbender/mapbender/issues/571).

#### Rationale

Separating the quite ControlButton concept from misc other Button class use cases removes headaches in developing QoL/design advancements in its intended, central function of controlling other elements. E.g. after this separation it will be much easier to:

- actually require a target Element and check for its presence (previously not required because it might just have been a link)
- automatically provide appropriate default label/tooltip depending on target element, and resolve related translation issues
- automatically suppress button markup if its target is missing (e.g. after Element grants filtering)
- automatically suppress of button markup it its target is generally uncontrollable/not currently controllable by a button (e.g. if the target is placed in a sidepane)
- automatically reform toolbar contents into compact menus in certain ongoing frontend templating concepts

### [1291](https://github.com/mapbender/mapbender/pull/1291)

Automatically decorates frontend application url with a url hash/fragment that encodes the current view parameters center, scale, rotation and CRS. Taking this url to a new browser tab will restore this view. Browser back/forward buttons can now undo/redo map navigation steps, including SrsSwitcher interactions.

This trivially allows **mobile browser share** functionality (which operates on the url), or showing a **specific view of the map** to another user simply by sending the complete url via email/chat or any other text-capable system.

There is no extra configuration for this functionality. It is currently always on.

This means hitting F5 to reload a Mapbender application will now send you back to the same part of the map. It will *not* send you to the configured initial map view (defined by Map element start extent), as it did before. To return to the configured map start extent, you must now either open the application again from the application list, or manually cut off the hash of the application url.

We considered the impact of this effect on application reloading via F5. Supporting previous expectation of returning to configured map start extent on reloading a browser tab would have required either an explicit "Reset view" helper Element, an Application-level off switch, or both.
We internally agreed that this is not a significant downside, and working around it is not immediately necessary. We will continue to monitor project demand, but for now, we will integrate the new feature without a guided "Reset view" helper.

> [!NOTE]
> Integrating this closes the loss of ZoomBar history navigation, which had no working implementation on Openlayers 4/6, and was removed in Mapbender 3.2. It also closes the loss of the SuggestMap, WmcEditor/WmcLoader and WmcList elements, as far as sharing the pure view parameters is concerned.

Great, what's the catch?

> [!IMPORTANT]
> The shared map view exclusively encompasses the center, scale, rotation, and CRS. It does not incorporate alterations in layer selection or sorting, runtime additions of sources via WmsLoader, or even currently visible geometric features.

### [1219](https://github.com/mapbender/mapbender/pull/1219)

Replaces sassc compiler dependency with [wheregroup/assetic-filter-sassc](https://packagist.org/packages/wheregroup/assetic-filter-sassc) plus [wheregroup/sassc-binaries](https://packagist.org/packages/wheregroup/sassc-binaries).

Allows using native sassc, e.g. available on Debian/Ubuntu via `apt get`. Set the path to your desired binary via parameter `mapbender.asset.sassc_binary_path`, usually to `/usr/bin/sassc`. This disables autopicking of the binary from one of the bundled ones.

> [!IMPORTANT]
> On Linux, outside of prod, results are identical (as per md5sum). This change needs verification on Windows and MacOS.  
The compiler is also used to validate the Application CSS form field in the backend. This should be reverified with/without deliberate errors in the CSS input.

### [1208](https://github.com/mapbender/mapbender/pull/1208)

JavaScript translations now support inputs beyond `.json.twig`, reducing the risk of errors caused by maintenance and translation key mapping mismatches.

This pull adds optional support for `Element::getAssets` and `Template::getAssets` to include in their return value list for `trans` assets:

1) direct translation keys, e.g. `mb.core.featureinfo.error.nolayer`
2) translation key prefixes ending in a `*` wildcard, e.g. `mb.core.featureinfo.*` or even `mb.core.f*`

Wildcard prefixes expand to a list of every available translation where the key starts with that prefix. This allows extending available messages for Elements simply by adding more messages with the appropriate prefix to a catalog file (*messages.en.yml* and other relevant files in any activated bundle).

> [!CAUTION]
> A direct key translation causes an error if the result is the same as the input to detect untranslatable messages.

### [1158](https://github.com/mapbender/mapbender/pull/1158)

Adds machinations to emit configurable site links to the backend and login layouts.  
Site links are configured by setting the `mapbender.sitelinks` parameter. An expected value is a collection of items with `link` and `text` keys. E.g. you could put the following into *parameters.yml*:

```yaml
     mapbender.sitelinks:
       - link: https://some-domain.org/
         text: External absolute link
       - link: relative-path/something.png
         text: Relative link under application/web
       - link: /absolute-path/something-else.html
         text: Absolute link to something on the same host
```

Site link item `text` values are piped through the twig translation filter to support localization.

By default, there are no site links configured. This machinery can be used to link to existing imprint and site meta information pages.

#### Customization impact

The implementation takes the LoginController and the relevant base templates from FOM into Mapbender, similar to the [recent adoption of the manager.html.twig template](https://github.com/mapbender/mapbender/pull/1120). This may cause some surprises with customization to the login template, or its outer box template.

If present, the following drop-in template replacements (in `app/resources`) will need to be reviewed and copied to the new paths:

- `FOMUserBundle/views/Login/box.html.twig` => `MapbenderCoreBundle/views/Login/box.html.twig`
- `FOMUserBundle/views/Login/login.html.twig` => `MapbenderCoreBundle/views/Login/login.html.twig`

### [1146](https://github.com/mapbender/mapbender/pull/1146)

Adds `Mapbender.Model.zoomToFeature`, with support for buffering and scale limits.  
Replaces (near identical) zoom-to-feature logic in SearchRouter, SimpleSearch and Redlining/Sketch with a call to the new method. This should ease porting to new map engines down the road.

### [1140](https://github.com/mapbender/mapbender/pull/1140)

Update **JavaScript-side `Mapbender.Util.Url` processing** to no longer rely on OpenLayers 2 utility methods, and resolves some collaterals.

1) Avoid undesired special treatment of get parameters that are comma-separated lists, inherited from OpenLayers 2 method. All extracted parameters are now scalars. No known usage of `Mapbender.Util.Url` expects or handles Array-style parameters.

2) Decode username/password and all parameters on parsing, reencode on reconstruction. This should fix any issues where these properties are modified between parsing and reconstruction, such as WmsLoader adding username and password, then reconstructing the url.

3) Fix loss of parameters-in-parameters on parsing + reconstruction. E.g. the `url` parameter in Owsproxy-style URLs bares an internal `_sginature` parameter, which would previously not survive `Util.Url` processing. Now it does.

4) Repeated or nested sequences of `(new Mapbender.Util.Url(someUrl)).asString()` are now idempotent. The first reconstruction may produce small inconsequential deviations (such as escaped forward slashes in query parameters where that is not strictly required). Every further repeat of the sequence, and every nesting depth (`(new Mapbender.Util.Url((new Mapbender.Util.Url(someUrl)).asString())).asString()`) now yields the same result as the input.

5) Add RFC 1738-conformant support for empty-valued query parameters (`scheme://host/?hat&cat&quaternion`)

6) Fix quadruple slash `file:////something` generated when reconstructing a parsed file-scheme URL.

### [1122](https://github.com/mapbender/mapbender/pull/1122)

**HTMLElement** was used as a stomping ground for certain developments before vis-ui.js and data-source were spun off. It has accumulated a lot of logic that is only really relevant for Digitizer and certain other data-sourcey Mapbender Elements.

[Data Source 0.1.11](https://github.com/mapbender/data-source/releases/tag/0.1.11) has everything it needs to keep even quite old Digitizer versions running happily, with no more need for any supporting magic in HTMLElement. In fact, as of 0.1.11, DataSource's BaseElement doesn't inherit from HTMLElement anymore.

As such, it is now safe to restore HTMLElement to its original purpose: a thing that renders a piece of markup.

#### Removals

This pull removes data-sourcey configuration preprocessing magic prepareItem, prepareItems, isAssoc.

This pull removes the Ajax entry point handler (single action `configuration`).

This pull removes processing logic for `jsSrc` and `css` options. These have never actually been configurable. Through the entire history of the repository, the HTMLElement form type has never defined form fields with these names. We can only speculate that a child class or two may have defined them, but never HTMLElement itself. [UPGRADING.md](https://github.com/mapbender/mapbender/blob/93d6458fe6ff56931fa3b96f5c84658762793c4b/UPGRADING.md#htmlelement-inheritance) now has instructions on what to do in these cases.

#### Other changes

This pull restores

- Explicit `getType` implementation (backend form type class)
- Explicit `getFormTemplate` implementation (backend form twig)

This pull changes the rendering logic for `html-element-inline` to emit a span instead of a div. This resolved certain CSS gotchas, and actually allowed the complete removal of the HTMLElement CSS rules.

The remaining HTMLElement only renders the markup defined in its `content` option, which may also use Twig functionality. It doesn't have a JavaScript widget constructor, and it doesn't have any predefined CSS rules.

### [1120](https://github.com/mapbender/mapbender/pull/1120)

#### BC impact

Drop-replacements for manager.html.twig (in app/Resources) will have to be moved (or safer yet, duplicated) to a new directory structure to remain effective.

- A customized `app/Resources/FOM/ManagerBundle/Resources/views/manager.html.twig` should be duplicated to `app/Resources/Mapbender/ManagerBundle/Resources/views/manager.html.twig`

- A customized `app/Resources/FOM/CoreBundle/Resources/views/Form/fields.html.twig` should be duplicated to `app/Resources/Mapbender/CoreBundle/Resources/views/form/fields.html.twig`

- A customized `app/Resources/FOM/CoreBundle/Resources/views/Manager/menu.html.` should be duplicated to `app/Resources/Mapbender/ManagerBundle/Resources/views/menu.html.twig`

Twig `extends` and `include` clauses for manager.html.twig will be kept safe and working. The other two templates `fields.html.twig` and `menu.html.twig` will vanish completely from their original locations.

#### Change summary

This brings in the following assets from FOM v3.0.6.3:

- fields.html.twig (the default form theme)
- manager.html.twig (the backend layout)
- menu.html.twig (skin for the backend sidepane menu)
- checkbox.js
- dropdown.js
- radiobuttonExtended.js
- collection.js
- components.js
- tabcontainer.js
- sidepane.js

It also brings in the `FOM\ManagerBundle\ManagerController`, renamed to `Mapbender\ManagerBundle\IndexController`.

##### Updating of references

There is generally no need to manually update anything on the project level, except for cases of drop-in twig replacements already described on top.

Includes of the twigs in Mapbender scope have been updated manually.

The form theme is carefully auto-rewritten based on [previous work](https://github.com/mapbender/mapbender/pull/1030). This change of default theme location only happens if the configured default theme was FOM's.  
Enterprising types who have already configured their system with, say, a Bootstrap 3 form theme, will not be impacted in any way.
Doing this automatically frees us from attempting a "simultaneous" commit into the Mapbender Starter repository.

References to the JavaScript assets are auto-updated on the AssetFactory level. This ensures custom Element and Template classes will move along automatically, with no need to rewrite the asset references.

Standard Mapbender routing configuration loads Mapbender ManagerBundle controllers before FOM ManagerBundle controllers, so the adopted new IndexController will take precedence automatically.

This change can coexist with older FOM versions. They will continue to wrap the "Users", "Groups" and "ACLs" sections in their their own backend twigs (general layout skeleton and menu). Mapbender's version of the form theme (whichever way it will develop from here) will take effect though.

##### Rationale for this change

1) Nothing in FOM even references any of the JavaScript assets. They are only referenced by the manager template *in Mapbender*, frontend Templates also *in Mapbender* and assorted Elements, of which FOM has no concept at all. As such, it makes no sense that FOM should control how these widgets work.
2) FOM only has a tiny bit of backend section of its own, for users and ACLs. The bulk of the backend is in Mapbender's ManagerBundle. As such it makes no sense that FOM should control the layout of the Mapbender backend.
3) FOM's form theme has numerous limitations and quirks that can only reasonably be fixed in conjunction with its CSS, which is in Mapbender, along with the vast majority of form types and form templates.

### [1116](https://github.com/mapbender/mapbender/pull/1116)

Reintegrates [the external Wmts bundle](https://github.com/mapbender/wmts) with Mapbender to allow ongoing architectural work on source and source instance handling to proceed in a unified fashion.

Side-by-side installation of the external bundle with this branch is not possible. This is a full displacement, using the same PHP namespace. An appropriate package conflict rule will be added to prevent simultaneous installation.
Some of the documented limitations and quirks of the external bundle have been resolved, but enough remain that this is still an experimental feature that we cannot reasonably enable by default.

### Known limitations/quirks

- Only a single layer can be enabled per WMTS/TMS instance. To get different layers from the source to display, additional instances need to be added to the same application
- WMTS/TMS sources cannot be "opened" in the Layertree, they are either on or off with no further control possible
- There is currently no support for Metadata via Layertree sub-menu.
- There is currently no support for FeatureInfo
- There is currently no support for vendorspecifics or dimensions
- What appears as the root layer in the instance configuration will fail to save its various checkbox settings, most notably the initially enabled state of the source. This means that WMTS/TMS instances added to an application will always start out enabled.
- WMTS/TMS sources will magically self-disable when switching to an incompatible SRS. Layertree checkboxes will uncheck themselves. Depending on current SRS, Layertree visual may become inconsistent when you click on the source
- There is no integration with any WmcBundle functionality (SuggestMap, WmcLoader, WmcEditor et al). Having active WMTS or TMS instances in the same application as these Elements may lead to incomplete states getting saved, generation of states that are incompatible with other applications, and other errors.
- There is no mechanism to add WMTS or TMS sources to YAML-defined Applications. Only database Applications can have them.
- WMTS and TMS sources cannot be reloaded. To effectively "update" them, you will need to delete it, load it as a new source, and create new instances.

### Resolved limitations (vs [external bundle documentation](https://github.com/mapbender/wmts/blob/master/README.md))

- There are no restrictions on the Map Element's dpi and scales settings. As with any fixed raster source, do note however that going off the "ideal" values may induce tile rescaling, and thus reduce visual quality.
- There are no restrictions on the Map Element's max extent
- There are no restrictions on the Map Element's initial `SRS` and switchable `Other SRS` setting
- There are no restrictions on the Map Element's tile size setting. Tile sizes for WMTS/TMS are predetermined by the source, are automatically selected as appropriate, and cannot be configured. The Map's tile size setting remains functional for WMS tiling and can be set to any desired value.
- "Zoom to layer" via the Layertree sub-menu is supported
- Current layer opacity is applied when exporting/printing
- Print scale is respected in the same way as for WMS sources

### Enabling

To enable WMTS and TMS source support, even with this branch integrated, two additional steps are required:

- The bundle must be added to the [application kernel's bundle registration](https://github.com/mapbender/mapbender-starter/blob/v3.0.8-beta1/application/app/AppKernel.php#L11), like so:

```diff
--- a/application/app/AppKernel.php
+++ b/application/app/AppKernel.php
@@ -30,6 +30,7 @@ class AppKernel extends Mapbender\BaseKernel
             // Optional Mapbender bundles
             new Mapbender\WmcBundle\MapbenderWmcBundle(),
             new Mapbender\WmsBundle\MapbenderWmsBundle(),
+            new Mapbender\WmtsBundle\MapbenderWmtsBundle(),
             new Mapbender\ManagerBundle\MapbenderManagerBundle(),
             new Mapbender\PrintBundle\MapbenderPrintBundle(),
             new Mapbender\MobileBundle\MapbenderMobileBundle(),
```

- The bundle's controller namespace must be added to [the routing configuration](https://github.com/mapbender/mapbender-starter/blob/v3.0.8-beta1/application/app/config/routing.yml), like so:

```diff
--- a/application/app/config/routing.yml
+++ b/application/app/config/routing.yml
@@ -15,6 +15,10 @@ mapbender_wmsbundle:
     resource: "@MapbenderWmsBundle/Controller/"
     type: annotation
 
+mapbender_wmtsbundle:
+    resource: "@MapbenderWmtsBundle/Controller/"
+    type: annotation
+
 mapbender_coordinatesutilitybundle:
     resource: "@MapbenderCoordinatesUtilityBundle/Controller/"
     type: annotation
```

### [1110](https://github.com/mapbender/mapbender/pull/1110)

Absorbs bundle iteration for Element classes into a new service.  

Adds [a DI compiler entry point to completely replace an Element with another](https://github.com/mapbender/mapbender/blob/9ab9cabfa5dd8ba4c9ce5ddeb5431bf8fcb1e76b/src/Mapbender/CoreBundle/Component/ElementInventoryService.php#L51), thus allowing a project to turn all instances of the base Element into their customized versions automatically.  
The same mechanism can also be used to mark Elements as ~"migrated somewhere else". The service comes with [a built-in curated list of such migrations](https://github.com/mapbender/mapbender/blob/9ab9cabfa5dd8ba4c9ce5ddeb5431bf8fcb1e76b/src/Mapbender/CoreBundle/Component/ElementInventoryService.php#L14).

Adds [a separate DI compiler entry point to prevent creation of specific Element classes](https://github.com/mapbender/mapbender/blob/9ab9cabfa5dd8ba4c9ce5ddeb5431bf8fcb1e76b/src/Mapbender/CoreBundle/Component/ElementInventoryService.php#L76), thus supporting projects that wish to turn off a built-in Element completely, without having to hack any built-in bundle code.

### [1108](https://github.com/mapbender/mapbender/pull/1108)

Mapbender does now render marker layers in ImageExport or print.
Supports blended marker layers (opacity << 1), icon offsets, arbitrary dpi scaling.  
Does **not** support icon images located outside of `/bundles/`.

### [1106](https://github.com/mapbender/mapbender/pull/1106)

Ends edit mode when

- deleting the currently edited feature; fixes [Issue #1040](https://github.com/mapbender/mapbender/issues/1040)
- deactivating (sidepane visibility off or popup closed)
- adjusting layer z index (this was found to break the edit control's interactions)

### [1101](https://github.com/mapbender/mapbender/pull/1101)

#### Behavioral fixes

Keeps the current selection rectangle across selection deactivation + reactivation.  
Meaning, in *Dialog* mode, closing and reopening the dialog; in *Element* mode, cycling the `(De)Activate Print Frame` button.

The selection rectangle owned by the PrintClient Element will fully reinitialize only if either

1) the same Element hadn't created a selection rectangle before, or
2) a previous selection rectangle exists but would be completely off screen on the current main map view

Full reinitialization means centering the rectangle around the current center of the main map view, and adopting the current map view's scale as the print selection scale, limited to the range of scales configured on the PrintClient Element.

In all other cases the selection rectangle will retain its old center and its old scale. In *Element* mode, the scale can still be manually changed via dropdown while the selection rectangle is actually inactive and invisible. If you do that, the selection rectangle will reflect your manually updated scale once you activate it again.

#### Technical fixes

Removes JS errors when cycling printclient selection rectangle on / off "too fast" (=opening/closing the dialog in Dialog mode, clicking `(De)Activate Print Frame` in Element mode).  
Reduces likelihood of JS errors when activating printclient selection rectangle "too early" by deferring layer + drag control initialization until feature display.  

Synchronicity fixes around the `getTemplateSize` request.

1) Prevent a print selection rectangle opened "too early" from being just a tiny dot. This happened because, before `getTemplateSize` comes back with the actual template dimensions, that's the "default" template site.
2) Resolve issue where where "too fast" consecutive template changes didn't apply properly.

Reduces total Ajax requests back to server.

1) No longer requests template size when *closing* the print client dialog.
2) Buffers already retrieved template sizes for the rest of the session.

### [1100](https://github.com/mapbender/mapbender/pull/1100)

Isolates legend handling in print into a separately DI-rewirable component, and introduces a bunch of related objects to ease further work.  

Separates legend handling into three logical phases:

- collecting all legends (fetch images + titles into LegendBlock objects)
- rendering "first page" legends
- rendering all unrendered legends onto spill pages

LegendBlocks remember if they are already rendered, so phase 3 is actually the same as phase 2, except it is allowed to introduce page breaks.
Having this separation allows a clean break after the first page, so other custom code (plugins etc) can freely add pages to the PDF before the legend rendering process resumes.

### [1097](https://github.com/mapbender/mapbender/pull/1097)

This change ties a knot between MapbenderContainerInfo and [the recent attempt at more uniform sidepane element control](https://github.com/mapbender/mapbender/blob/0e0506118f4cd7721c7b258c66a9aba3adc5d0fd/src/Mapbender/CoreBundle/Resources/public/init/element-sidepane.js). MCI instances are punched into the element node data if present, and provisions are made to invoke those callbacks, even though MCI itself no longer does any of this itself.

The immediate benefit is that Element widgets relying on MapbenderContainerInfo now behave properly in a `Buttons`-style sidepane. Most prominently, Digitizer now correctly switches itself on and off. This was previously only possible in an `Accordion`-style sidepane.

### [1096](https://github.com/mapbender/mapbender/pull/1096)

The purpose of the Mapbender Button is to toggle Elements on and off. Doing this properly can be a complex task.

Some Elements that have nothing at all to do with controlling other Elements are seen to inherit from Button in both the PHP and JavaScript worlds. Most commonly so they blend into the toolbar visually without trying to reinvent the markup for that, and to get the base functionality of a toggling highlight.

Recent changes to the Button as a control device for other Elements have posed compatibility issues for those "I just want to look like a Button" non-controlling Elements.

To resolve this conflict of goals, the Button JavaScript widget has been split, offering a plain, just-for-looks button under the old name mapbender.mbButton, while spinning off all functionality related to Element control into a new mapbender.mbControlButton.

The decision which to initialize is based on inheritance:

- If the Button is just the Button, it will be initialized as an mbControlButton
- If the Button is a child class of the Button, it will be initialized as an mbButton

> [!NOTE]
> Of course, any PHP child class of the Button can override get getWidgetName method and thus overrule this logic. We expect most existing Button children already do this anyway.

### [1095](https://github.com/mapbender/mapbender/pull/1095)

This makes Buttons controlling other Elements work even if the backend `activate`/`deactivate` configuration is completely empty. The "correct" settings for these options can be [non-intuitive](https://github.com/mapbender/mapbender/issues/1050#issuecomment-463119829) and figuring that out is better left to [a piece of code](https://github.com/mapbender/mapbender/commit/d8be3b5dc64decfe752f054c45a99ceecda52ab4#diff-47bf66afd39ec612d69958ee81aafcb8R85).  

> [!NOTE]
> If there are any backend `activate`/`deactivate` settings they are respected insofar as they are the highest-priority picks of methods to use to control the target element *but only if* the target widget has such a method. This retains administrative control and the option to call entirely non-standard, randomly named methods.

On the other hand, it auto-heals any erroneous configurations by finding something that works. So the straightforward approach of leaving those backend values completely empty, will now just work.

One positive side effect is that the button highlighting machinery now works automatically as well. Highlighting hacks (assigning a button to a unique group, where no other buttons have the same group value) are no longer necessary.

Because the highlighting machinery is generally coming back alive with these changes, logic has also been added to set an appropriate [initial highlight state for targets with `autoOpen` (or similar) options](https://github.com/mapbender/mapbender/pull/1095/commits/04b50b5b392106d817ca49847a365bb821769565#diff-47bf66afd39ec612d69958ee81aafcb8R173).

[↑ Back to top](#git-archive)

[← Back to README](../README.md)

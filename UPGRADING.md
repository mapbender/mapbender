## v3.3.x
### Removed Component\Application
This legacy class contained exclusively static utility methods
which required passing in the full Symfony service container.

Remaining usages must be update to use the [UploadsManager service (id `mapbender.uploads_manager.service`)](https://github.com/mapbender/mapbender/blob/v3.2.4/src/Mapbender/CoreBundle/Component/UploadsManager.php) instead.

See [previous implementation of Component\Application](https://github.com/mapbender/mapbender/blob/v3.2.6/src/Mapbender/CoreBundle/Component/Application.php) for working
replacement code using the service.

### Element API migration
The rebasing of all shipping elements to [the new Symfony-4-compatible Element API](https://github.com/mapbender/mapbender/pull/1367)
has been completed. PHP child classes of any shipping Mapbender Element will break and require some updates.

### Component installer changes
[The abandoned robloach/component-installer package](https://packagist.org/packages/robloach/component-installer)
has been removed, and partially superseded by [code in Mapbender Starter](https://github.com/mapbender/mapbender-starter/pull/98).
Any asset reference from Element or Template PHP classes to a precompiled file
(e.g. "...-built.css") should be expected to break. Replace these
asset references with the concrete source file path(s).

Prefer sourcing asset files from the vendor tree instead of web/components
(one exception: the datatables component is currently only available
from web/components, not inside vendor).

### Bundle registration changes
SensioFrameworkExtraBundle is now registered in Mapbender's BaseKernel.
You will see errors if your application kernel attempts to register it again separately.

## v3.2.6
### Element API changes
In preparation for making Mapbender compatible with Symfony 4,
a [new Symfony-4-compatible Element PHP API](https://github.com/mapbender/mapbender/pull/1367)
has been introduced. Many elements have already been ported to this new
API. PHP child classes of the updated Elements will most likely break
and need some adjustments. Please see the linked PR for guidance.

### Doctrine fixtures for production data
Fixture-based production database setup has been deprecated and
will break on Mapbender 3.3 / Symfony 4.
Quote: ["Fixtures are used to load a “fake” set of data into a database that can then be used for testing or to help give you some interesting data while you’re developing your application"](https://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html).

#### Initial Application import
Avoid using `app/console doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Application/`
in scripts. Replace with `app/console mapbender:application:import app/config/applications`.

#### Seeding mb_core_srs table
Avoid using `app/console doctrine:fixtures:load --fixtures=mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/`
in scripts. Replace with `app/console mapbender:database:init`.

### Global application config modification
It's not longer legal for Elements to perfrom unconstrained
rewriting of the full application configuration (`updateAppConfig` PHP method).

Use client-side script to inspect other elements. Handle
client-side script events to make final adjustments during
initialization.

## 3.0.8.5-RC1
### Relative urls in CSS depending on entry script
Relative URLs in generated application css now always use the correct base path, independent of the presence of the
entry script name (`app.php`, `app_dev.php` or nothing) in the url. Previously, relative urls would commonly contain one `../` too many.

If your Mapbender installation responds to a root url (`http://hostname/app.php` or just `http://hostname/`), this change will have no apparent effect.

If your Mapbender installation responds to a subdirectory url (`http://hostname/mapbender/`), you will have noticed differences
between invocations with `/app.php` and without.

If you have deployed workarounds for the now resolved relative CSS url generation Mapbender deficiency, such as reconfiguring your
web directory path with an additional dummy directory, the fix will most likely conflict with that workaround.  
You should reevaluate the generated CSS after clearing cache. Most likely, removing workarounds will resolve any issues you may encounter.

### CSS hidden aliases
Mapbender is moving towards resolving conflicts with standard Bootstrap form markup and is already dropping some
conflicting CSS rules. If your template contains markup that should initially be invisilbe, whenever possible,
use the class `hidden` instead of certain legacy alternatives. _Do_ _not_ rely on CSS classes `mbHiddenCheckbox` and `hiddenDropdown`
to hide HTML elements. Use `hidden` if you notice markup is rendered visibly. Even though Bootstrap 4 will drop
the `hidden` CSS declaration, Mapbender will continue providing it for the foreseeable future.

Class `mbHiddenCheckbox` no longer exists. Class `checkbox` is no longer globally hidden. Instead, `.checkWrapper > input[type="checkbox"]`
is hidden, to allow modern form markup to contain visible checkboxes.

#### Form markup changes
To improve Bootstrap-theme compatibility, a significant amount of form markup-generating twig code has been
reduced to remove theme-specific element structure and CSS classes. Instead,
forms are now rendered via simple `{{ form_row(forrm.single_field) }}` or even just `{{ form_widget(form) }}` twig
constructs. This may lead to conflicts in customized form templates.

For maximum forward compatibility with Mapbender and potential default form theme changes, do not generate legacy form markup (`labelInput`, `labelCheck`, `inputWrapper` explicity. Use a form type
and use `{{ form_widget(form) }}` or `{{ form_row(form.single_field) }}` in twig templates whenever possible.

Labels (or label translation key references) should be placed into the form type class as a `label` value.  
Custom CSS classes and other attributes should be reviewed for necessity and placed into the `attr` value of the form type.

Note that issues with checkbox markup generated via `form_row` have been resolved since 3.0.8-RC1. The form theme now generates
the correct (legacy) markup for all basic form types. Manual form markup construction in custom twig is no longer necessary and will
on the contrary impede future form theme switches.

#### Changed package dependencies
For improved Symfony forward compatibility, `eslider/sasscb` has been replaced with `wheregroup/assetic-filter-sassc:^0.0.1` *and*
`wheregroup/sassc-binaries:^0.0.1`.

For PostgreSQL 10 schema migration support, the dependency `wheregroup/doctrine-dbal-shims:^1` has been added.

If you update Mapbender with git only, you will have to add these packages manually at your own discretion. When upgrading
Mapbender the recommended way (`bin/composer update`, or optionally `bin/composer update mapbender/mapbender` for a targetted single-package update),
you will not be impacted at all.

If these packages are not installed correctly, all Application and backend CSS compilation will fail (completely unstyled HTML pages).

The `wheregroup/doctrine-dbal-shims` dependency is highly recommended, but functionally optional. If not installed, you will continue to experience errors when attempting to `doctrine:schema:update` on
PostgreSQL 10 connections, as before.

#### Dropped dependencies
The legacy Joii library is no longer required nor provided by Mapbender and will not be reintroduced.
If you expect Joii usages in custom JavaScript code, you will have to readd the dependency on the project level:
```sh
bin/composer require 'wheregroup/joii:^3'
```
You will also have to add the script dependency to your Template class.

To find remaining Joii usages in your project, search for `=\s*Class\s*\(` (regex, case sensitive) in Javascript files.

To permanently get your project code away from Joii, you should use [standard ES5 JavaScript classes](https://dev.to/_hridaysharma/understanding-classes-es5-and-prototypal-inheritance-in-javascript-n8d).

## 3.0.8
#### Package conflicts
If installed, mapbender/data-source must be at least 0.1.11. A conflict rule prevents installation of older versions via Composer. This is a
[a dependency of mapbender/digitizer, mapbender/query-builder and mapbender/data-manager](https://packagist.org/packages/mapbender/data-source/dependents).

Digitizer, if installed, must be >=1.1.68 for changes in feature printing API. See [PR#1123](https://github.com/mapbender/mapbender/pull/1123), [Digitizer PR#69](https://github.com/mapbender/mapbender-digitizer/pull/69). Mutual conflict rules prevent simultaneous installation. Forked / branched packages should include the changes from the linked PRs and only then be marked with appropriate version aliases.

If installed, mapbender/coordinates-utility must be at least 1.0.5 to work at all. We recommend 1.0.7.1 for best results.

If you update Mapbender alone and have trouble also merging in the corresponding Starter update, we recommend you run through the list of package updates in [Mapbender Starter's changelog](https://github.com/mapbender/mapbender-starter/blob/master/CHANGELOG.md) and try to replicate them
manually.

#### BaseKernel inheritance now mandatory
[Mapbender\BaseKernel](https://github.com/mapbender/mapbender/blob/8df72bc31a4d09623c8447fa42197111bb4b277e/src/Mapbender/BaseKernel.php) was introduced a good while ago with Mapbender 3.0.7,
to [simplify dependency coupling with Mapbender Starter](https://github.com/mapbender/mapbender/issues/773). After a grace period of roughly
a year, it is now mandatory that your AppKernel (in starter repository scope) [inherits from this BaseKernel](https://github.com/mapbender/mapbender-starter/blob/v3.0.7.3/application/app/AppKernel.php#L9).

#### Removed functionality
LayerTree.displaytype has been removed. There is only one LayerTree display type in Mapbender.

Layertree.titlemaxlength has been removed in favor of a CSS solution that supports variable widths.

Map: wmsTileDelay has been removed in favor of map engine defaults.

Legend.generateLegendGraphicUrl has been removed due to the conflicts it caused with print legend consistency
vs frontend and general legend image deduplication and dynamification efforts.
This changes nothing if the loaded WMS services advertise their legend images in a conformant fashion.  
If demand for new workarounds for ill-configured WMS legends turns out high, this will necessarily only happen
on the level of source instances, never again globally for entire Mapbender installations.

#### Element customization: inherited asset references
After a weird phase, the Element's `getAsset` method is now again the leading method, in favour of `listAssets`. The static `listAsset` method
cannot account for dynamic circumstances because it has no access to the Element's configuration, the application, the template, the region etc.
Mapbender is re-standardizing on `getAssets`, and no included Element class implements `listAssets` anymore.

The [base Element's getAssets method still deflects to listAssets](https://github.com/mapbender/mapbender/blob/v3.0.8-beta1/src/Mapbender/CoreBundle/Component/Element.php#L236),
so a custom Element implementing only listAssets will still function. However, a custom Element inheriting from a shipping Mapbender
Element can no longer expect to receive those parent Element's asset references via `parent::listAssets()`. The result will be empty.
Inheriting Elements should change their `listAssets` implementation to `getAssets` (which is a simple matter of changing the method name
and removing the `static` keyword), and then replace `parent::listAssets()` invocations with `parent::getAssets()`.

#### PrintClient migration from CoreBundle into PrintBundle
The PrintClient Element, along with its assets, views, admin type and all related code has been moved from
the CoreBundle into the \Mapbender\PrintBundle namespace. No provisions have been made to detect / pick up
any project customizations on the old file locations. If you have customized PrintClient views or assets via app/Resources
drop-ins, you must move them accordingly.

If you have customized PrintClient twigs, you should also review your twigs vs changes to the shipping versions.

To facilitate [print queue integration](https://github.com/mapbender/mapbender/pull/1070), the PrintClient twig has been split up into [the settings form](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Resources/views/Element/printclient-settings.html.twig)
and a [basic element shell](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Resources/views/Element/printclient.html.twig).
The template for the settings form can be customized individually, by asset drop-in or [method override](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Element/PrintClient.php#L204),
separately from the main template. This allows to reuse the exact same settings form template for queued mode, where its markup is wrapped into a
tab container with the queue status in a second tab, and the familiar non-queued mode.

#### Client: Removal of MapQuery
The mapquery component is still installed, but only used as a vehicle to deliver OpenLayers 2 assets. The actual MapQuery script is not
loaded into any applications anymore. There is only [limited emulation shimming](https://github.com/mapbender/mapbender/blob/8df72bc31a4d09623c8447fa42197111bb4b277e/src/Mapbender/CoreBundle/Resources/public/mapbender.model.js#L7) to retain the look and feel of certain MapQuery data structures,
such as Mapbender.Model.map, and Mapbender.Model.map.layersList.  
[Mapbender.Model.map.layers()](https://github.com/mapbender/mapbender/blob/8df72bc31a4d09623c8447fa42197111bb4b277e/src/Mapbender/CoreBundle/Resources/public/mapbender.model.js#L60) may now only be called to create a single vector layer, where it will generate a client-side warning. All other
invocations will throw an Error.

#### Client: icon CSS rules, Button icon markup
CSS rules for .icon* and particularly .iconBig have been updated. The negative margins, negative paddings and `position: absolute` rules
are all gone. If you have counter-styled these rules in a custom CSS theme and now observe icon layouting issues, those are most likely
resolved by removing counter-rules for paddings and margins.

Icon CSS classes on Button Elements are no longer assigned on the full button. Icons are now emitted as a tag inside the
button instead. Markup for custom toolbar-dwelling Elements with icons should be reviewed against the updated button.html.twig
template if issues arise.

#### Backend: Removal of FOS JS Routing
The Element configuration backend no longer emits the [FOS JS Routing](https://packagist.org/packages/friendsofsymfony/jsrouting-bundle) assets.
Element forms with custom JavaScript making calls to `Routing.generate` et al will fail. We strongly advise against re-adding FOS JS Routing explicitly
to your Element form. Instead we recommend that you generate any required URLs on the twig level, using the `path()` method.
See [Coordinates Utility Pull #11](https://github.com/mapbender/coordinates-utility/pull/11/files) for a concrete example on how to implement this change.

#### Other asset removals
The outdated underscore.js version in Mapbender/CoreBundle/Resources/public/vendor has been removed. Custom Elements or Templates requiring underscore should use the version installed into web/components/underscore (which is provided by default by all included Templates).

Redundant copies of StringHelper SymfonyAjaxManager and EventDispatcher scripts have been removed. You will still find versions of these in mapbender/data-source and mapbender/vis-ui.js packages.

An assortment of obsolete jQuery plugin sources unused by Mapbender been removed. See [the v3.0.7.7 tree](https://github.com/mapbender/mapbender/tree/v3.0.7.7/src/Mapbender/CoreBundle/Resources/public/regional/vendor/jquery) for affected files. If
you need any of these in a custom project template, you should re-add these, or suitable modern versions, into the appropriate project bundle.

[Vintage Internet Explorer shims](https://github.com/mapbender/mapbender/tree/v3.0.7.7/src/Mapbender/CoreBundle/Resources/public/regional/vendor/ie-hacks) have likewise been removed.

#### PrintService restructuring
It has generally been very difficult to customize PrintService via standard inheritance / DI methods due
to its almost entirely closed nature.  
PrintService has been broken apart into multiple components and its internal API has seen significant
changes. Of note:
* Each layer type is now processed by its own [LayerRenderer class](https://github.com/mapbender/mapbender/blob/v3.0.8-beta1/src/Mapbender/PrintBundle/Component/LayerRenderer.php), e.g. [WMS](https://github.com/mapbender/mapbender/blob/v3.0.8-beta1/src/Mapbender/PrintBundle/Component/LayerRendererWms.php) and [GeoJson](https://github.com/mapbender/mapbender/blob/v3.0.8-beta1/src/Mapbender/PrintBundle/Component/LayerRendererGeoJson.php).  
  These are individually DI-pluggable services. You can replace them or [register entirely new ones](https://github.com/mapbender/mapbender/blob/69d028ee3949d4870d8afb7c97a8d5633532e4c8/src/Mapbender/WmtsBundle/DependencyInjection/Compiler/RegisterWmtsExportLayerRendererPass.php).
* Print now builds onto (overhauled) ImageExport logic for main map and overview. This mainly helps bring ImageExport quality up to print levels.  
  Do note that ImageExport and Print _can_ have different sets of LayerRenderers. You might want to add hypothetical "WatermarkLayerRenderer" to Print, but not ImageExport.
* Legends have been spun off into a dedicated [handler service](https://github.com/mapbender/mapbender/blob/v3.0.8-beta1/src/Mapbender/PrintBundle/Component/LegendHandler.php)
* [Print Templates are now internally objects](https://github.com/mapbender/mapbender/pull/1092) (with significant BC amenities for array-style access)
  * All 'shapes' (named text fields and regions) are now objects as well. Every such shape extracted
    from the odg template runs through either [handleRegion](https://github.com/mapbender/mapbender/blob/b51807d8ebd1587d47b26e4532c283cb5e7eb134/src/Mapbender/PrintBundle/Component/PrintService.php#L293) or
    [addTextFields](https://github.com/mapbender/mapbender/blob/b51807d8ebd1587d47b26e4532c283cb5e7eb134/src/Mapbender/PrintBundle/Component/PrintService.php#L318). These objects carry the font style and offset / size information.

#### Print template field name uniqueness
All text fields defined in the odg part of print templates must all have unique names.

#### Logic redistribution into services
- Component\Element's `getElementForm` and `getAdminFormType` have been absorbed into a new [ElementFormFactory](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/ManagerBundle/Component/ElementFormFactory.php)
  - Collateral: Component\Element's `getFormAssets` method has been removed entirely after determining that its return value was never evaluated in any scope. If your Element forms need special extra assets, the way to do this was, and still is, to source them from your form's twig template (see [example](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/WmsBundle/Resources/views/ElementAdmin/dimensionshandler.html.twig#L17)).
- Component\Element's `getDefaultElement` has been absorbed into a new [ElementFactory](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Component/ElementFactory.php)
- Component\Application's `getAssets` et al have been absorbed into a new [ApplicationAssetService](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Asset/ApplicationAssetService.php)
- Component\Application's `createAppWebDir`, `removeAppWebDir` and `copyOrderWeb` have been superseded by a new [UploadsManager](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Component/UploadsManager.php). The new
  method APIs function differently. Please see [66cf9299c](https://github.com/mapbender/mapbender/blob/66cf9299c90fe3bf5c9cf99ccd09d1ea522883ba/src/Mapbender/CoreBundle/Component/Application.php#L234) for an exact
  emulation of these three methods' old behaviors, but already using the service.
- Component\Application's `getGrantedRegionElementCollection` et al have been superseded by additions to [the application presentation service](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Component/Presenter/ApplicationService.php).
- Component\Applications's `addViewPermissions` has been removed in favor of the new ([YamlApplicationImporter](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Component/YamlApplicationImporter.php).
- [Redacted] Disused Component\Application's `getConfiguration` has now been removed; its duties had already been fully taken over by [ConfigService](https://github.com/mapbender/mapbender/blob/e2fd234ffa5f98d6c74c0359f26d7d60362f50dd/src/Mapbender/CoreBundle/Component/Presenter/Application/ConfigService.php) in previous versions  
  This change has been reverted to support potentially remaining invocations from customized twig templates.

#### HTMLElement inheritance
HTMLElement [has been reverted to a pure markup renderer](https://github.com/mapbender/mapbender/pull/1122). Any child classes of HTMLElement
that rely on the preprocessing of "`children`" configuration options through methods such as `processItems` or similar will probably want to inherit
from [DataSource's BaseElement](https://github.com/mapbender/data-source/blob/0.1.11/Element/BaseElement.php) instead.

Also gone are the options `jsSrc` and `css`. There are already three viable ways to inject scripts and stylesheets into your HTMLElement child:
1) Return asset references from your getAssets method
2) Insert complete inline `<script>` or `<style>` tags into the `content` option body
3) Leverage the Twig capabilities of HTMLElement to generate asset urls from within the `content` option body, e.g.:  
   `<script src="{{ asset('something-in-your-web-folder.js') }}">`

#### Partial FOM absorption
Mapbender has [taken in certain portions of FOM](https://github.com/mapbender/mapbender/pull/1120). The general BC impact of this
move will be very low, but there is a definite impact for installations with drop-in customized `manager.html.twig` and / or `menu.html.twig`
templates. Concise instructions what to do are [in the pull](https://github.com/mapbender/mapbender/pull/1120).

#### API change `printDigitizerFeature`
This method signature has changed in tandem with Digitizer's invocation, to make its intended use case work.
See [PR#1123](https://github.com/mapbender/mapbender/pull/1123), [Digitizer PR#69](https://github.com/mapbender/mapbender-digitizer/pull/69).

If you attempt to invoke this method from JavaScript generated from a FeatureInfo template, you will also need to update your
invocation.

You are now expected to pass in a JavaScript attribute: value mapping. A first attempt may look something like this ([Mapserver-specific](https://mapserver.org/output/template_output.html)):
```
<...>.printDigitizerFeature({
        id: "[item name=id]",
        tensile_strength: "[tensile_strength]",
        some_other_attribute: "[some_other_attribute]"
    },
    "name-of-a-feature-schema");
```
Because any field you render requires a corresponding text field in an odg template to show up in print, you should have
full awareness of the set of attributes you have to pass in.  
The second argument references a predefined call of features, used to look up specialized print templates.

We consider this use case a hack. Deep calls into element internals made from externally generated code cannot be
supported. There will be no particular amenities to make such hacks work.

#### Misc Element changes
SearchRouter and SimpleSearch buffer settings are now always in meters, not in "map units" as currently documented.

## v3.0.7.7
Starting from Mapbender v3.0.7.7, PrintClient JavaScript widget inherits from ImageExport JavaScript widget.
Any custom PrintClient-derived Element that inherits from the base PrintClient widget client-side
must now also [require the ImageExport JavaScript server-side](https://github.com/mapbender/mapbender/blob/v3.0.7.7/src/Mapbender/CoreBundle/Element/PrintClient.php#L57).

## v3.0.7.6
Mapbender v3.0.7.6 requires changes from FOM. FOM must be updated to at least v3.0.6.2.  
Common symptoms when not updating FOM:
- Vertical content spills in frontend Element popups (try a Legend with many active layers)

## v3.0.7.4
Requires a `doctrine:schema:update`. Common symptom when skipping update:
- Errors on updating / accessing sources that have keywords in their metadata if running on Oracle database 

## v3.0.6.x / v3.0.5.x => v3.0.7.x 
Mapbender v3.0.7.x requires changes from Mapbender Starter. A forked starter should be
merged up to at least v3.0.7.3 when updating Mapbender.  
Mapbender v3.0.7.x requires running a `doctrine:schema:update`.

Common symptoms when not updating / merging starter:
- Kernel initialization fails noting a missing `Doctrine\MigrationsBundle`
- CI / build process failures
Common symptom when not updating schema:
- Exceptions noting a missing `layerOrder` column in WmsInstance entity


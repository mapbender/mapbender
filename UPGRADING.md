## dev-release/3.0.7
#### Package conflicts
If installed, mapbender/data-source must be at least 0.1.11. A conflict rule prevents installation of older versions via Composer. This is a
[a dependency of mapbender/digitizer, mapbender/query-builder and mapbender/data-manager](https://packagist.org/packages/mapbender/data-source/dependents).

If installed, mapbender/coordinates-utility must be at least 1.0.5 to work at all. We recommend 1.0.7.1 for best results.

#### BaseKernel inheritance now mandatory
[Mapbender\BaseKernel](https://github.com/mapbender/mapbender/blob/8df72bc31a4d09623c8447fa42197111bb4b277e/src/Mapbender/BaseKernel.php) was introduced a good while ago with Mapbender 3.0.7,
to [simplify dependency coupling with Mapbender Starter](https://github.com/mapbender/mapbender/issues/773). After a grace period of roughly
a year, it is now mandatory that your AppKernel (in starter repository scope) [inherits from this BaseKernel](https://github.com/mapbender/mapbender-starter/blob/v3.0.7.3/application/app/AppKernel.php#L9).

#### Inherited Element asset references
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

#### Backend: Removal of FOS JS Routing
The Element configuration backend no longer emits the [FOS JS Routing](https://packagist.org/packages/friendsofsymfony/jsrouting-bundle) assets.
Element forms with custom JavaScript making calls to `Routing.generate` et al will fail. We strongly advise against re-adding FOS JS Routing explicitly
to your Element form. Instead we recommend that you generate any required URLs on the twig level, using the `path()` method.
See [Coordinates Utility Pull #11](https://github.com/mapbender/coordinates-utility/pull/11/files) for a concrete example on how to implement this change.

#### Other asset removals
The outdated underscore.js version in Mapbender/CoreBundle/Resources/public/vendor has been removed. Custom Elements or Templates requiring underscore should use the version installed into web/components/underscore (which is provided by default by all included Templates).

#### PrintService restructuring
It has generally been very difficult to customize PrintService via standard inheritance / DI methods due
to its almost entirely closed nature.  
PrintService has been broken apart into multiple components and its internal API has seen significant
changes. Of note:
* [Print Templates are now internally objects](https://github.com/mapbender/mapbender/pull/1092) (with significant BC amenities for array-style access)
  * All 'shapes' (named text fields and regions) are now objects as well. Every such shape extracted
    from the odg template runs through either [handleRegion](https://github.com/mapbender/mapbender/blob/b51807d8ebd1587d47b26e4532c283cb5e7eb134/src/Mapbender/PrintBundle/Component/PrintService.php#L293) or
    [addTextFields](https://github.com/mapbender/mapbender/blob/b51807d8ebd1587d47b26e4532c283cb5e7eb134/src/Mapbender/PrintBundle/Component/PrintService.php#L318). 

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


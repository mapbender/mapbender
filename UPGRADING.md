## Future

An upcoming TBD Mapbender version will break compatibility with mapbender/data-source < 0.1.9,
which is
[a dependency of mapbender/digitizer, mapbender/query-builder and mapbender/data-manager](https://packagist.org/packages/mapbender/data-source/dependents).
An updated version of mapbender/data-source is already anchored in the current Mapbender Starter's composer.lock.
Please merge up your starter or update the mapbender/data-source package manually at the
earliest convenience. 

An upcoming TBD Mapbender version will remove the fosjsrouting dependency, which will require updates in other packages:
- mapbender/coordinates-utility, if used, must be >= 1.0.5

## dev-release/3.0.7
The PrintClient Element, along with its assets, views, admin type and all related code has been moved from
the CoreBundle into the \Mapbender\PrintBundle namespace. No provisions have been made to detect / pick up
any project customizations on the old file locations. If you have customized PrintClient views or assets via app/Resources
drop-ins, you must move them accordingly.

To facilitate print queue integration, the PrintClient twig has been split up.
The [settings form](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Resources/views/Element/printclient-settings.html.twig) has
been broken out of [the element shell](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Resources/views/Element/printclient.html.twig).
The template for the settings form can be customized individually, by asset drop-in or [method override](https://github.com/mapbender/mapbender/blob/8dbd2e9eefdbacf1dd4b60f14f8e7b345005743f/src/Mapbender/PrintBundle/Element/PrintClient.php#L204),
separately from the main template (which may or may not wrap the settings form into a tab container, depending on queue mode configuration).

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


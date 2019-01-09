## Future
An upcoming TBD Mapbender version will break compatibility with mapbender/data-source < 0.1.9,
which is
[a dependency of mapbender/digitizer, mapbender/query-builder and mapbender/data-manager](https://packagist.org/packages/mapbender/data-source/dependents).
An updated version of mapbender/data-source is already anchored in the current Mapbender Starter's composer.lock.
Please merge up your starter or update the mapbender/data-source package manually at the
earliest convenience. 

An upcoming TBD Mapbender version will remove the fosjsrouting dependency, which will require updates in other packages:
- mapbender/coordinates-utility, if used, must be >= 1.0.5

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


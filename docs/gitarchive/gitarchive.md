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

Note that the `@` sign in the replacement key needs to be escaped by another `@@` sign, otherwise symfony tries (and fails) to resolve the file as a service.

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

[↑ Back to top](#git-archive)

[← Back to README](../README.md)

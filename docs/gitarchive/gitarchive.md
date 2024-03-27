# Git Archive

## Good to know: Pull request descriptions

### [1450](https://github.com/mapbender/mapbender/pull/1450)

- Add twig template option `dropdown_elements_html` which, when set, won't encode dropdown elements

### [1453](https://github.com/mapbender/mapbender/pull/1453)

- Add thousand separators in all places within the two elements (using JS's [toLocaleString](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/toLocaleString) and PHP's [NumberFormatter](https://www.php.net/manual/de/numberformatter.create.php))

### [1457](https://github.com/mapbender/mapbender/pull/1457)

- Add *jquery-ui-touch-punch* library for touch support

### [1458](https://github.com/mapbender/mapbender/pull/1458)

- Add CSRF Protection tokens to forms and calls that modify content and were not yet protected by the Symfony forms system
- Add access control checks to various calls in *ElementController*
- Correctly show/hide `No instance added` notice in layerset configuration

> [!NOTE]
> Also modified all files where there were still windows style line endings (`\r\n` instead of `\n`). Select the `hide whitespace changes` option in the *Files changed* tab to ignore those changed from being displayed.

### [1461](https://github.com/mapbender/mapbender/pull/1461)

Mapbender 3.2.5 removed properties from *WmsBundle/Component/Style* ([see commit](https://github.com/mapbender/mapbender/commit/f318cc6611ecfdfa6a036e6ba76d77d512ba3b2e)). The style is stored serialised in the database. PHP 8.2 does not ignore unknown properties like previous version but issues a deprecated message that Symfony converts into an error (see [PHP changelog](https://www.php.net/manual/en/migration82.deprecated.php#migration82.deprecated.core.dynamic-properties)).

As a workaround, overwrite `__unserialize` and check for known properties ignoring the old ones.  

### [1468](https://github.com/mapbender/mapbender/pull/1468)

To greatly improve debugging experience in Mapbender, the generated js and css files in dev mode will now provide a source map. The debug markers are removed in the process, finding them in a 5 MB, >100.000 lines file was not convenient anyway.

Limitations:

- does only work in local installations since the source files are not publicly exposed
- works in chrome, not flawlessly in firefox though. The file protocol is weakly supported there

### [1481](https://github.com/mapbender/mapbender/pull/1481)

#### Added new parameter `mapbender.markup_cache.class`

default: `Mapbender\FrameworkBundle\Component\Renderer\ApplicationMarkupCache`.
FQCN of the *MarkupCache* class. Change if you want to customise the class that is responsible for caching the markup of frontend applications

#### Added new parameter `mapbender.markup_cache.include_session_id`

`default:`false`` The default markup cache caches an application based on application slug, locale, map engine code and element id that are visible to the user. This means however, that two people with the same rights will be delivdered the same markup. Usually that's fine, if however you display user-specific information, like their email address, in the frontend, set this new parameter to true to avoid them receiving the same markup. Note that for each user and application a file will be created on the server. Consider your application logic if you have a lot of users.

### [1483](https://github.com/mapbender/mapbender/pull/1483)

- Refer to the [UPGRADING.md](../UPGRADING.md#Removed-OpenLayers-2-support) document to learn about the removal of OpenLayers 2 and method renaming.

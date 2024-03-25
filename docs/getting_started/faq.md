# Frequently Asked Questions

## System requirements

### Does Mapbender work with PHP 7?

Yes, a freshly installed Mapbender 3.0.8.5 works "out of the box" with PHP from (and including) 5.4 up to (and including) 7.2.

#### 7.2 ... does Mapbender work with >= PHP7.3?

A complete test has not yet been run, but basically yes. However, manual interventions are still necessary.

#### Update to Symfony 3.4

The platform in the *composer.json* of the launcher must be set to e.g. 7.1.9, or removed completely. Then run `bin/composer update doctrine/common doctrine/dbal doctrine/orm` once.

## Installation

### How do I install Mapbender on my workstation?

* [Note system requirements](https://github.com/mapbender/mapbender-starter#requirements),

* [Clone and bootstrap](https://github.com/mapbender/mapbender-starter#getting-the-code).

### How do I solve file system access errors in Apache and/or on the console?

These errors occur because files are created and read by both Apache and command line processes, and these are assigned incompatible owners and permissions by default.

Apache must therefore be configured as a "process user". The server is called and behaves like the user who triggers the **console processes**. Locally, this is your SSH user name, for a customer the SSH user you use to log in.

This is achieved by setting `APACHE_RUN_USER` and `APACHE_RUN_GROUP`, followed by a `sudo service apache restart`.

The weaker alternative is to approach Apache by joining the `www-data` group.

> [!IMPORTANT]
> This is different for each distribution and version.

```console
sudo usermod -aG <your-account-name> www-data
```

### How do I get my Mapbender development onto a server?

Ideally, directly from Gitlab via `git clone` or `git pull` followed by `composer install`.

If read access to Gitlab and/or packagist is not allowed from the target system, two things must be done:

1. the responsible PM must be informed that the restricted server access is an impediment that must be explained to the customer as additional work and risk.
2. the entire package is bundled into an archive via `bin/composer run build`, transferred to the server and unpacked there again.

Special care must be taken if an sqlite database is used on the server. Overwriting the sqlite file from the archive to the target system is only desirable for initial installation.

### How do I integrate my feature-specific branch of package X/Y?

Current version of *specificbranchname*:

```console
bin/composer require 'mapbender/digitizer:dev-specificbranchname@dev'
```

Exact version of *specificbranchname* nailed to a specific commit:

```console
bin/composer require 'mapbender/digitizer:dev-specificbranchname#5a5a2f497c9244d186624c925946e886fab25b39@dev'
```

### What do I do if the installation aborts with version conflicts?

> [!CAUTION]
> Version conflicts are modelled because package X in/on/before certain version **really doesn't** work with package Y in/on/before certain version. If you know the reasons for the specific conflict and are sure that your branch version will not cause any problems, define a pseudo-version number that is no longer recognised as a conflict:

```console
bin/composer require 'mapbender/digitizer:dev-specificbranchname as 1.1.99@dev'
```

Do it all at the same time (full syntax):

```console
bin/composer require 'mapbender/digitizer:dev-specificbranchname#5a5a2f497c9244d186624c925946e886fab25b39 as 1.1.99@dev'
```

## Runtime environment (JavaScript)

### How do I access the main map from my element?

Classically via a configured *target*: This contains the *DOM ID* of the map element. At runtime, the map can then be found via the *DOM-ID*, or (better) via the *elementRegistry*.

Advantage of *elementRegistry*:

1) you get the widget instance as a real JavaScript object;
2) you get a Promise that you can wait for individually or even `via $.when` in combination with other Promises;
3) you get (again through the promise) a separate code path for error handling.

Accessing the DOM node from the widget instance is easier than the other way round: `widget.element` for JQuery-Collection, or `widget.element.get(0)` for the naked DOM element.

#### Example see "Ruler": Form type and frontend script

The form type for maps is initialised automatically when the form is called.
For Yaml-defined applications, the ID of the map must be entered.

### Can there be several map elements?

A Mapbender application should and must always contain exactly one map.
Example of a script-side lookup in the registry using the CSS class selector for `.mb-element-map`:

```javascript
Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) { console.log(mbMap); })
  t.<computed>.<computed> {element: n.fn.init(1), uuid: 11, eventNamespace: ".mbMap11", bindings: n.fn.init(1), hoverable: n.fn.init, ...}
    element: n.fn.init [div#52.mb-element.mb-element-map.olMap, context: div#52.mb-element.mb-element-map.olMap]
    uuid: 11
    eventNamespace: ".mbMap11"
    bindings: n.fn.init [div#52.mb-element.mb-element-map.olMap, prevObject: n.fn.init, context: undefined]
    hoverable: n.fn.init {}
    focusable: n.fn.init {}
    classesElementLookup: {}
    document: n.fn.init [document, context: document]
    window: n.fn.init [Window]
    options: {classes: {...}, disabled: false, create: null, poiIcon: {...}, layersets: Array(2), ...}
    _super: undefined
    _superApply: undefined
    elementUrl: "/app_dev.php/application/aliks-bb-test/element/52/"
    model: {mbMap: t.<...>.<computed>, map: NotMapQueryMap, sourceTree: Array(16), srsDefs: Array(7), mapMaxExtent: {...}, ...}
    map: NotMapQueryMap {idCounter: 16, layersList: {...}, element: n.fn.init(1), olMap: initialise}
<...>
```

Support for finding the map via *target* will remain.

### How do I get from my element to any other element?

Still via a configured *target*. Multiple assignments of different targets (including the classic map) are certainly possible. See e.g. `POIAdminType`. The values (element IDs) are stored in the element under the selected attribute in the options.

Access to the element widget is also best via the registry.

The element should also be retrieved via the element registry instead of a simple DOM selection:

```php
_create: function() {
   var self = this;
   Mapbender.elementRegistry.waitReady(this.options.any_target).then(function(externalWidget) {
      self.externalWidget = externalWidget;
   });
},
_anyMethod: function() {
    // Access to widget instance
    this.externalWidget.deactivate();
    // Access to markup via jQuery
    this.externalWidget.element.addClass('test').show();
    // Access to naked DOM node
    alert("This thing is a " + this.externalWidget.element.get(0).nodeName);
}
```

[↑ Back to top](#frequently-asked-questions)

[← Back to README](../README.md)

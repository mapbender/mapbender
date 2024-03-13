# Developer Guide

The Mapbender team welcomes contributions from all members - so you are welcome to join us in the development!

Third-party patches are essential for the preservation of high standards in [Mapbender].

We simply cannot access the huge number of platforms and myriad configurations that run [Mapbender].

We want it as easy as possible to carry out changes to get the [modules] in your environment to run.

Therefore, we provide a few [guidelines][rules] as an overview for contributors to Mapbender.

## Architecture

Mapbender is based on a [Symfony framework] and uses [composer] to manage external and internal libraries as own [modules][module].

## Modules

Module is a new part of the [Mapbender] concept, based on [Symfony modularity rules](http://www.symfony.com)
and [composer] dependency manager.

Special builds can be created that exclude subsets of Mapbender functionality.

This allows smaller custom builds when the builder is certain
that those parts of Mapbender are not being used.

For example, it is possible to create an application which only uses map view and did not need [Digitizer] functionality.

Future [Mapbender] releases may be able to exclude any additional modules apart from the core application.

In the past, the development bundles were part of the git [submodules].

Today, each module should be in its own git repository
and reuse the same directory structure.

## Rules

It's __very important__ to follow enclosed rules:

Each module is:

* [git] repository
* [Symfony] bundle
* mapbender [bundle]
* [composer] library (has [composer] definition)

Each module should have:

* only one [bundle]
* only one primary namespace
* identical structure
* own [license] file
* own function description [README] file
* own [CONTRIBUTING].md that describes how other developers should install, setup and contribute in it
* own [tests] relevant to new [features], [elements] or functionality

Write your code using PSR-2, a coding [style guide] standard.

## Bundles

A bundle is a set of functionality (similar to a library) which can be created and used outside of the [Mapbender].
The goal of the Bundle is to restrict the usage of global name spaces and optionally switch, swap and extend the [Mapbender] functionality.

### Bundle structure

A Bundle contains a special set of folders and files:

* __Command/__ - Contains commands. Read more about commands [here] (<http://symfony.com/doc/current/components/console/introduction.html#creating-a-basic-command>)  
* __Controllers/__ - Contains _controllers_ in other words public [API]'s.
* __Component/__ - Contains _components_ in other words _services_,
    this contains business logic in classes. The _components_ are used by controllers or other components.
* __DataFixtures/__ - Fixtures are used to load a controlled set of data into a database. This data can be used for testing or could be the initial data required for the application to run smoothly.
* __DependencyInjection/__ - Contains only one file, this makes [components] in _magical_ way available as [services],
    if they are _registred_ in _Resources/config/services.xml_ [bundle] folder.
* __Documents/__ - Contains documents related to the [bundle]. [MD] for text and [PUML] for charts formats are preferred.
* __Exception/__ - Contains exceptions.
* __Element/__ - Contains Mapbender [elements]. This folder isn't [symfony] conform.
* __Element/Type__ - Contains Mapbender [elements] administration types/forms.
* __Entity/__ - Contains entities.
* __EventListener/__ - Contains event listeners.
* __Resources/config/__ - Contains configurations.
* __Resources/public/__ - Contains web resources ([CSS], JS, images).
* __Resources/views/__ - Contains [twig] and php templates.
* __Resources/translations/__ - Contains [translations].
* __Template/__ - Contains mapbender [templates].
* __Tests/__ - Contains [PHPUnit] and functional tests.
* __composer.json__ - Describes the bundle as [composer] package/library. [Example](https://github.com/mapbender/mapbender-digitizer/blob/master/composer.json)
* __LICENSE__  - Contains [LICENSE] text.
* __README.md__ - Contains [README] text.
* __CONTRIBUTING.md__ - Contains [CONTRIBUTING] text.
* __MapbenderNameBundle.php__ - Bundle description file, this registers and makes available bundle [elements], [templates], [manager controllers] and [layers] register.

Read more about best practices for reusable [bundles] [here](http://symfony.com/doc/2.3/cookbook/bundles/best_practices.html).

### Bundle creation

Create a [git] repository outside of Mapbender, as your own project.

```sh
cd ~/Projects
mkdir new-awesome-bundle
cd new-awesome-bundle
git init 
```

In order to create a [bundle], please take a look at its [structure](#bundle-structure).

**Don't forget to follow [module] [rules]**!

### Create bundle description class

Bundles can contains [Templates], [Elements], [Roles], administration manager menu items or ACL classes.
Bundle class file describes which Templates, Elements or ACL classes are delivered and available for the bundle.
The name of bundle description  file should contain full name of bundle and class name like this: `MapbenderMapbenderNameBundle.php`

Description class should extend the _MapbenderBundle_ class

#### Register bundle components

Methods available to rewrite from _MapbenderBundle_:

* _getElements_ - Should return a list of element classes provided by the bundle. Each entry in the array should have a fully qualified class name.  See [source](https://github.com/mapbender/mapbender/blob/release/3.0.6/src/Mapbender/CoreBundle/MapbenderCoreBundle.php#L33) for an example.
* _getTemplates_ - List of template classes provided by bundle. Each entry in the array is a fully qualified class name.  See [source](https://github.com/mapbender/mapbender/blob/release/3.0.6/src/Mapbender/ManagerBundle/MapbenderManagerBundle.php#L33) for an example.
* _getManagerControllers_ - List of controllers to be embedded into administration manager interface. The list must be an array of arrays, each giving the integer weight, name, route and array of route prefixes to match against. See [source](https://github.com/mapbender/mapbender/blob/release/3.0.6/src/Mapbender/ManagerBundle/MapbenderManagerBundle.php#L11) for an example.
* _getACLClasses_ - List ACL bundle classes. See [source](https://github.com/mapbender/mapbender/blob/release/3.0.6/src/Mapbender/CoreBundle/MapbenderCoreBundle.php#L82) for an example.
* _getRoles_ - List bundle roles. The list must be an array with
  * name: String, must start with ROLE_, e.g. ROLE_USER_ADMIN
  * title: String, human readable, e.g. "Can administrate users"
  * @return array roles. See [source](https://github.com/mapbender/mapbender/blob/release/3.0.6/src/Mapbender/ManagerBundle/MapbenderManagerBundle.php#L64) example.

### Create composer package

Create a [composer].json as described in the example.

Dont forget to fill it up:

* __authors__ - Is required in order to know the technical director of the [modules].
* __name__ - Unique name of the [module]. You can check the existens by [composer packagist](https://packagist.org/) service.
* __license__ - [license] short name.
* __description__ - Describes the [module].
* __autoload__ - [psr-4] Path to the namespace classes to load them correctly.
* __target-dir__ - Path where [bundle] root should be placed in.

Better if __autoload__ and __target-dir__ will be copied from example as is, so only [bundle] names should be changed.

```sh
{
    "name": "mapbender/new-awesome-bundle",
    "description": "New awesome bundle description",
    "keywords": ["mapbender","awesome","geo"],
    "type": "library",
    "license": "MIT",
    "authors": [
        {"name": "Andriy Oblivantsev"}
    ],
    "require": {
        "php": ">=5.3.3",
        "imag/ldap": "2.x"
    },
    "autoload": {
  "psr-4": {"Mapbender\\NewAwesomeBundle": "."}
    },
    "target-dir": "Mapbender/NewAwesomeBundle",
    "extra": {
        "branch-alias": {
            "dev-master": "1.0-dev"
        }
    }
}
```

More about composer definition [here](https://getcomposer.org/doc/04-schema.md).

### Save bundle

* Commit changes
* [Create](https://help.github.com/articles/create-a-repo/) [GitHub] repository
* [Add remote](https://help.github.com/articles/adding-a-remote/)
* [Push](https://help.github.com/articles/pushing-to-a-remote/)  changes to [GitHub]

### Versioning

To learn about semantic versioning please read the documentation [here][versioning].

#### Create version

```sh
git tag 0.0.1
```

#### List versions

```sh
git tag -l
```

#### Push version

```sh
git push --tags
```

### Install package with active source control

I.e. you want to install an optional package in a form that allows you to branch it and run arbitrary git commands on
it.

```sh
composer require --prefer-source mapbender/awesome-optional-package:dev-master@dev
```

Note: the `dev-master` is a special type of "version" [recognized by composer as a branch name](https://getcomposer.org/doc/articles/versions.md#branches).
The `@dev` relaxes [stability requirements](https://getcomposer.org/doc/articles/versions.md#minimum-stability) so you can directly install, in this case, the
latest commit on the master branch. With default "stable" stability, you could only install proper tagged release versions.

This essentially clones the git repository into vendor/mapbender/awesome-optional-package, instead of
just extracting a prepackaged zip archive containing the files.

### Switch to module directory

```sh
cd vendor/mapbender/new-awesome-bundle/Mapbender/NewAwesomeBundle/
```

This is a normal [git] repository, [bundle] and [composer] package at the same time.

Now you are ready to change and commit code directly in the project.

To get involved, please look at [digitizer] structure as example.

## Elements

### Definition

Mapbender elements are an optional part of each [bundle] and should be stored under _SomeBundle/SomeElementName_ folder.

Each Mapbender element is:

* A central part of Mapbenders configurable functionality
* [Symfony] controller([API])
* [jQuery] [widget]
* Part of [bundle]
* Child of [Element] class

Each Mapbender element has its own:

* JavaScript front end [jQuery] [widget]
* HTML [DOM] element
* [translation]/s as [TWIG] file
* [SCSS]/[CSS] style(s)
* [Backend] [API]
* administration form type to set, store and restore configuration

### Element Creation

Generate a new element by giving:

* name of [bundle]
* name of new [element]
* source directory, relative to _application_ folder, where the [bundle] is stored

```sh
app/console mapbender:generate:element "Mapbender\DigitizerBundle" MyNewElement vendor/mapbender/digitizer
```

Now there are new files located in the [bundle] folder. For more information read the [full tutorial](http://doc.mapbender3.org/en/book/development/element_generate.html).

In order to introduce our new element and to show it by adding a new element, it should be registered in the main [bundle] file in "getElements" method,
located in the root folder of the [bundle].

#### Example

* Bundle file: Mapbender/DigitizerBundle/MapbenderDigitizerBundle.php

```php
...
class MapbenderDigitizerBundle extends MapbenderBundle
{
    public function getElements()
    {
        return array(
            'Mapbender\DigitizerBundle\Element\MyNewElement'
        );
    }
}
```

## Templates

* __Fullscreen__ - is the main template. This should be used for a desktop
based application.

* __Mapbender mobile template__ - is the current mobile template. This is in development
and can be used for simple tasks. Use it at your own risk.

* __Classic template__ - is deprecated. This template shouldn't be used. The only reason why it's still in the list is for
backwards capability of Mapbender 3.0.x based projects.

* __Responsive__ - isn't ready and shouldn't be used. This template is just a
playground for future development and for new templates. Use it at your own risk.

## Styling

Application template styling can be done by using the [CSS] tab in the backend for adding your own style sheets.

[CSS]/[SCSS] text will be parsed to use on top of the application it's stored for.

### Template Creation

A template is a part of the [bundle]. It's located in the  "Templates/" directory.

* Create new template PHP-Class in "Template" directory
* Extend Mapbender template by:
  * "Mapbender/CoreBundle/Component/Fullscreen" for desktop application
  * "Mapbender/MobileBundle/Template/Mobile" for mobile application

Example:

```php
class NewTemplate extends Mapbender\CoreBundle\Component\Template{
}
```

* override public methods pass your needs
* register template in [bundle] register file "AcmeBundle.php", this is located in bundle root folder

```php
    public function getTemplates()
    {
        return array('Mapbender\AcmeBundle\Template\NewTemplate');
    }
```

* remove the cache

Now your template should be avaible. You can use it by creating a new application and choose it in the template list.

## Translations

Read more about [translations](http://symfony.com/doc/2.3/book/translation.html).

To get unique named translations, use a bundle name prefix before subject.

### Translation Example

```xml
      <trans-unit id="9728e3887eb78b1169723e59697f00b9" resname="somebundle.dialog.button.add">
        <source>somebundle.dialog.button.add</source>
        <target>Add</target>
      </trans-unit>
```

### Generate translations

By using [TWIG] files, a implemented generator can transform any used [translation] automatically in 'xlf' files.

Therefore, these few parameters must be submitted:

* __--output-format=__ - Format of generated translation file. It's important to use [xlf].
* __--force__ - Force append new translations to existing translation files
* __Language__ - Language short name (de/en/ru)
* __BundleName__ - Name of [bundle]

### Translation generation example

```sh
app/console translation:update --output-format=xlf --force de MapbenderCoreBundle
```

## Feature branch

It's mandatory to use the "feature/" prefix in the branch name.

Example:

* Create branch:

```sh
cd mapbender
git checkout -b "feature/mega-cool-feature-x"
```

* Improve the code.
* Save changes:

```sh
git add *
git commit -m "Add some new stuff"
```

* Merge current release code:

```sh
git fetch -a
git merge "release/3.0.6"
```

* If conflicts arise, resolve [them][Resolve git conflicts].
* Run tests.
* Push the changes on [github]:

```sh
git push
```

* Create [pull-request]:

Then just wait for our feedback. We will check it out and review your code to merge it in the branch. Thanks!

## Bug fix branch

It's mandatory to use the "hotfix/" prefix in your branch name.

Example:

* Create branch:

```sh
cd mapbender
git checkout -b "hotfix/bug-short-description"
```

* Improve the code.
* Save changes:

```sh
git add *
git commit -m "Fix bug description"
```

* Merge current release code:

```sh
git fetch -a
git merge "release/3.0.6"
```

* If conflicts arise, resolve [them][Resolve git conflicts].
* Run or add new tests relevant to the fixed bug.
* Push the changes on [github]:

```sh
git push
```

* Create [pull-request] on the current release branch.

Then just wait for our feedback. We will check it out, test and review your code to merge it in the branch. Thanks!

## Release branch

This branch can only be changed by a project maintainer.
It's mandatory to use _release/_ prefix in your branch name.

Example:

* Checkout release branch:

```sh
cd mapbender
git checkout "release/3.0.6"
```

* Fetch changes:  

```sh
git fetch -a
git pull
```

* Merge changes:

```sh
git merge "hotfix/bug-short-description"
```

* If conflicts arise, resolve [them][Resolve git conflicts].
* Run or add new tests relevant to the new feature.
* Review the code.
* Run tests.
* Save changes:

```sh
git commit -m "Merge 'hotfix/bug-short-description'"
```

* Push on [github]:

```sh
git push
```

## Building packages

There are special [composer] commands for distributing and building packages:

* `bin/composer build` Command to build a package with the following optional parameters:
  * __[tar.gz|zip]__ - Optional parameter that defines the package file format. The default configuration is defined in `composer.json` as `config/archive-format`.
  * __[dist-name]__ - Optional parameter that defines the package file name prefix. The default configuration is defined in `composer.json` as `name`, a vendor name will be ignored.
  * __[dist-version]__ - Optional parameter that defines the package version. This is included as suffix in the package name. The default configuration is defined in `composer.json` as `version`.

You can define the [composer] distributing path in `composer.json` as `config/archive-dir`. The default location is the `dist` folder located in root of the project.

## Build package example

You can build and distribute an articat to `dist/test-distribution.1.0.1.tar.gz` by running:

```bash
bin/composer build zip test-distribution 1.0.1
```

## Building linux tarball-file

```bash
bin/composer build tar.gz
```

## Tests

Don't forget to write tests!
Moreover, please write a clear commit message.
Here are some good explanations:

### Testing Examples

* Test all [bundles]:

```bash
bin/phpunit -c app vendor/mapbender
```

* Test unique [bundle]:

```bash
bin/phpunit -c app vendor/mapbender/digitizer
```

* Test [bundle] class:

```bash
bin/phpunit -c app vendor/mapbender/digitizer/Mapbender/DigitizerBundle/Tests/FeaturesTest.php
```

## Resources

### Resources Modules

* [Mapbender] - Contains Core, Manager, Print, Mobile and some other [bundles] this will be extracted as [modules] in next releases.
* [FOM] - **F**riends **o**f **M**apbender contains Administration and Security components [bundles]. The module is deprecated and will be split in new modules as optional parts of Mapbender3.
* [OWS Proxy] - Secure communicate remote hosts through Mapbender3 backend.
* [Digitizer] - Digitalizing [bundle], which contains geometry [services].
* [DataStore] - DataStore [bundle], which contains data drivers and [services].

### Libraries

* [Symfony framework]
* [Composer documentation](https://getcomposer.org/doc/)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests/)

[rules]: #rules "Rules"
[bundle]: #bundles "Bundle"
[bundles]: #bundles "Bundle"
[tests]: #tests "Tests"
[features]: #features
[elements]: #elements
[element]: #elements
[templates]: #templates
[translation]: #translations
[translations]: #translations
[modules]: #modules
[module]: #modules
[Git submodules]: #submodules
[manager controllers]: #manager-controllers
[layers]: #layers
[services]: http://symfony.com/doc/2.3/book/service_container.html "Symfony Services"
[components]: http://symfony.com/doc/current/components/index.html
[style guide]: http://www.php-fig.org/psr/psr-2/
[Symfony]: http://www.symfony.com "Symfony framework"
[Symfony framework]: http://www.symfony.com "Symfony framework"
[Composer]: https://getcomposer.org/doc/
[git]: https://git-scm.com/ "Git"
[API]: https://en.wikipedia.org/wiki/Application_programming_interface
[jQuery]: https://jquery.com/
[widget]: http://github.bililite.com/understanding-widgets.html
[license]: https://getcomposer.org/doc/04-schema.md#license
[README]: https://en.wikipedia.org/wiki/README
[CONTRIBUTING]: https://github.com/blog/1184-contributing-guidelines
[MD]: https://guides.github.com/features/mastering-markdown/ "Markdown"
[PUML]: http://plantuml.com/ "PlaintUML"
[DOM]: "http://www.w3schools.com/js/js_htmldom.asp" "HTML DOM"
[SCSS]: http://sass-lang.com/guide "SCSS"
[CSS]: http://www.w3schools.com/css/css_intro.asp "CSS"
[TWIG]: http://twig.sensiolabs.org/ "TWIG"
[pull-request]: https://help.github.com/articles/creating-a-pull-request/ "Pull requests"
[Resolve git conflicts]: https://help.github.com/articles/resolving-a-merge-conflict-on-github/ "Resolve git conflicts"
[Mapbender]: https://mapbender3.org/  "Mapbender3"
[FOM]: https://github.com/mapbender/fom  "FOM submodule"
[OWS Proxy]: https://github.com/mapbender/owsproxy3  "OWS proxy submodule"
[Digitizer]: https://github.com/mapbender/mapbender-digitizer "Mapbender digitizer module"
[DataStore]: https://github.com/mapbender/data-source "Mapbender data source"
[github]: https://github.com/ "GitHub"
[phpunit]: https://phpunit.de/getting-started.html "PHPUnit"
[versioning]: http://semver.org/

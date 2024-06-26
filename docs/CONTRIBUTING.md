# Contributing Guide

The Mapbender team welcomes contributions from all members - so you are welcome to join us in the development!

Third-party patches are essential for the preservation of high standards in Mapbender. We simply cannot access the huge number of platforms and myriad configurations that run Mapbender. We want it as easy as possible to carry out changes to get the [modules] in your environment to run.

Therefore, we provide a few [guidelines][rules] as an overview for contributors to Mapbender.

## Architecture

Mapbender is based on a [Symfony framework] and uses [composer] to manage external and internal libraries as own [modules].

## Modules

Module are  based on [Symfony modularity rules](http://www.symfony.com) and the [composer] dependency manager.

Special builds can be created that exclude subsets of Mapbender functionality. This allows smaller custom builds when those parts of Mapbender are not being used. For example, it is possible to create an application which only uses map view and does not need [Digitizer] functionality.

Each module should be in its own git repository and reuse the same directory structure.

### Rules

Please follow the attached rules to contribute to a module.

Each module is a:

* [Git] repository
* [Symfony] bundle
* Mapbender [bundle]
* [Composer] library

Each module should have:

* only one [bundle],
* only one primary namespace,
* an identical structure,
* its own [license] file,
* its own function description [README] file,
* its own [CONTRIBUTING].md that describes how other developers should install, setup and contribute in it,
* its own [tests] relevant to new features, [elements] or functionality.

Write your code using PSR-12, a coding [Style guide] standard.

## Bundles

A bundle is a set of functionality (similar to a library) that can be created and used outside of [Mapbender].
The goal of the bundle is to restrict the use of global namespaces and optionally switch, swap and extend the [Mapbender] functionality.

### Bundle structure

A bundle contains a specific set of folders and files:

* **Command/**: Contains [commands] that can be run on the command-line (<http://symfony.com/doc/current/components/console/introduction.html#creating-a-basic-command>)
* **Controllers/**: Contains public [API]'s.
* **Component/**: Contains services, which contain business logic in classes. The components are used by controllers or other components.
* **DataFixtures/**: Fixtures are used to load a controlled set of data into a database. This data can be used for testing or could be the initial data required for the application to run smoothly.
* **DependencyInjection/**: Contains only one file, this makes [components] available as [services],
 if they are registered in `Resources/config/services.xml` [bundle] folder.
* **Documents/**: Contains documents related to the [bundle]. [MD] for text and [PUML] for charts formats are preferred.
* **Exception/**: Contains exceptions.
* **Element/**: Contains Mapbender [elements]. This folder is mapbender-specific and does not exist in [Symfony].
* **Element/Type**: Contains Mapbender [elements] administration types/forms.
* **Entity/**: Contains (database) entities.
* **EventListener/**: Contains event listeners.
* **Resources/config/**: Contains configurations.
* **Resources/public/**: Contains web resources ([CSS], JS, images).
* **Resources/views/**: Contains [twig] and php templates.
* **Resources/translations/**: Contains [translations].
* **Template/**: Contains mapbender [templates].
* **Tests/**: Contains [PHPUnit] and functional tests.
* **composer.json**: Describes the bundle as [composer] package/library. [Example](https://github.com/mapbender/mapbender-digitizer/blob/master/composer.json)
* **LICENSE** : Contains [LICENSE] text.
* **README.md**: Contains [README] text.
* **CONTRIBUTING.md**: Contains [CONTRIBUTING] text.
* **MapbenderNameBundle.php**: Bundle description file, this registers and makes available bundle [elements], [templates], manager controllers and layers register.

Read more about best practices for reusable [bundles] [here](https://symfony.com/doc/6.4/bundles/best_practices.html).

### Bundle creation

Create a [Git] repository outside of Mapbender as your own project.

```sh
cd ~/Projects
mkdir new-awesome-bundle
cd new-awesome-bundle
git init 
```

In order to create a [bundle], please take a look at its [structure](#bundle-structure).

> [!IMPORTANT]
> **Don't forget to follow the [module] [rules]**!

### Create a bundle description class

Bundles can contain templates, elements, roles, administration manager menu items and more.  
A bundle class file describes which templates and elements are delivered and available for the bundle.
The name of the bundle description file should contain the full name of the bundle and its class name, like this:

> `MapbenderMapbenderNameBundle.php`

The description class should extend the `Symfony\Component\HttpKernel\Bundle\Bundle` class.


### Create composer package

Create a `composer.json` as described in the example.

Don't forget to fill in:

* **authors**: Is required in order to know the technical director of the [modules].
* **name**: Unique name of the [module]. You can check the existence by the [composer packagist](https://packagist.org/) service.
* **license**: Short name of the license.
* **description**: Describes the [module].
* **autoload**: Path to the namespace classes to load them correctly.
* **target-dir**: Path where [bundle] root should be placed in.

> [!TIP]
It is better if **autoload** and **target-dir** are copied from the example as is, so only the [bundle] names should be changed.

```sh
{
    "name": "mapbender/new-awesome-bundle",
    "description": "New awesome bundle description",
    "keywords": ["mapbender","awesome","geo"],
    "type": "library",
    "license": "MIT",
    "authors": [
        {"name": "Thorsten Hack"}
    ],
    "require": {
        "php": ">=8.1.0",
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

Read more about composer definition [here](https://getcomposer.org/doc/04-schema.md).

### Save your bundle

* Commit the changes,
* [Create](https://help.github.com/articles/create-a-repo/) the [GitHub] repository,
* [Add a remote](https://help.github.com/articles/adding-a-remote/),
* [Push](https://help.github.com/articles/pushing-to-a-remote/) your changes to [GitHub].

### Versioning

To learn about semantic versioning, please read the documentation [here][versioning].

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

### Install a package with active source control

That means you want to install an optional package in a form that allows you to branch on it and run arbitrary git commands on it.

```sh
composer require --prefer-source mapbender/awesome-optional-package:dev-master@dev
```

> [!NOTE]
> `dev-master` is a special type of version [recognized by composer as a branch name](https://getcomposer.org/doc/articles/versions.md#branches).
The `@dev` relaxes [stability requirements](https://getcomposer.org/doc/articles/versions.md#minimum-stability) so you can directly install, in this case, the
latest commit on the master branch. With default "stable" stability, you could only install proper tagged release versions.

This essentially clones the git repository into vendor/mapbender/awesome-optional-package, instead of
just extracting a prepackaged zip archive containing the files.

### Switch to module directory

```sh
cd vendor/mapbender/new-awesome-bundle/Mapbender/NewAwesomeBundle/
```

This is a [git] repository, [bundle] and [composer] package at the same time.

Now you are ready to change and commit the code directly in the project.

To get involved, please look at the [digitizer] structure as an example.

## Elements

### Definition

Mapbender elements are an optional part of each [bundle] and should be stored under *SomeBundle/SomeElementName* folder.

Each Mapbender element is:

* A central part of Mapbenders configurable functionality,
* a [Symfony] controller([API]),
* a [jQueryUI] [widget],
* a child of an [Element] class.

Each Mapbender element has its own:

* JavaScript front end [jQueryUI] [widget],
* HTML [DOM] element,
* [translation]/s as [TWIG] file,
* [SCSS]/[CSS] style(s),
* [Backend] [API],
* administration form type to set, store and restore configuration.

### Element Creation

For information on how to add elements, see the [full tutorial](elements/elements.md).

## Templates

* **Fullscreen**: Main template. This should be used for a desktop based application.

* **Mapbender mobile template**: A reduced-feature template optimized for use on mobile devices

## Styling

Application template styling can be done by using the [CSS] tab in the backend for adding your own style definitions.

[CSS]/[SCSS] text will be parsed to use on top of the application it is stored for.

### Template Creation

A template is a part of a [bundle]. It is located in the `Templates/` directory.

* Create a new template PHP class,
* Extend Mapbender template by:
  * *Mapbender/CoreBundle/Component/Fullscreen* for desktop application
  * *Mapbender/MobileBundle/Template/Mobile* for mobile application

Example:

```php
class NewTemplate extends Mapbender\CoreBundle\Component\Template {
}
```

* Override public methods to pass your needs,

* Clear the cache.


Now your template should be available. You can select it when creating a new application. 
Note that the template cannot be changed for existing applications.

## Translations

Translations are defined using yaml files. Read more about translations [in the symfony documentation](https://symfony.com/doc/6.4/translation.html).

To get unique named translations, use a bundle name prefix before the translation key.


## Adding features

To add a feature to mapbender core: 

* fork the project

* Create a new branch based of the current `develop` branch:

```sh
cd mapbender
git checkout -b "feature/new-feature-x"
```

* Improve the code.

* Save changes:

```sh
git add *
git commit -m "Improve the new feature"
```

* If conflicts arise, resolve [them][Resolve git conflicts].
* Run tests.
* Push the changes on [GitHub]:

```sh
git push
```

* Create [pull-request]:

Then just wait for our feedback. We will check it out and review your code to merge it in the branch. Thanks!

## Bug fix branch

Hotfixes can be branched off the `master` branch.

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
* 
* Run or add new tests relevant to the fixed bug.
* Push the changes on [github]:

```sh
git push
```

* Create [pull-request] on the current release branch.

Then just wait for our feedback. We will check it out, test and review your code to merge it in the branch. Thanks!


## Building packages

There are special [composer] commands for distributing and building packages. They contain the code including all dependencies.

* `bin/composer build` command to build a package with the following optional parameters:
  * **[tar.gz|zip]**: Optional parameter that defines the package file format. The default configuration is defined in `composer.json` as `config/archive-format`.
  * **[dist-name]**: Optional parameter that defines the package file name prefix. The default configuration is defined in `composer.json` as `name`, a vendor name will be ignored.
  * **[dist-version]**: Optional parameter that defines the package version. This is included as suffix in the package name. The default configuration is defined in `composer.json` as `version`.

You can define the [composer] distributing path in `composer.json` as `config/archive-dir`. The default location is the `dist` folder located in root of the project.

## Build a package example

You can build and distribute an artifact to `dist/test-distribution.1.0.1.tar.gz` by running:

```bash
bin/composer build zip test-distribution 1.0.1
```

## Building a linux tarball-file

```bash
bin/composer build tar.gz
```

## Tests

Do not forget to write tests. Moreover, please write a clear commit message.

Here are some good explanations:

### Testing Examples

* Test all [bundles]:

```bash
bin/phpunit -c app vendor/mapbender
```

* Test a unique [bundle]:

```bash
bin/phpunit -c app vendor/mapbender/digitizer
```

* Test a [bundle] class:

```bash
bin/phpunit -c app vendor/mapbender/digitizer/Mapbender/DigitizerBundle/Tests/FeaturesTest.php
```

## Resources

### Resources Modules

* [Mapbender]: Contains Core, Manager, Print, Mobile, Administration and Security components and some other [bundles].
* [OWS Proxy]: Secure communication with remote hosts through the Mapbender backend.
* [Digitizer]: Digitizing [bundle] that contains geometry [services] and the data store.

### Libraries

* [Symfony framework]
* [Composer documentation](https://getcomposer.org/doc/)
* [General GitHub documentation](https://help.github.com/)
* [GitHub pull request documentation](https://help.github.com/send-pull-requests/)

[rules]: #rules "Rules"
[bundle]: #bundles "Bundle"
[bundles]: #bundles "Bundle"
[tests]: #tests "Tests"
[elements]: #elements
[element]: #elements
[templates]: #templates
[translation]: #translations
[translations]: #translations
[modules]: #modules
[module]: #modules
[services]: http://symfony.com/doc/2.3/book/service_container.html "Symfony Services"
[components]: http://symfony.com/doc/current/components/index.html
[Style guide]: http://www.php-fig.org/psr/psr-12/
[Symfony]: http://www.symfony.com "Symfony framework"
[Symfony framework]: http://www.symfony.com "Symfony framework"
[Composer]: https://getcomposer.org/doc/
[git]: https://git-scm.com/ "Git"
[API]: https://en.wikipedia.org/wiki/Application_programming_interface
[jQuery]: https://jquery.com/
[jQueryUI]: https://jqueryui.com/
[widget]: http://github.bililite.com/understanding-widgets.html
[license]: https://getcomposer.org/doc/04-schema.md#license
[README]: https://en.wikipedia.org/wiki/README
[CONTRIBUTING]: https://github.com/blog/1184-contributing-guidelines
[MD]: https://guides.github.com/features/mastering-markdown/ "Markdown"
[PUML]: http://plantuml.com/ "PlaintUML"
[DOM]: "http://www.w3schools.com/js/js_htmldom.asp" "HTML DOM"
[SCSS]: http://sass-lang.com/guide "SCSS"
[CSS]: http://www.w3schools.com/css/css_intro.asp "CSS"
[TWIG]: https://twig.symfony.com/ "TWIG"
[pull-request]: https://help.github.com/articles/creating-a-pull-request/ "Pull requests"
[Resolve git conflicts]: https://help.github.com/articles/resolving-a-merge-conflict-on-github/ "Resolve git conflicts"
[Mapbender]: https://mapbender.org/ "Mapbender"
[FOM]: https://github.com/mapbender/fom "FOM submodule"
[OWS Proxy]: https://github.com/mapbender/owsproxy3 "OWS proxy submodule"
[Digitizer]: https://github.com/mapbender/mapbender-digitizer "Mapbender digitizer module"
[DataStore]: https://github.com/mapbender/data-source "Mapbender data source"
[github]: https://github.com/ "GitHub"
[phpunit]: https://phpunit.de/getting-started.html "PHPUnit"
[versioning]: http://semver.org/

[↑ Back to top](#contributing-guide)

[← Back to README](README.md)

# Conventions for Mapbender

## Code conventions

* Variable names / way of coding
* Code documentation
* [Translation convention](#translation-convention)
* document the steps on the way to a new functionality
  * define the topic
  * create a ticket
  * create a workflow
  * discuss the workflow with the Mapbender team and find a final solution
  * do the programming
  * insert a license
  * test the feature
  * document the new feature in the mapbender-documentation repository
  * close the ticket
* where to put a module/element
* naming vor files (referred to symfony convention)

## Git branch conventions

We follow the Git Flow branching model (Read more about it in the [original document](https://nvie.com/posts/a-successful-git-branching-model) describing it). Basically that boils down to having at least two branches:

* develop - for the daily work, always has the latest merged commits, and is
  equal to or ahead of the latest release
* master - only changes on new releases (right now and until the 3.0.1 release,
  some of our repositories don't have a master branch but will get one then
  again)

Furthermore there might be more branches, which must always be namespaced:

* feature/name - Used for developing new features (feature/printservice)
* hotfix/name - Used for making hot fixes for releases (hotfix/bug_123)
* release/name - Used for preparing releases, very short-lived (release/3.0.1)

Some Linux distributions have a package called git-flow which will provide easy
git command shortcuts to use the merge/branch model of Git Flow without having
to do everything by yourself (which is still possible and you should always know
how Git Flow uses plain git to achieve things).

## Layout conventions

What to keep in mind, when you create a layout

* naming conventions
* where to put the css
* where to put the twig
* where to put the images / should be possible to easy switch an image collection an get other buttons

## Translation convention

* Also have a look at [Translation](../workflows/translation.md).

## Issue conventions

Issues (bugs and features) are administrated in the [Mapbender repository issue tracker](https://github.com/mapbender/mapbender/issues).

We create a [GitHub milestone](https://github.com/mapbender/mapbender/milestones) for every version of Mapbender.

There are some rules you should keep in mind:

### Write understandable issues

* Write a **title** that summarizes the problem.
  * `Browser - Backend/Frontend - element - issue`
  * like: `Firefox - Frontend - layertree - option visible is not handled in frontend`
  * see [example](https://github.com/mapbender/mapbender/issues/48)
* Write **comments** with all necessary information.
  * for *bugs*: describe step by step how the error can be reproduced.
  * for *features*: describe feature and functionality.
* When you create a new issue, do not assign it to a milestone or developer yourself.
* **Add labels** to your issue
  * Bug - describes a bug that orrurs in a special version of Mapbender (add info about the version)
  * Feature - new feature
  * Enhancement - stands for feature enhancement
  * WIP - work in progress
* When you work on a issue or close it please **assign** a user and milestone
* When you successfully **close** an issue, please:
  * add a comment in the issue and **refer to the commit**,
  * refer to the documentation or a demo if possibile.

## Versioning conventions

The Mapbender version is defined by a four digit numbering system, seperated by dots.

4.0.10.20

* The **first** digit is constant and represents the Mapbender software cycle.

* The **second** digit describes all new features and major changes in Mapbender, with the highest difficulty level of a update process.

* The **third** digit describes new features and minor changes, which can be easily updated.

* The **fourth** digit represents only bugfixes and micro changes.

Increase a digit means always a reset for all digits before. For example - 4.0.10.20 -> 4.1.0.0

This numbering system started with Mapbender version 3.0.0.0

## Release

* check whether all relevant issues are completed,
* build a [Mapbender build](#how-to-build-a-new-mapbender-build),
* update the Roadmap and its milestones,
* update the [Demo](https://demo.mapbender.org),
* write a release mail (mapbender-user / mapbender-dev / major releases osgeo-announce),
* post about the release on social media channels.

## How to build a new Mapbender build

* Resolve and close all tickets for the relevant [milestone](https://github.com/mapbender/mapbender/milestones).
* Update the Changelog.md.
* Update version number in `parameters.yaml.dist` and push
* Update version number in `composer.json`
* **Tagging**: Tag at Github. You have nice capabilities for creating good tags and descriptions.
  * Mapbender
  * OWSProxy
  * FOM
  * Mapbender-starter
  * Documentation
* Create **Pull requests** to merge release branch into master
  * Mapbender
  * OWSProxy
  * FOM
  * Mapbender-starter
  * Documentation

* Clone the source-code from the release branch

```console
git clone -b release/3.0.6 git@github.com:mapbender/mapbender-starter
```

* Change to the directory

```console
cd mapbender-starter
```

* Bootstrap

```console
sh bootstrap
```

* Change to the directory

```console
cd application
```

* Generate the docs

```console
bin/composer docs
```

* Create the ZIP/Tar.gz

```console
bin/composer build
bin/composer build zip
```
  
* Move TAR.GZ and ZIP file to /sites/www.mapbender.org/builds
* Update symbolic links (version.zip and version.tar.gz and current.tar.gz and current.zip)
* Move current doc to docs.mapbender.org (get it from web/docs. Remove the api folder).
* Edit the actual release link ([english](https://mapbender.org/en/download)) and [german](https://mapbender.org/mapbender-herunterladen/)
* Write release mail to mapbender-user and mapbender-dev
* Only for major releases write release mail to <news_item@osgeo.org> (see also <https://www.osgeo.org/about/faq/osgeo-mailing-lists/>)
* Twitter on <https://twitter.com/mapbender>
* Update <https://demo.mapbender.org> and <https://sandbox.mapbender.org>
* Create a [version based installation](https://version.mapbender.org)

[↑ Back to top](#conventions-for-mapbender)

[← Back to README](../README.md)

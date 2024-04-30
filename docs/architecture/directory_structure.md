# Directory structure

## app

This directory contains:

* the php-Cache (var/cache)
* the logs (var/log)
* the configuration directory (config)
* the application kernel (src/Kernel.php) (is called by the FrontendControllers and controls the whole application)
* the Autoloading (autoload.php)
* the application specific resource directory (Resources)
* the command line application for maintaining and management tasks (bin/console)

## config

Basic configuration files of Mapbender are placed in the config directory and the config/packages directory. Two files are of particular importance:

* parameters.yaml

* packages/doctrine.yaml

### config/applications

The directory config/applications contains all applications that are defined in a YAML file.

## bin

Here you find some libraries.

## mapbender

Directory of the [Mapbender submodule](https://github.com/mapbender/mapbender). Provides the Mapbender specific bundles and the Mapbender code.

### mapbender/...../translations

Directory: *mapbender/src/Mapbender/CoreBundle/Resources/translations/*

The translations are stored in YAML files. Every language needs an YAML-file like *messages.en.yaml* for the English translation.

## public

This directory has to be published by the webserver. The ALIAS has to refer to this directory.

It controls:

* index.php - the FrontendController (PHP script which can be called).
* this directory contains the static resources like css, js, favicon etc.

### public/bundles

* storage for the static resources of the single bundles.
* the following command copies the resources from the bundles to the folder:

```yaml
     bin/console assets:install --symlink --relative public
```

If you use Windows, you cannot create symbolic links and therefore have to run the command `bin/console assets:install public` to copy the files to the directory after every change in the code.

## src

Directory for application specific bundles.

## vendor

Directory for external libraries (loaded by composer) are placed. Resources are used by Symfony using the Autoloading.

[↑ Back to top](#directory-structure)

[← Back to README](../README.md)

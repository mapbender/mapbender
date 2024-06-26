# Components

Mapbender is made up of different components. On the server side we use Symfony as a framework which comes along with powerful components like Doctrine, Twig, Monolog and more. On the client side we use OpenLayers, jQuery & jQuery UI.

We have a Mapbender core bundle with basic Mapbender functionalities and more Mapbender bundles which are optional. We also offer a Mapbender Starter package. With the Mapbender Starter package, you can set up a Mapbender installation easily.

## Symfony

Symfony is a full object-oriented PHP Web Development Framework. It builds blocks for all modern web application needs. It is a collection of software and a development methodology. It relies on the philosophy of building blocks. It is optimized for speed. It uses Byte Code Cache.

Here is a list of some components Symfony offers:

* Profiler and Debug Toolbar
* Database abstraction (object-relation mapping) via Doctrine
* User authentication, authorization
* Templating via Twig
* Translation
* Logging via Monolog
* Security

## OpenLayers

OpenLayers is a powerful software for web maps. It supports a lot of data sources and functionality.

## jQuery and jQuery UI

jQuery is a feature-rich JavaScript library. jQuery UI is a set of user interface interactions, effects, widgets, and themes built on top of the jQuery JavaScript Library.  

## Mapbender

The Mapbender core consists of a collection of bundles located in the `src` folder.

### CoreBundle

The Mapbender CoreBundle is the base bundle for Mapbender. It offers base classes for applications, elements, layers and more.

It provides jQuery, jQuery UI and OpenLayers for all other Mapbender bundles.

## Mapbender Starter

Mapbender Starter is Symfony demo project which uses the Mapbender bundles to showcase a Mapbender application.

It contains demo applications which are defined in the directory `config/applications` using YAML files. It also provides a web interface with authentication which provides the possibility to create applications, create users/groups and build up a service repository.

Mapbender Starter can be used as a template to start Mapbender projects.


[↑ Back to top](#components)

[← Back to README](../README.md)

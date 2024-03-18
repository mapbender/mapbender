# Components

Mapbender is made up of different components. On the server side we use Symfony as a framework which comes along with powerful components like Doctrine, Twig, Monolog and more.

On the client side we use OpenLayers, MapQuery and jQuery & jQuery UI.

We have a Mapbender core bundle with the Mapbender basic functionalities. And more Mapbender bundles which are optional.

We offer a Mapbender Starter package. With the Mapbender Starter package you can set up a Mapbender installation easily.

  .. image:: ../../figures/mapbender_components.png
     :scale: 60

## Symfony

Symfony is a full object oriented PHP Web Development Framework. It builds blocks for all modern web application needs. It is a collection of software and a development methodology. It relies on the philosophy of building blocks. It is optimized for speed. It uses Byte Code Cache.

Here is a list of some components Symfony offers:

* Symfony config.php to check the prerequisites
* Symfony Profiler
* Database abstraction via Doctrine
* User authentication, authorization
* Templating via Twig
* Translation using xliff-files
* Logging via Monolog
* Security

## OpenLayers

OpenLayers is a powerful software for web maps. It supports a lot of data sources and functionality. You find example applications with OpenLayers at <http://dev.openlayers.org/examples/>

## jQuery and jQuery UI

jQuery is a feature-rich JavaScript library. jQuery UI is a set of user interface interactions, effects, widgets, and themes built on top of the jQuery JavaScript Library.  

Read more about jquery at <http://jquery.com>. Read more about jquery UI at <http://jqueryui.com/>

## Mapbender

Mapbender is a collection of bundles. Only the MapbenderCoreBundle and the FOMBundles are mandatory.

There are optional bundles like:

* WMSBundle
* WMTSBundle
* WMCBundle
* MonitoringBundle

### CoreBundle

The Mapbender CoreBundle is the base bundle for Mapbender. It offers base classes for applications, elements, layers and more.

It provides jQuery, jQuery UI, OpenLayers and MapQuery for all other Mapbender bundles.

.. ToDo
  FOM Bundle

## Mapbender Starter

Mapbender Starter is Symfony demo project which uses the Mapbender bundles to showcase a Mapbender application.

It contains demo applications which are defined in the mapbender.yaml with WMS, WMTS. It provides a web interface with authentication which provides the possibility to create applications, create users/groups and build up a service repository.

Mapbender Starter can be used as a boiler template to start Mapbender projects.

Find the GitHub Repository here: <https://github.com/mapbender/mapbender-starter>

## External Repositories

You find more code connected to Mapbender at GitHub, which is not part of the main project. Other providers can offer Bundles for Mapbender like the DesktopIntegrationBundle which is provided by `WhereGroup <http://wheregroup.com>`__ and sponsored by customers.

WhereGroup offers Bundles for Mapbender at: <https://github.com/WhereGroup>

[↑ Back to top](#components)

[← Back to README](../README.md)

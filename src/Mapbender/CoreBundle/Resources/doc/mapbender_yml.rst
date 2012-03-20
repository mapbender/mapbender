The mapbender.yml explained
===========================

The mapbender.yml file located in the app/config directory is the file based
way to configure your Mapbender3 installation. The file format is YAML which
you should get familiar with. One big tip: Never ever use tabs for indentation.
Things will break if you do!

Basically we hook into the parameters configuration of Symfony 2 to declare
Mapbender3 applications. Therefore you will notice the following structure:

::

    parameters:
        applications:
            app1:
                #...
            app2:
                #...

This would declare two applications with the ids app1 and app2. The id can be
anything you like as long as they stay unique.

Each application configuration block consists of several other block, each
providing the configuration for a specific part of your application:

* general configuration: Some configuration data is specified directly.
* security: Right now, application access can be controlled on ROLE level.
* template: This specifies the application template class to use.
* layersets: Each application can have one or more sets of layers.
* elements: Elements are the building blocks of your application.

General Configuration Options
-----------------------------
There are two options as of now which are of a general nature:

* title: Specify a short title, which may be used in the browser title bar
* description: This should describe your application in a couple of sentences.
  This is primarily used in the application overview page.

::

    parameters:
        applications:
            app1:
                title: Demo App 1
                description: This is my wonderful demo application.

Application Security
--------------------
For now each application can be either publicly accessible or restricted to
certain user ROLES. Roles are declared using the Symfony security subsystem and
will can simply thought of as user groups.

To specify a ROLE or ROLES which are allowed to access an application give an
array of ROLES as the value of the *roles* option::

    parameters:
        applications:
            app1:
                title: Demo App 1
                description: This is my wonderful demo application.
                roles: [ ROLE_A, ROLE_B ]

A single role has nevertheless to be given as an array. Omitting the roles
options will make the application publicly available.

Application Template
--------------------
Each application is based on a HTML template which defines the general layout
of the application. Such a template is enriched by CSS and JavaScript and
provides so-called *regions* which are containers for application elements.
Each application template is defined in a PHP class implementing the
Mapbender\CoreBundle\Component\TemplateInterface.
This class provides the Mapbender3 application controller with all information
to dynamically build the application HTML. Basically the render function of
the class is called and will usually use a Twig template.

The *getMetadata* method describes the template by listing the region names and
CSS and JavaScript files used by the template.

The Mapbender\CoreBundle\Template\Fullscreen is a good starter template using
the MapbenderCoreBundle:Template:fullscreen.html.twig Twig template and the
mapbender.template.fullscreen.css and no JavaScript file.

Most of the time this template will be a good starting point. If you need to
enhance your application HTML, CSS or JavaScript it is a good idea to copy this
class and it's files to your own bundle.

The template configuration option is expecting the class name::

     parameters:
        applications:
            app1:
                title: Demo App 1
                description: This is my wonderful demo application.
                roles: [ ROLE_A, ROLE_B ]
                template: Mapbender\CoreBundle\Template\Fullscreen

Application Layersets
---------------------
All map layers - WMS or WMTS services to display - are defined in what in
Mapbender3 is called a *layerset*. Each layerset is an collection of layers
and each application can have one or more layersets. (Even if you only have one
layerset, you need to define layersets as an list of just one layerset.)

Each layerset lists layer ids which define a configuration for one layer. Each
layer class is explained in it's own documentation file.

::

     parameters:
        applications:
            app1:
                title: Demo App 1
                description: This is my wonderful demo application.
                roles: [ ROLE_A, ROLE_B ]
                template: Mapbender\CoreBundle\Template\Fullscreen
                layersets:
                    main: # this is just an ID
                        layer1:
                            #...
                        layer2:
                            #...

Each layer configuration has at least the *class* and *title* option which
are more or less self explanatory.

Application Elements
--------------------
Elements are the building blocks of each application. As detailed in the
template section, elements are displayed in *regions* of the HTML application
template. Therefore their configuration is grouped by the region ids.
Each element class needs it's own special configuration, look at the
corresponding documentation files for each class.
Each element is given an id and will at least have the class option, giving the
element class.

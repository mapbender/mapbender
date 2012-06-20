TODOs
=====

General
-------

* LICENSE should be inserted in all required files. If there's a short version
  possible I'd prefer it over a long bulky text at the top of every file.

* Installing -> this needs you to run doctrine:schema:create and
  mapbender:resetroot now as the standard configuration uses the DB for storing
  users/roles.

* UPDATING: The changes for the Manager backend break updates for the master
  branch
  - There's a special MapbenderBundle class to use for, ehm, Mapbender bundles.
  - Elements and Layer API has changed a bit (I'd say it got unlittered).
    Especially the WMS and WMTS layer classes had Geoportal.DE specific code
    in them. Big No-GO!
  - I've decoupled the Application from the ApplicationController. It should
    be possible now to write custom controllers using different routes and
    stuff. Fetching applications should go trough the service called
    'mapbender', which transparently fetches from both YAML and DB sources.

  This implies that projects already started will need work to use the new
  Manager backend based Mapbender.

* User registration: There's Geoportal.DE specific stuff in the
  CoreBundle\Controller\UserController class (e-mail adresses and stuff.) I
  would like to suggest:
  - The UserController of the CoreBundle should just handle login/logout,
    nothing more.
  - Move the controller for self-registration, password recovery into the
    ManagerBundle. This way these can be more easily overriden (both).
  - Remove the dependency to the Captcha bundle for the starter. Write an
    tutorial on how to wire up custom login forms using an captcha in your
    application.

* Symfony Update: Once 2.1 is ready I want to upgrade to it. It has some nice
  things which will be useful. We should consider moving more of the Symfony
  stuff as an external dependency (repository-wise). I would like someone to
  investigate if we can use Composer (getcomposer.org) for easier dependency
  management.

Responsibilities
----------------

I have found project specific stuff in the CoreBundle. This should not have
happened. Therefore I would like to propose monthly code reviews where people
take responsibility for the code and mark code to be refactored/reworked on.
I would like to split up the responsibility. Given the nature that much of the
API has been my brain-fart, I will take responsibility for the following
components:

- CoreBundle: Together with at least one more developer and I would suggest that
  the WhereGroup developers should at least participate. It will grow our
  understanding of how things work and should be done.
- ManagerBundle, except the Layer management/repository. I will support in
  understanding the concepts and inner workings.
- PrintBundle: This does not even exist yet, still I want to kickstart it at
  this year's OSGeo Bolsena code sprint. Co-Maintainers welcome.

This leaves the following components open to adoption:

- CoreBundle Layer API: We need to fixate the common API for layer classes.
- WMSBundle
- WMTSBundle
- KMLBundle
- WFSBundle - With all the things happening I would move this into 3.1...

All of these include providing custom forms to be used in the Manager backend.

Documentation
-------------

* I've implemented an ApiGen task inside the build.xml. I've been trying to
  code document the MapbenderCoreBundle and the ManagerBundle. The docs for the
  other bundles are more of lucky shots I suppose and should be rewritten as
  these bundles are worked on. (The Layer class bundles WILL need work.)

* More prosaic documentation should be implemented, maybe using Sphinx? Who
  wants to set up a working environment for that? I'll be happy to write docs
  then and coauthor with anyone who wants to get to know Mapbender3 better.
  This should be implemented to work with our Phing build system.

Layer Management
----------------

This is very basic for now:

* Layerset / Layer hierarchy is subject to discussion.

* API is subject to discussion.

* Using a central Entity (MapbenderCoreBundle:Layer) with one configuration
  attribute which stores the complete serialized configuration and a class
  attribute which determines the layer class at runtime is the approach choosen
  for elements, but this must not be the final word. Experts, anyone?

* WMS and WMTS had too much stuff from the Geoportal.DE project inside.
  I dropped all this, knowing this will break updates for that project.

* Service/Layer Repository - Who want's to volunteer?

Manager Backend
---------------

* This provides basic functionality for managing users and roles (and their
  associations) as well as applications (bar layer and real element management)
  I will develop the universal YAML editor and the special editors for Map and
  Button element as examples how to code custom forms.

* Make manager components plugable via service injection. This will require some
  sort of base controller or stuff. I'm thinking about at the moment.

* Proper paging. Could be postponed for 3.1 i suppose.

User management
---------------

* The basic user entity (MapbenderCoreBundle:User) has id, username, email and
  password. This will need to be slightly extended (is active, login counter?)
  and a mechanism for custom user profiles need to be implemented.

* Roles are stored using the MapbenderCoreBundle:Role entity class. I think we
  want hierarchical roles, which could be done using a MPTT model and a custom
  repository class to encapsulate the hard work of creating upating tree nodes
  and providing a flat list of roles (direct + inherited)

* Maybe there should be a MapbenderSecurityInterface which Application, Element
  and Layer should implement so these can be handled exactly the same way, the
  checking logic wouldn't event have to know what thing this is it is looking
  at...?

* The mapbender:resetroot command should be verified to work nicely when
  resetting an existing root account.

Elements
--------

* Most elements need the following things:
  - Custom form
  - individual CSS

* Probably the element PHP class need another static function giving an array
  of public functions in the JavaScript widget, so buttons can be wired up for
  example.

Application
-----------

* Maybe we'll merge Entity/Application + Component/Application or maybe we'll
  subclass Component/Application from Entity/Application? Right now in templates
  you have to be aware if you need to use {{ application.foo }} or
  {{ application.entity.foo}}. I'm not quite happy with that.

* The Component\ApplicationYAMLMapper should be extended so it can be used to
  export applications as YAML files for transfering / backups. Will need a new
  console command (plus an action in the Manager backend)

Asset management
----------------

* I have implemented the asset management using a controller which works pretty
  nice. It collects assets from all application, elements and layers, and can
  apply filters (CSSRewrite at the moment). It checks for modification and
  returns a 304 if appropiate. Make things faster already.
  Dumping the assets could be postponed for Mapbender3.1 I think.


There's always more to do...


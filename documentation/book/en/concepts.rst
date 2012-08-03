Concepts
########

This chapter will introduce you to the main concepts of Mapbender3. After
reading you should have an understanding of the parts Mapbender3 is made
of and how they interact.

Application
===========

The application is a single Mapbender3 configuration which is usually used
as an interactive web map. It's what was called an GUI in Mapbender 2. But
please forget that term instantly - it's called an Application now.

Every application consists of several components:

* Element
* Layersets
* Application Template

Element
=======

Elements are the building blocks of every application. Each element provides
some functionality and can interact with other elements. The map element is
probably the element you face most of the time as it provides all map viewing
capabilities.

Each element consists of four parts itself:

* PHP class - describes the element with it's capabilities and also can provide
  an Ajax callback point, so that the client side widget (see below) can
  execute database queries and display the result for example.
* JavaScript widget - this is the client side part of an element. It's
  everything you do and interact with on your screen. Using Ajax, it can call
  it's server-side counterpart to do things like database queries.
* Template - HTML the element is using. In the most basic version, this would
  just be an DIV, but it can be as complex as is needed.
* CSS - of course, most elements want some style, so they may provide their
  own.

Application Template
====================

Each application is an HTML page, and the application template renders the basic
layout of that page. Each application can have a different template as needed.
Think of HTML templates specialised for mobile viewing.

Frontend
========

The frontend is basically the application view of your Mapbender3 installation.
This includes the application list and each application view. It's what your
users will interact with.

Backend
=======

The backend is where you configure your Mapbender3 installation. It's called
the manager and allows your admins to manage users, roles, services,
applications and elements.


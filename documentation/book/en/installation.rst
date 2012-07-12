Installation
############

This document describes all neccessary steps in order to get an running
Mapbender3 installation.

Prerequisites
*************

Mapbender3 needs the following components in order to run:

* PHP 5.3 or later (php5)
* PHP CLI interpreter (php5-cli)
* PHP SQLite extension (php5-sqlite)
* PHP cURL extension (php5-curl)

Optionally, in order to use a database other than the preconfigured SQLite one
you will one a matching PHP extension supported by
`Doctrine http://www.doctrine-project.org/projects/dbal.html`.

If you want to use the developer mode, for using the web installer or to create
profiler data to be used to analyze errors you will still need the SQLite
extension!

Download
********

Installation packages are distributed as compressed packages and are available
for download at the `download http://mapbender3.org/download` page.

After downloading, extract the package in a directory of your choice. Then make
sure your Webserver points to the web directory inside the mapbender3 directory
you just uncompressed. You will also need to make sure that the default
directory index is *app.php*.

Configuration
=============

Using the web installer
-----------------------

Configuration right inside your browser is not yet available. Please use the
command line method below for now.

Using the command line
----------------------

Configuring your Mapbender3 installation is made up of the following steps:

* Creating the database
* Creating the database schema
* Copying the bundles' assets to the public web directory
* Initializing the role system
* Creating the "root" user

All can be done using the console utility provided by Symfony2, the awesome
framework Mapbender3 is build upon. There's a mayor caveat though you should
understand, before continuing:

  | The console utility will write files in the app/cache and app/logs
  | directories. These operations are made using the user permissions of
  | whatever user you're logged in with. This is also true for the app/db
  | directory and the SQLite database within. When you open the application
  | from within the browser, the server PHP process will try to access/write
  | all these files with other permissions. So make sure you give the PHP
  | process write access to these files. See last step below.

Adapting the configuration file
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Database connection parameters are stored together with some more configuration
parameters in the file app/config/parameters.yml. This file is using YAML
syntax, so be aware that you can **not** use tabs for indenting. Be careful
about this.

Creating the database
^^^^^^^^^^^^^^^^^^^^^

Symfony2 can attempt to create your database, this works of course only if the
configured database user is allowed to. Call the console utility like so::

    app/console doctrine:database:create

Creating the database schema
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Symfony2 will create the database schema for you, if you ask nicely::

    app/console doctrine:schema:create

Copying the bundles' assets
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Each bundle has it's own assets - CSS files, JavaScript files, images and more -
but these need to be copied into the public web folder::

    app/console assets:install web


As a developer, you might want to use the symlink switch on that command to
symlink instead of copy. This will make editing assets inside the bundle
directories way easier.

Initializing Mapbender's role system
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The standard roles need to be initialized in the database::

    app/console mapbender:initroles

Creating the administrative user
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The first user - which has all privileges - must be created using the command::

    app/console mapbender:resetroot

This will interactively ask all information needed and create the user in the
database.

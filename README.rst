Requirements
============
Mapbender3 is built upon the shoulders of Symfony2, the new generation of
the well-known PHP application framework.
This introduces some requirements which you have to meet in order to run
Symfony2 or Mapbender3:

1. PHP >= 5.3.2: This is essential to run Symfony2
2. date.timezone setting in your php.ini (apache and cli)
3. PHP CLI for running console commands for Symfony2
4. PHP modules: php5-sqlite, php5-psql, php5-intl

For an overview of more optional requirements, see
http://symfony.com/doc/current/reference/requirements.html. We will list
Mapbender3-specific requirements in this document as they arise during
development.

To generate documentation and build tarballs you will need to install the
following dependencies, too:

1. Phing: See http://www.phing.info/docs/guide/stable/ for installation
2. ApiGen: See http://apigen.org/##installation for installation

a call to::

    phing -l

will then list all available phing targets.

Installation
============

Installing using the download package
-------------------------------------
COMING SOON: We will start building downloadable all-in-one packages soon.
Expect to see zipfiles, tarballs, deb and rpm packages.

Installing using Git
--------------------
Our code is maintained using git and hosted at Github. We split up our code
into three parts:

1. mapbender-starter: The starter project you are using right now. This
   provides a complete application to play with and build upon.
2. mapbender: The mapbender code which is independent of a specific project is
   maintained in it's own repository.
3. mapquery: Mapbender uses MapQuery as it's jQuery/OpenLayers wrapper. We
   maintain our own clone.

You can either go ahead by hand, or use the provided bootstrap script:::

  curl https://raw.github.com/mapbender/mapbender-starter/master/bin/bootstrap.sh -o - | sh

Cloning
~~~~~~~
You can clone our code using the command::

  git clone git://github.com/mapbender/mapbender-starter

The mapbender-starter repository references the mapbender repository as a
submodule which again references the mapquery repository.

Therefore you need to pull in the submodules code using::

  git submodule update --init --recursive

Configuration
-------------
After installing the code, you need to make sure that your web server can
write into the application/app/cache and application/app/logs directories.

1. Make sure your webserver can write into the application/app/cache and
   application/app/logs directories. This often breaks, as running
   Symfony console commands writes into these with user rights.

2. Copy the parameters.ini.dist, found in the application/app/config folder,
   as your parameters.ini and modify to fit your database connection. By
   default a SQLite database is preconfigured in the file.

3. Initialize the demo database in app/db/demo.sq3 by running:::

    app/console doctrine:database:create
    app/console doctrine:schema:create


4. Install the bundle assets into the web folder by running the Symfony
   console command from the application directory:::

    app/console assets:install web

   If you are on a Unix-like system, you can use this form to use symlinks
   instead of copying which is great during development:::

    app/console assets:install --symlink web

   Hint: You probably need to run a::

    sudo rm -Rf app/cache/*

   before and after installing the assets.

Running
-------
Symfony2 uses front-end controllers, which are located in the application/web
directory. You should expose this directory via your webserver and run the
app_dev.php to use the development mode front-end controller. You can only
run this controller if the webserver is running on your localhost.

The production front-end controller is named app.php.

Demo users
~~~~~~~~~~
Some of the demos may require you to login. In the delivered security.yml,
two users are hardcoded (name/password):

 user/user
 root/root


Requirements
============
Mapbender3 is built upon the shoulders of Symfony2, the new generation of
the well-known PHP application framework.
This introduces some requirements which you have to meet in order to run
Symfony2 or Mapbender3:

1. PHP >= 5.3.2: This is essential to run Symfony2
2. date.timezone setting in your php.ini

For an overview of more optional requirements, see
http://symfony.com/doc/current/reference/requirements.html. We will list
Mapbender3-specific requirements in this document as they arise during
development.

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


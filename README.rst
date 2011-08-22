
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


Git-based installation
######################

If you want to participate in the Mapbender3 development, or for some other
reasons want to use the Git repositories for Mapbender3, follow this guide
instead of the normal download. This guide is based on Ubuntu 12.04. For
other distributions, you may need to adapt, especially package names like
sphinx-common.

Cloning the Repository
**********************

Cloning is easy, just issue the following command in your shell:

    :command:`git clone -b 3.0 git://github.com/mapbender/mapbender-starter`

Developers granted secure access to the code must use the SSH-URL of the
repository: git@github.com:mapbender/mapbender-starter

Fetching the Submodules
***********************

The starter application does not include the Mapbender3 bundles, these are
kept in a repository of their own and are included as a submodule of the
starter repository. To fetch them, issue the following command at the root
directory of your cloned repository:

    :command:`git submodule update --init --recursive`

Build management using Phing
****************************

Build management is done using `Phing` which is installed using Pear. So, first
we need to get Pear, we are assuming a Debian-based system here:

    :command:`sudo apt-get install php-pear`

We then tell Pear where to autodiscover it's repositories and for good measure,
update Pear:

    :command:`sudo pear config-set auto_discover 1`

    :command:`sudo pear upgrade-all`

Then let's get Phing:

    :command:`sudo pear install phing/phing`

Our build scripts need some more dependencies to run unit test, generate
documentation and build installation packages.

Once you have installed the dependencies listed below, you can get an overview
of available build tasks by issuing

    :command:`phing -l`

The first task you want to - actually need to - execute is the deps task, which
uses `Composer http://getcomposer.org` to install the runtime dependencies like
Symfony and Doctrine:

    :command:`phing deps`


cURL
====

Our build system uses cURL to fetch some remote components, therefore you need
to install the cURL command line tool:

    :command:`sudo apt-get install curl`

Package Build Tools
===================

TODO: Skipped for now, KMQ has the knowledge.

PHPUnit
=======

Symfony2 needs a more recent PHPUnit than for example comes with Ubuntu 12.04.
So we will use Pear to install PHPUnit:

    :command:`sudo pear install phpunit/PHPUnit`

Sphinx
======

Sphinx is used to build the documentation you are reading right now. On Debian-
based systems, you can use apt to install Sphinx:

    :command:`sudo apt-get install sphinx-common`


ApiGen
======

`ApiGen <http://apigen.org>`_ is our API documentation generator of choice. It too
can be installed using Pear, so use the following command:

    :command:`sudo pear install pear.apigen.org/apigen`

Troubleshooting
***************

* The ApiGen task only works with recent versions of Phing. 2.4.12 is sufficent,
  while 2.4.9 is not. Check with :command:`phing -v`. You can update all your
  Pear packages with

    :command:`sudo pear upgrade-all`


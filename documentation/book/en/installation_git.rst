Git-based installation
######################

If you want to participate in the Mapbender3 development, or for some other
reasons want to use the Git repositories for Mapbender3, follow this guide
instead of the normal download.

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

Dependency management using Composer
************************************

All the remaining bundles - Symfony, Doctrine and so on - are also not included.
To fetch them, we use `Composer http://getcomposer.org` as the dependency
manager. Getting composer is easy with curl:

    :command:`cd application`
    :command:`curl -s http://getcomposer.org/installer | php`

Then the remaining dependencies can easily be fetched:

    :command:`php composer.phar install`

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

Once you have installed the following dependencies, you can get an overview
of availbale build tasks by issuing

    :command:`phing -l`

Package Build Tools
===================

TODO: Skipped for now,.KMalhas has the knowledge.

PHPUnit
=======

Symfony2 needs a more recent PHPUnit than for example comes with Ubuntu 12.04.
So we will use Pear to install PHPUnit:

    :command:`sudo pear install phpunit/PHPUnit`

Sphinx
======

Sphinx is used to build the documentation you are reading right now. On Debian-
based systems, you can use apt to install Sphinx:

    :command:`apt-get install sphinx-common`



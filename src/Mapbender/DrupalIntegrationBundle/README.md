Drupal
======

Add the following the end of your settings.php:

$conf['mapbender'] = array(
  'path' => '/path/to/mapbender/app-directory',
  'env' => 'dev'
);

Copy - or symlink - the directory Resources/drupal_module to
sites/all/modules/mapbender and enable it in Drupal's admin backend.

Mapbender's assets directory
=============================

Copy - or better symlink - the web/bundles directory into Drupal's main
directory so that it stands next to Drupal's index.php

Mapbender's security.yml
=========================

For each firewall you want to share auth information with Drupal, add a entry
enabling it:

    firewalls:
        foo:
            drupal: ~

Also, add the Drupal user provider:

    providers:
        drupal:
            id: drupal_user_provider

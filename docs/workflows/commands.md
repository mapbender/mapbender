# Mapbender terminal commands

Mapbender uses [Composer](https://getcomposer.org/) for package dependency management. For deployment convenience, we already
bundle a version of Composer with the repository in *application/bin/composer*. Of course you may also use a system-installed version of Composer.

All following example command lines assume your current working directory is *application*.

## Basic composer commands

We will only briefly document the most commonly used commands here.

Please see [the official documentation](https://getcomposer.org/doc/03-cli.md) for full composer usage instructions.

Note that active package requirement maintenance will also require git and unfettered internet access.

### Install dependencies

The [`install` command](https://getcomposer.org/doc/03-cli.md#install-i) uses the information in composer.lock to install the exact same combination
of package versions as committed to the repository by the developers. This ensures repeatable results and is the way to go for reliable deployment.

You can pass a `--no-dev` switch to exclude packages not relevant to productive deployment.
If dev packages were previously installed, passing this switch will actively remove them from the local file system.

```bash
# install all packages
php bin/composer install
# install production packages only (removes dev packages if previously installed)
php bin/composer install --no-dev
# Re-add dev packages after removal
php bin/composer install
```

### Update dependencies

The [`update` command](https://getcomposer.org/doc/03-cli.md#update-u) scans [the online package index](https://packagist.org/) for updated package
versions, and replaces them accordingly. It also supports the `--no-dev` switch.

By default, `update` will look for updated versions of *all* currently installed packages. You can
pass a (list of) package name(s) to restrict updating to a desired set.

```bash
# install available updates to all packages (including dev)
php bin/composer update
# install available updates to all packages (excluding dev)
php bin/composer update --no-dev
# install available updates to specific packages (excluding dev)
php bin/composer update --no-dev mapbender/mapbender mapbender/owsproxy symfony/symfony
```

### Add or modify dependency

The [`require` command](https://getcomposer.org/doc/03-cli.md#require) marks one or several packages as required for your installation to work and will attempt to install them.
You can optionally specify explicit version constraints for every package named.

You may also re-require an already required package with a new explicit version constraint to switch versions, perform explicit package downgrades and so forth.

```bash
# Add new package
php bin/composer require mapbender/data-manager
# Add new package, explicit minimum version
php bin/composer require 'mapbender/digitizer:^2.0.0'
# Switch and lock package to exact version
php bin/composer require 'mapbender/digitizer:2.0.0'
```

## Mapbender-specific commands

In Mapbender-Starter, the list of Composer commands has been extended with some custom functionality.

### Create Mapbender archive

It is possible to create a filesystem copy of your current Mapbender project with the ```build``` command. The result will be saved in the `dist /` directory of the project.

```bash
php bin/composer build
```

The default format for the generated archive is `.tar.gz`.

#### Choosing format explicitly

You can pass the desired format as an extra argument. Valid choices are `zip` and `tar.gz`.

```bash
php bin/composer build zip
php bin/composer build tar.gz
```

* example:

```text
application$ php bin/composer build tar.gz
> ComposerBootstrap::distribute

 Trying to install assets as relative symbolic links.

 --- -------------------------- ------------------ 
      Bundle                     Method / Error    
 --- -------------------------- ------------------ 
  ✔   FrameworkBundle            relative symlink  
  ✔   FOSJsRoutingBundle         relative symlink  
  ✔   FOMCoreBundle              relative symlink  
  ✔   FOMManagerBundle           relative symlink  
  ✔   FOMUserBundle              relative symlink  
  ✔   MapbenderCoreBundle        relative symlink  
  ✔   MapbenderWmcBundle         relative symlink  
  ✔   MapbenderWmsBundle         relative symlink  
  ✔   MapbenderManagerBundle     relative symlink  
  ✔   MapbenderPrintBundle       relative symlink  
  ✔   MapbenderMobileBundle      relative symlink  
  ✔   MapbenderDigitizerBundle   relative symlink  
  ✔   SensioDistributionBundle   relative symlink  
 --- -------------------------- ------------------ 

 [OK] All assets were successfully installed.                                                                           

Distributed to: /home/XX/Projekte/Mapbender/mapbender-starter-commands/dist/mapbender-starter-current
> ComposerBootstrap::build
27M     /home/XX/Projekte/Mapbender/mapbender-starter-commands/dist/mapbender-starter-current.tar.gz
```

[↑ Back to top](#mapbender-terminal-commands)

[← Back to README](../README.md)

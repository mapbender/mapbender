# Project Setup Guide

This guide is intended to help you set up new Mapbender projects as painlessly as possible. It starts from scratch, Mapbender knowledge is not necessarily required. This guide aims at creating a "customer project" that customizes the appearence and functionality of mapbender for a certain client, not at providing new functionality that should be published.   

## Initialising the repository

Customer projects are usually managed in a private repository hub.

```bash
- git clone git@my-private-git.com:.....
- git remote add mapbender git@github.com:mapbender/mapbender-starter.git
- git fetch --all
- git fetch --tags
- git merge v4.0.0 # a tagged release as base -> recommended 
- git merge mapbender/master # the master as base -> not recommended
```

Merge conflicts in *README.md* can be ignored for now, just use your own version, the default *README.md* in the customer projects makes no sense.

After initialisation, the mapbender-remote can be removed again:

```bash
git remote remove mapbender
```

> [!IMPORTANT]
> For updates, add it again and then merge it into the local branch using the same merge command. In principle, the remote can also be left in, but then make sure that the customer project is NEVER pushed to the mapbender remote!

Bootstrapping then follows as described in [Mapbender Starter README](https://github.com/mapbender/mapbender-starter/blob/master/README.md#bootstrapping).

## Configuration

The following initial configurations are necessary, all in `application/app/config`:

- `applications`: Demo applications. Usually the can all be deleted.
- `config.yml`: If several databases are used, the connections can be added under `doctrine.dbal.connections`, analogue to the default connection. The actual passwords are set in `.env` or `.env.local`.
- `parameters.yaml`: not checked in, should have been created by bootstrapping. Customize the branding (images and titles) here.
- `config/routes/attributes.yaml`: If you want to add your own routes (independent pages) for the project, you can add them here later. However, the bundle must be created first.
- `config/packages/security.yaml`: If you want to add your own roles for the project, you can add them here later. User-defined routes can also be secured here.

## Create your own bundle

The customer-specific code (elements, templates etc.) is located at `application/src`. For most projects one bundle is sufficient. According to convention, the bundle name is the name of the project followed by "Bundle". Create a PHP class in `application/src/MyCustomBundle.php` that just extends from symfony's bundle:

```php
namespace MyCustomer/MyCustomBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MyCustomBundle extends Bundle
{
}
```

If your customer code is very complex, for structuring purposes it can be useful to create several bundles. In this case, create a separate directory for each bundle and create the bundle files there. 

Register your bundle(s) in `config/bundles.php` by adding the line(s) 

```php
MyCustomer/MyCustomBundle::class => ['all' => true],
```

Also, adapt the `autoload` section of the top level composer.json file by replacing the second line (App) with your customer's namespace. Even if you have several bundles, adding one entry is sufficient if your bundles have the same top level namespace.

```php
"autoload": {
    "psr-4": {
        "": "bin/",
        "MyCustomer\\MyCustomBundle\\": "src/"
    }
},
```

Now you also need to replace the `App` namespace with your custom namespace in the following files:
- `src/Kernel.php`
- `public/index.php` (in use statement)
- `public/index_dev.php` (in use statement)
- `bin/console` (in use statement)
- `.env` (in KERNEL_CLASS)


Congratulations on achieving the first big step! In order for symfony to recognise your bundle, you might need to run 

```bash
bin/composer install
bin/console cache:clear
```

:warning: This example assumes you use Symfony's [Autowiring and Autoconfiguration](https://symfony.com/doc/6.4/service_container/autowiring.html). If you don't want to use this feature (it's recommended that you do for new projects), refer to [this guide](../architecture/bundles-without-autoconfiguration.md) since the bundle creation involves some more steps in this case.

## Next steps
- [Modify the behaviour of an existing element](../elements/overriding.md)


[↑ Back to top](#project-setup-guide)

[← Back to README](../README.md)

# Intro to ExtraTools

It a bundle that contains some usefull symfony2 tools.

## Features:

- a `bcc:trans:update` command that extract all your missing i18n message from your twig templates and saves into yaml, xliff, php or pot translation files.
- a twig extension that translates dates and contries

## Installation and configuration:

### Get the bundle

Add to your `/deps` file :

```
[BCCExtraToolsBundle]
    git=http://github.com/michelsalib/BCCExtraToolsBundle.git
    target=/bundles/BCC/ExtraToolsBundle
```

And make a `php bin/vendors install`.

### Register the namespace

``` php
<?php

    // app/autoload.php
    $loader->registerNamespaces(array(
        'BCC' => __DIR__.'/../vendor/bundles',
        // your other namespaces
    ));
```

### Add ExtraToolsBundle to your application kernel

``` php
<?php

    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new BCC\ExtraToolsBundle\BCCExtraToolsBundle(),
            // ...
        );
    }
```

### Register the twig extension

*If you want to use the twig extension you must have the apache intl module installed.*

Add to your `config.yml`:

``` yml
#BCC configuration
services:
    bcc.twig.extension:
        class: BCC\ExtraToolsBundle\Twig\TwigExtension
        tags:
            -  { name: twig.extension }
```

## Usage examples:

### bcc:trans:update command example

You now have the new command. You can use it as follows:

- To extract messages from your bundle and display in the console:

    `bcc:trans:update --dump-messages fr MyBundle`

- You can save them to the `MyBundle\Resources\translations` directory:

    `bcc:trans:update --force fr MyBundle`

- In another language:

    `bcc:trans:update --force en MyBundle`

- Specify the output format with the `--output-format` option (either `yml`, `xliff`, `php` or `pot`):

    `bcc:trans:update --output-format="xliff" --force en MyBundle`

- Change the prefix used for newly added messages with the `--prefix` option:

    `bcc:trans:update --output-format="xliff" --force --prefix='myprefix' en MyBundle`

### Twig extensions examples

Translate a date value :

- `{{ user.createdAt | localeDate }}` to have a medium date and no time, in the current locale

- `{{ user.createdAt | localeDate('long','medium') }}` to have a long date and medium time, in the current locale

Translate a contry :

- `{{ user.country | country }}` to have the country, in the current locale

- `{{ user.country | country('c
ountry does not exist') }}` Define the returned value if the country does not exist
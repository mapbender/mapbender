# Testing

## Prerequisites

### Development dependencies installed

You need to install composer development dependencies. This will not change the versions
of already installed packages, only add extra packages required to run tests.

You need to run

```sh
bin/composer install --dev
```

from your `application` directory.

You can revert back to a shipping state by running

```sh
bin/composer install --no-dev
```


## Running choices

Run all tests, including "functional" browser-simulating tests, by invoking in the application directory

```bash
bin/phpunit
```

Additional command line arguments are passed through to phpunit. Try

```bash
bin/phpunit --help
```

## Authoring tests

By default, _application/src_ (suite "Project") and the _mapbender/fom/owsproxy_ trinity of directories are scanned for *Test classes. They can be located anywhere, inside or outside of bundles, phpunit will find them.

Please [annotate your tests with `@group`](https://docs.phpunit.de/en/10.5/annotations.html#group) to appropriately preclassify them as either `unit` or `functional`. Additional groups may be stacked on top (a test case can be placed into more than one group).

If one group makes sense for all tests in your test case, you can annotate the class once, and all tests within it will inherit the setting.

[↑ Back to top](#testing)

[← Back to README](../README.md)

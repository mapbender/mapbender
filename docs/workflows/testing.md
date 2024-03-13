# Prerequisites

## Development dependencies installed

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

### Posix + bash + PHP>=5.6

It might work on gitbash and/or cygwin with some fiddling.

## Running choices

Run all tests, including "functional" browser-simulating tests, by invoking

```bash
bin/run_tests.sh
```

(that's the top-level bin, _not_ the application/bin)

This will require phantomjs on your path. If it cannot invoke phantomjs, it will bail and do nothing.

Run just the straightforward set of in-process tests by invoking

```bash
bin/run_tests-dev.sh
```

This one does not require phantomjs.

Both of these commands can be invoked from any current working directory you want.

You can create a symlink to a project's `bin/run_tests-dev.sh` somewhere in your path and get immediate access from anywhere.

Additional command line arguments are passed through to phpunit. Try

```bash
bin/run_tests-dev.sh --help
```

## Authoring tests

By default, `application/src` (suite "Project") and the `mapbender/fom/owsproxy` trinity of directories are scanned for *Test classes. They can be located anywhere, inside or outside of bundles, phpunit will find them.

Please [annotate your tests with `@group`](https://phpunit.de/manual/5.7/en/appendixes.annotations.html#appendixes.annotations.group) to appropriately preclassify them as either `unit` or `functional`. Additional groups may be stacked on top (a test case can be placed into more than one group).

If one group makes sense for all tests in your test case, you can annotate the class once, and all tests within it will inherit the setting.

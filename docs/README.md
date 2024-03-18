# Mapbender Development Documentation

## Introduction

Welcome to the Mapbender Development Documentation! This documentation is targeted at Mapbender developers and will cover useful topics not needed by administrators or users of Mapbender installations. It provides in-depth knowledge on how to contribute to (and tinker with) Mapbender's open source code. Before you start, please check out our [Conventions](getting_started/conventions.md). If you want to dive deep on a single page, please read the [Contributing Guide](CONTRIBUTING.md).

## Table of Contents

- [Commands](workflows/commands.md)
- [Components](architecture/components.md)
- [Contributing Guide](CONTRIBUTING.md)
- [Controllers](controllers/controllers.md)
- [Conventions](getting_started/conventions.md)
- [Directory structure](architecture/directory_structure.md)
- [Elements](elements/elements.md)
- [Request/Response Workflow](workflows/requestresponse.md)
- [Security](security/security.md)
- [Styleguides](style/)
- [Testing](workflows/testing.md)
- [Translation](workflows/translation.md)

## Things to consider

There are a couple of things you should be familiar with in order to contribute to Mapbender:

- **PHP**: We're using PHP which offers full object orientation.
- **Symfony**: This is what we build upon. Read [The Book](https://symfony.com/doc/current/index.html) to learn more about Symfony.
- **JavaScript**: We use jQuery a lot and especially the jQuery UI Widget factory. These are essential to understand and write maintainable JavaScript code.

## Mapbender Installation

The installation procedure from Git is described in the [Mapbender User Documentation](https://doc.mapbender.org/en/installation/installation_git.html).

## Modules and bundles

Please refer to the respective sections in the [Contributing Guide](CONTRIBUTING.md) to understand what [modules](CONTRIBUTING.md#modules) and what [bundles](CONTRIBUTING.md#bundles) are, and how the latter can be created.

## Twig

Symfony follows the template approach that we use in Mapbender. This uses a templating engine to generate HTML, CSS or other content. A template is a text file that can generate any text based format like HTML or XML. It is used to express presentational logic. You can use them to create a layout. You can create a base layout and then overwrite or append any of your layout blocks with individual templates.
Read more about Templates in Mapbender at [Templates](CONTRIBUTING.md#Templates) or in the [Contributing Guide](CONTRIBUTING.md#generate-translations) and find a good introduction about Twig in the [Symfony Template documentation](https://symfony.com/doc/current/templates.html).

## Getting Help

If you need more help, refer to one of the sources to deepen your Mapbender knowledge.

### Malinglists

- [Mapbender-Dev mailinglist](https://lists.osgeo.org/mailman/listinfo/mapbender_dev)
- [Mapbender-User mailinglist](https://lists.osgeo.org/mailman/listinfo/mapbender_users)

### Libraries and frameworks

- [Symfony framework](https://www.symfony.com/)
- [PHPUnit documentation](https://phpunit.de/)
- [Composer documentation](https://getcomposer.org/doc/)
- [General GitHub documentation](https://help.github.com/)
- [GitHub pull request documentation](https://help.github.com/send-pull-requests/)

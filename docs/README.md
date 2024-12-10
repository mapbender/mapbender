# Mapbender Development Documentation

## Introduction

Welcome to the Mapbender Development Documentation! This documentation is intended for Mapbender developers and covers topics that are not necessary for administrators or users of Mapbender installations. It provides in-depth knowledge on how to contribute to and modify Mapbender's open source code. Before you begin, make sure to review our [Conventions](getting_started/conventions.md) and familiarize yourself with the basics of collaborative work. If you want to dive deeper into a specific topic, be sure to read the [Contributing Guide](CONTRIBUTING.md).

## Table of Contents

- [API](api/setup.md)
- [Commands](workflows/commands.md)
- [Components](architecture/components.md)
- [Contributing Guide](CONTRIBUTING.md)
- [Controllers](controllers/controllers.md)
- [Conventions](getting_started/conventions.md)
- [Directory structure](architecture/directory_structure.md)
- [Elements](elements/elements.md)
- [FAQs](getting_started/faq.md)
- [Git Archive](gitarchive/gitarchive.md)
- [Project Setup Guide](workflows/setup.md)
- [Request/Response Workflow](workflows/requestresponse.md)
- [Security](security/security.md)
- [Styleguides](style/)
- [Testing](workflows/testing.md)
- [Translation](workflows/translation.md)
- [Upgrading Guide](UPGRADING.md)

## Things to consider

To contribute to Mapbender, it is essential to have a solid understanding of object-oriented PHP and Symfony, the framework we use.

You can find more information about Symfony by reading [The Book](https://symfony.com/doc/current/index.html). We often rely on jQuery, especially the jQuery UI Widget factory, in our JavaScript code for legacy reasons.

## Mapbender Installation

The installation procedure from Git is described in the [Mapbender User Documentation](https://doc.mapbender.org/en/installation/installation_git.html).

## Modules and bundles

Please refer to the respective sections in the [Contributing Guide](CONTRIBUTING.md) to understand what [modules](CONTRIBUTING.md#modules) and what [bundles](CONTRIBUTING.md#bundles) are, and how the latter can be created.

## Twig

Symfony follows the template approach that we use in Mapbender. This uses a templating engine to generate HTML, CSS or other content. A template is a text file that can generate any text based format like HTML or XML. It is used to express presentational logic. You can use them to create a layout. You can create a base layout and then overwrite or append any of your layout blocks with individual templates.
Read more about Templates in Mapbender in the [Contributing Guide](CONTRIBUTING.md#templates) and find a good introduction about Twig in the [Symfony Template documentation](https://symfony.com/doc/current/templates.html).

## Getting Help

For further assistance, consult one of the sources to enhance your understanding of Mapbender.

### Malinglists

- [Mapbender-Dev mailinglist](https://lists.osgeo.org/mailman/listinfo/mapbender_dev)
- [Mapbender-User mailinglist](https://lists.osgeo.org/mailman/listinfo/mapbender_users)

### Libraries and frameworks

- [Symfony framework](https://www.symfony.com/)
- [PHPUnit documentation](https://phpunit.de/)
- [Composer documentation](https://getcomposer.org/doc/)
- [General GitHub documentation](https://help.github.com/)
- [GitHub pull request documentation](https://help.github.com/send-pull-requests/)

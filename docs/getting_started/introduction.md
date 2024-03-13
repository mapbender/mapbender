# Introduction

This book is targeted at Mapbender developers and will cover useful topics not needed by administrators or users of Mapbender installations.

## Things to consider

There are a couple of things you should be familiar with in order to contribute to Mapbender:

* Object-Orientated PHP: We're using PHP which offers full object orientation.
* Symfony: This is what we build upon. So read `The Book <https://symfony.com/doc/current/index.html>`_ to learn more about Symfony.
* JavaScript: We use jQuery a lot and especially the jQuery UI widget factory. These are essential to understand to write maintainable JavaScript code.

## Installation

The installation procedure from Git is described in [Mapbender User Documentation](https://doc.mapbender.org/en/installation/installation_git.html)

## Modules and bundles

Please refer to the respective sections in the `CONTRIBUTING.md` guide to understand what `modules <https://github.com/mapbender/mapbender-starter/blob/master/CONTRIBUTING.md#modules>` and what `bundles <https://github.com/mapbender/mapbender-starter/blob/master/CONTRIBUTING.md#bundles>` are, and how the latter can be `created <https://github.com/mapbender/mapbender-starter/blob/master/CONTRIBUTING.md#bundle-creation>`_.

## Twig

Symfony follows the template approach and we use this in Mapbender. Symfony uses a templating engine to generate HTML, CSS or other content.
A template is a text file that can generate any text based format like HTML, XML. It is used to express presentation and not programm logic.
You can use them to create a layout. You can create a base layout and then overwrite or append any of your layout blocks with individual templates.
Read more about Templates in Mapbender at [Templates](/CONTRIBUTING.md#Templates)
or in the `Contributing Guide <https://github.com/mapbender/mapbender-starter/blob/master/CONTRIBUTING.md#generate-translations>` and find a good introduction about Twig in the `Symfony Template documentation <https://symfony.com/doc/current/templates.html>`_.

## Getting Help

Malinglists:

* `Mapbender-Developer and -User mailinglist <https://mapbender.org/?q=en/community>`_

Libraries and frameworks:

* `Symfony framework <https://www.symfony.com/>`_
* `PHPUnit documentation <https://phpunit.de/>`_
* `Composer documentation <https://getcomposer.org/doc/>`_
* `General GitHub documentation <https://help.github.com/>`_
* `GitHub pull request documentation <https://help.github.com/send-pull-requests/>`_

## Topics

.. toctree::
   :maxdepth: 1

   backend -> controllers
   conventions -> getting started
   elements -> elements
   frontend -> workflows
   getting_started -> introduction
   requestresponse -> workflows
   security -> security

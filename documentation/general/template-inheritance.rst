Template Inheritance
====================

Mapbender3 uses template inheritance to achieve a common look and feel (and
layout). To help you getting started understanding the template stack and thus
enable you to override individual templates without having to mess with the
files provided in the bundles this document will give you an overview of the
most common templates and their dependencies.
To learn how to overwrite templates inside bundles without having to alter
them directly, see the Symfony2 book on templating. TODO: URL

The base template
-----------------
This is the template from which all over templates generating HTML in
Mapbender3 derive. You can adress it with the URL "::base.html.twig" and thus
it can be found in the application/app/Resources/views folder.

You can easily adapt it to your needs as long as you provide these blocks:

- baseHead, which will output content which needs to go inside the HTML head
  tag.
- baseContent, which will output content which needs to go inside the HTML
  body tag.

The standard template is great as it is and provides an special class to the
body tag when displayed inside Internet Explorer <= 9, which make it easy to
write IE-targeting CSS.

The backend template
--------------------
The backend template is used for all backend pages, i.e. the administration.
You can adress it with the URL "MapbenderCoreBundle::backend.html.twig".
You can find the original in the application/mapbender/src/Mapbender/CoreBundle/Resources/views/
folder.

The template provides the following blocks for your use:

- title, which I don't need to explain. The title will be suffixed with the
  server_name parameter taken from your parameters.ini.
- css, which can be used to output more CSS links.
- content, which outputs the main content of each page. Before the content
  you can find a administration menu rendered by this template.
- js, which can be used to append more Javascript tags. These are put after
  the content for faster page rendering.

The frontend template
---------------------
The frontend template is the base for all application templates.
You can adress it with the URL "MapbenderCoreBundle::frontend.html.twig".
You can find the original in the application/mapbender/src/Mapbender/CoreBundle/Resources/views/
folder.

The template provides the following blocks for your use:

- title, which I don't need to explain. The title will be suffixed with the
  server_name parameter taken from your parameters.ini.
- css, which can be used to output more CSS links.
- content, which outputs the main content of each page.
- js, which can be used to append more Javascript tags. These are put after
  the content for faster page rendering.


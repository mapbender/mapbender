Activity Indicator
******************

The activity indicator provides a simple widget showing background activity (Ajax calls and pending map tile requests).
In the default configuration it uses a spinner GIF to work. This can be easily modified by overriding the CSS for the 
widget.

Class, Widget & Style
==============

* Class: Mapbender\\CoreBundle\\Element\\ActivityIndicator
* Widget: mapbender.element.activityindicator.js
* Style: mapbender.elements.css

Configuration
=============

<Put YAML configuration here, include defaults and explain>
.. code-block:: yaml

    activityClass: mb-activity          # CSS class to indicate activity (Ajax or tile)
    ajaxActivityClass: mb-activity-ajax # CSS class to indicate Ajax activity
    tileActivityClass: mb-activity-tile # CSS class to indicate tile loading activity


HTTP Callbacks
==============

None.

JavaScript API
==============

None.

JavaScript Signals
==================

None.

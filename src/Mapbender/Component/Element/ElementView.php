<?php


namespace Mapbender\Component\Element;


/**
 * Base class for service-type Element frontend views.
 */
abstract class ElementView
{
    public $attributes = array();
    public $cacheable = true;
}

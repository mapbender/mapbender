<?php


namespace Mapbender\CoreBundle\Element;

/**
 * Migration bridge for inheriting classes after class rename. Not assignable to applications.
 * Child classes should inherit from Sketch directly.
 *
 * @deprecated
 * @internal
 */
class Redlining extends Sketch
{
}

<?php

/**
 * License Block - Todo
 */

namespace Mapbender;

use Mapbender\Component\Foo;

/**
 * Just an API-doc example (Oneline description used in lists)
 *
 * Bar is just an API documentation example to serve as a guideline,
 * inspiration and to be included in the developer's book.
 *
 * This file is directly included in the documentation. Yeah!
 *
 * @todo Make me better
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class Example extends Foo
{
    /**
     * Constructor
     *
     * Does some magic.
     */
    public function __construct()
    {
    }

    /**
     * Magic function
     *
     * Does some pretty awesome magic.
     *
     * @param array $input input data
     * @return string Result string
     *
     * @deprecated use newMagic instead
     */
    public function oldMagic(array $input)
    {
    }

    /**
     * Magic function
     *
     * Does some pretty awesome magic, only better.
     *
     * @param array $input input data
     * @return string Result string
     *
     * @todo make faster
     */
    public function newMagic(array $input)
    {
    }
}


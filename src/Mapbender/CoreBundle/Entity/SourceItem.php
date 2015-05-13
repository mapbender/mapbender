<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Entity;

use Mapbender\CoreBundle\Entity\Source;

/**
 *
 * @author Paul Schmidt
 */
abstract class SourceItem
{
    /**
     *
     * @var Source source
     */
    protected $source;

    /**
     * Set Source
     * @param Source $source the source
     * @return Source
     */
    abstract public function setSource(Source $source);

    /**
     * Get Source
     *
     * @return Source
     */
    abstract public function getSource();
}

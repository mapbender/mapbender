<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

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
     * @param $source the source
     * @return Source
     */
    public abstract function setSource(Source $source);

    /**
     * Get Source
     *
     * @return Source 
     */
    public abstract function getSource();
}

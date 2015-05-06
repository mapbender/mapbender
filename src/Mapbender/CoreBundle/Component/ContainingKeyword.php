<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 *
 * @author Paul Schmidt
 */
interface ContainingKeyword
{

    /**
     * Returns an id.
     */
    public function getId();

    /**
     * Sets keywords
     *
     * @param ArrayCollection $keywords collections of keywords
     */
    public function setKeywords(ArrayCollection $keywords);

    /**
     * Returns keywords.
     *
     * @return ArrayCollection 
     */
    public function getKeywords();

    /**
     * Adds a keyword into keywords collection.
     *
     * @param Keyword $keyword
     */
    public function addKeyword(Keyword $keyword);
}

<?php


namespace Mapbender\Component\Application;


use Mapbender\CoreBundle\Entity\Element;

class ElementBucket
{
    /** @todo: use region descriptor object instead of string */
    /** @var mixed */
    protected $region;

    /** @var Element[] */
    protected $elements;

    public function __construct($region)
    {
        $this->region = $region;
        $this->elements = array();
    }

    /**
     * @param Element $element
     */
    public function addElement($element)
    {
        $this->elements[] = $element;
    }

    /**
     * @return Element[]
     */
    public function getElements()
    {
        return $this->elements;
    }
}

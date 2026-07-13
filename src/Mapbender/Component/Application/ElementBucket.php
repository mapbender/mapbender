<?php


namespace Mapbender\Component\Application;


use Mapbender\CoreBundle\Entity\Element;

class ElementBucket
{
    /** @var Element[] */
    protected array $elements;

    /**
     * @param mixed $region
     */
    public function __construct(protected $region)
    {
        $this->elements = [];
    }

    /**
     * @param Element $element
     */
    public function addElement($element): void
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

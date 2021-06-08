<?php


namespace Mapbender\Component\Application;


use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Exception\Application\MissingMapElementException;
use Mapbender\Exception\Application\MultipleMapElementsException;

class ElementDistribution
{
    /** @var Element|null */
    protected $mapElement;
    /** @var ElementBucket[] */
    protected $anchoredContentElements;
    /** @var ElementBucket */
    protected $unanchoredContentElements;
    /** @var ElementBucket[] */
    protected $nonContentRegionMap;

    /**
     * @param Element[] $elements
     */
    public function __construct($elements)
    {
        $this->mapElement = null;
        $this->nonContentRegionMap = array();
        $this->anchoredContentElements = array();
        $this->unanchoredContentElements = new ElementBucket('content');    /** @todo: use region descriptor object instead of string */
        foreach ($elements as $element) {
            if (\is_a($element->getClass(), 'Mapbender\Component\Element\MainMapElementInterface', true)) {
                if ($this->mapElement) {
                    throw new MultipleMapElementsException("Invalid application: multiple map elements");
                }
                $this->mapElement = $element;
                $bucket = null;
            } else {
                $bucket = $this->selectBucket($element);
                $bucket->addElement($element);
            }
        }
    }

    /**
     * @param string $regionName
     * @return ElementBucket|null
     */
    public function getRegionBucketByName($regionName)
    {
        switch ($regionName) {
            case 'content':
                return $this->unanchoredContentElements;
            default:
                if (!empty($this->nonContentRegionMap[$regionName])) {
                    return $this->nonContentRegionMap[$regionName];
                } else {
                    return null;
                }
        }
    }

    /**
     * @param string $anchorValue
     * @return Element[]
     */
    public function getFloatingElements($anchorValue)
    {
        if (!empty($this->anchoredContentElements[$anchorValue])) {
            return $this->anchoredContentElements[$anchorValue]->getElements();
        } else {
            return array();
        }
    }

    /**
     * @return Element
     * @throws MissingMapElementException
     */
    public function getMapElement()
    {
        if (!$this->mapElement) {
            throw new MissingMapElementException("Invalid application: missing map element");
        }
        return $this->mapElement;
    }

    /**
     * @param Element $element
     * @return ElementBucket
     */
    protected function selectBucket(Element $element)
    {
        $region = $element->getRegion();
        if ($region !== 'content') {
            if (!isset($this->nonContentRegionMap[$region])) {
                $this->nonContentRegionMap[$region] = new ElementBucket($region);
            }
            return $this->nonContentRegionMap[$region];
        } else {
            /** @todo: use FloatableElement interface to detect, not "anchor" */
            $config = $element->getConfiguration();
            if (!empty($config['anchor'])) {
                $anchor = $config['anchor'];
                if (!isset($this->anchoredContentElements[$anchor])) {
                    $this->anchoredContentElements[$anchor] = new ElementBucket($region);   /** @todo: use region descriptor object instead of string */
                }
                return $this->anchoredContentElements[$anchor];
            } else {
                return $this->unanchoredContentElements;
            }
        }
    }
}

<?php


namespace Mapbender\Component\Collections;


use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\Component\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Layerset;

class YamlSourceInstanceCollection extends AbstractLazyCollection
{
    /** @var SourceInstanceFactory */
    protected $factory;
    /** @var Layerset */
    protected $layerset;
    /** @var array */
    protected $data;

    /**
     * @param SourceInstanceFactory $factory
     * @param Layerset $layerset
     * @param array $data
     */
    public function __construct(SourceInstanceFactory $factory, Layerset $layerset, $data)
    {
        $this->factory = $factory;
        $this->layerset = $layerset;
        $this->data = $data;
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollection();
        $weight = 0;
        foreach ($this->data as $instanceId => $instanceDefinition) {
            $instance = $this->factory->fromConfig($instanceDefinition, $instanceId);
            $instance->setLayerset($this->layerset);
            $instance->setWeight($weight++);
            $this->collection->add($instance);
        }
    }
}

<?php


namespace Mapbender\Component\Collections;


use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Layerset;

class YamlSourceInstanceCollection extends AbstractLazyCollection
{

    public function __construct(
        protected TypeDirectoryService $directoryService,
        protected Layerset             $layerset,
        protected array                $data)
    {
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollection();
        $weight = 0;
        foreach ($this->data as $instanceId => $instanceDefinition) {
            if (!isset($instanceDefinition['type'])) {
                throw new \InvalidArgumentException("Source definition is missing required 'type' key");
            }
            $factory = $this->directoryService->getInstanceFactoryByType($instanceDefinition['type']);
            $instance = $factory->fromConfig($instanceDefinition, $instanceId);
            $instance->setLayerset($this->layerset);
            $instance->setWeight($weight++);
            $instance->setYamlRoles(array_key_exists('roles', $instanceDefinition) ? $instanceDefinition['roles'] : array());
            $this->collection->add($instance);
        }
    }
}

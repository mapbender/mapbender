<?php


namespace Mapbender\Component\Collections;


use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class YamlElementCollection extends AbstractLazyCollection
{
    /** @var ElementFactory */
    protected $factory;
    /** @var Application */
    protected $application;
    /** @var array */
    protected $data;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ElementFactory $factory
     * @param Application $application
     * @param array $data
     * @param LoggerInterface|null $logger
     */
    public function __construct(ElementFactory $factory, Application $application, $data, LoggerInterface $logger = null)
    {
        $this->factory = $factory;
        $this->application = $application;
        $this->data = $data;
        $this->logger = $logger ?: new NullLogger();
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollection();
        foreach ($this->data as $region => $elementsDefinition) {
            $weight = 0;
            foreach ($elementsDefinition ?: array() as $id => $elementDefinition) {
                $element = $this->createElement($id, $region, $elementDefinition);
                if (!$element) {
                    continue;
                }
                $element->setWeight($weight++);
                $element->setApplication($this->application);
                $element->setYamlRoles(array_key_exists('roles', $elementDefinition) ? $elementDefinition['roles'] : array());
                $this->collection->add($element);
            }
        }
    }

    /**
     * @param string $id
     * @param string $region
     * @param mixed[] $configuration
     * @return Element
     */
    protected function createElement($id, $region, $configuration)
    {
        $title = ArrayUtil::getDefault($configuration, 'title', false);
        $className = $configuration['class'];
        unset($configuration['class']);
        unset($configuration['title']);
        try {
            $element = $this->factory->newEntity($className, $region);
            $element->setConfiguration($configuration);
            $element->setId($id);
            $elComp = $this->factory->componentFromEntity($element);

            if (!$title) {
                $title = $elComp->getTitle();
            }
            // Do not use original $configuration array. Configuration may already have been modified once implicitly.
            /** @see ConfigMigrationInterface */
            $defaults = $elComp->getDefaultConfiguration();
            $configInitial = $element->getConfiguration();
            $mergedConfig = array_replace($defaults, array_filter($configInitial, function($v) {
                return $v !== null;
            }));
            // Quirks mode: add back NULL values where the defaults didn't even have the corresponding key
            foreach (array_keys($configInitial) as $key) {
                if (!array_key_exists($key, $mergedConfig)) {
                    assert($configInitial[$key] === null);
                    $mergedConfig[$key] = null;
                }
            }
            $element->setConfiguration($mergedConfig);
            $element->setTitle($title);
            return $element;
        } catch (ElementErrorException $e) {
            $this->logger->warning("Your YAML application contains an invalid Element {$className}: {$e->getMessage()}");
            return null;
        }
    }
}

<?php


namespace Mapbender\Component\Collections;


use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\Common\Collections\Selectable;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\FrameworkBundle\Component\ElementEntityFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class YamlElementCollection extends AbstractLazyCollection implements Selectable
{
    protected LoggerInterface $logger;

    /**
     * @param ElementEntityFactory $factory
     * @param Application $application
     * @param array $data
     * @param LoggerInterface|null $logger
     */
    public function __construct(protected ElementEntityFactory $factory, protected Application $application, protected $data, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    protected function doInitialize(): void
    {
        $this->collection = new ArrayCollection();
        foreach ($this->data as $region => $elementsDefinition) {
            $weight = 0;
            foreach ($elementsDefinition ?: [] as $id => $elementDefinition) {
                $element = $this->createElement($id, $region, $elementDefinition);
                if (!$element) {
                    continue;
                }
                $element->setWeight($weight++);
                $element->setApplication($this->application);
                $element->setYamlRoles(array_key_exists('roles', $elementDefinition) ? $elementDefinition['roles'] : []);
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
    protected function createElement($id, $region, array $configuration)
    {
        $title = ArrayUtil::getDefault($configuration, 'title', false);
        $className = $configuration['class'];
        $screenType = ArrayUtil::getDefault($configuration, 'screenType', false);
        unset($configuration['class']);
        unset($configuration['title']);
        unset($configuration['screenType']);
        try {
            $element = $this->factory->newEntity($className, $region);
            $element->setId($id);
            $this->configureElement($element, $configuration);
            if ($title) {
                $element->setTitle($title);
            }
            if ($screenType) {
                $element->setScreenType($screenType);
            }
            return $element;
        } catch (ElementErrorException $e) {
            $this->logger->warning("Your YAML application contains an invalid Element {$className}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function matching(Criteria $criteria): ReadableCollection&Selectable
    {
        $this->initialize();
        return $this->collection->matching($criteria);
    }

    /**
     * @param Element $element
     * @param mixed[] $configuration
     */
    protected function configureElement(Element $element, array $configuration)
    {
        $defaults = $element->getConfiguration() ?: [];
        // Replace non-null top level values
        $mergedConfig = array_replace($defaults, array_filter($configuration, fn($v): bool => $v !== null));
        // Quirks mode: add back NULL values where the defaults didn't even have the corresponding key
        foreach (array_keys($configuration) as $key) {
            if (!array_key_exists($key, $mergedConfig)) {
                assert($configuration[$key] === null);
                $mergedConfig[$key] = null;
            }
        }
        $element->setConfiguration($mergedConfig);
    }
}

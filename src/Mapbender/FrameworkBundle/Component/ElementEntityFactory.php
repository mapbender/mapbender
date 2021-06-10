<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Translation\TranslatorInterface;


class ElementEntityFactory
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(ElementFilter $elementFilter,
                                TranslatorInterface $translator)
    {
        $this->elementFilter = $elementFilter;
        $this->translator = $translator;
    }

    /**
     * @param $componentClass
     * @param $region
     * @param Application|null $application
     * @return Element
     */
    public function newEntity($componentClass, $region, Application $application = null)
    {
        /** @var string|MinimalInterface $componentClass */
        $componentClass = $this->elementFilter->getAdjustedElementClassName($componentClass);
        if (!$componentClass || !ClassUtil::exists($componentClass)) {
            throw new UndefinedElementClassException($componentClass);
        }

        $entity = new Element();
        $configuration = $componentClass::getDefaultConfiguration();
        $entity
            ->setClass($componentClass)
            ->setRegion($region)
            ->setWeight(0)
            ->setConfiguration($configuration)
        ;
        if (!$componentClass || !\is_a($componentClass, 'Mapbender\CoreBundle\Element\ControlButton')) {
            // Leave title empty. Will be resolved to target title when rendering
            // @todo: make title column nullable (will require schema update)
            $entity->setTitle('');
        } else {
            // @todo: reevaluate translation; translation should be done on presentation, not persisted
            $entity->setTitle($this->translator->trans($this->elementFilter->getDefaultTitle($entity)));
        }
        if ($application) {
            $entity->setApplication($application);
        }
        return $entity;
    }
}

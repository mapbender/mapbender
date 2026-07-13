<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Contracts\Translation\TranslatorInterface;


class ElementEntityFactory
{
    public function __construct(protected ElementFilter $elementFilter, protected TranslatorInterface $translator)
    {
    }

    /**
     * @param $className
     * @param $region
     * @param Application|null $application
     * @return Element
     */
    public function newEntity($className, $region, ?Application $application = null): Element
    {
        $canonicalClass = $this->elementFilter->getInventory()->getCanonicalClassName($className);
        $entity = new Element();
        $entity
            ->setClass($canonicalClass)
            ->setRegion($region)
            ->setWeight(0)
        ;
        /** @var string|MinimalInterface $handlingClass */
        $handlingClass = $this->elementFilter->getHandlingClassName($entity);
        if (!$handlingClass || !ClassUtil::exists($handlingClass)) {
            throw new UndefinedElementClassException($handlingClass);
        }
        $entity->setConfiguration($handlingClass::getDefaultConfiguration());

        if (!$handlingClass || !\is_a($handlingClass, 'Mapbender\CoreBundle\Element\ControlButton')) {
            // Leave title empty. Will be resolved to target title when rendering
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

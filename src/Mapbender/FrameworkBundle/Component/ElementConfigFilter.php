<?php


namespace Mapbender\FrameworkBundle\Component;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\Exception\UndefinedElementClassException;
use Mapbender\CoreBundle\Entity\Element;

class ElementConfigFilter
{
    public function getHandlingClassName(Element $element)
    {
        if ($element->getClass() && $element->getClass() === 'Mapbender\CoreBundle\Element\Button') {
            $config = $element->getConfiguration();
            if (!empty($config['click'])) {
                return 'Mapbender\CoreBundle\Element\LinkButton';
            } else {
                return 'Mapbender\CoreBundle\Element\ControlButton';
            }
        } else {
            return $element->getClass();
        }
    }

    protected function migrateConfigInternal(Element $element, $handlingClass)
    {
        if (!$handlingClass || !ClassUtil::exists($handlingClass)) {
            throw new UndefinedElementClassException($handlingClass);
        }
        if (\is_a($handlingClass, 'Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface', true)) {
            /** @var string|\Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface $handlingClass */
            $handlingClass::updateEntityConfig($element);
        }
    }
}

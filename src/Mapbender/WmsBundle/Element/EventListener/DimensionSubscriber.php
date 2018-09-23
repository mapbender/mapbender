<?php

namespace Mapbender\WmsBundle\Element\EventListener;

use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\FormInterface;

/**
 * DimensionSubscriber class
 */
class DimensionSubscriber extends AbstractDimensionSubscriber
{
    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    protected function addFields($form, $data)
    {
        $form
            ->add('name', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'name',
                ),
            ))
            ->add('units', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'units',
                ),
            ))
            ->add('unitSymbol', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'unitSymbol',
                ),
            ))
            ->add('default', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'default',
                ),
            ))
            ->add('multipleValues', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'multipleValues',
                ),
            ))
            ->add('nearestValue', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'nearestValue',
                ),
            ))
            ->add('current', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'current',
                ),
            ))
            ->add('type', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'type',
                ),
            ))
            ->add('active', 'hidden', array(
                'auto_initialize' => false,
                'attr' => array(
                    'data-name' => 'active',
                ),
            ))
        ;
        $this->addExtentFields($form, $data);
    }
}

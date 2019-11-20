<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WmtsInstanceInstanceLayersType extends AbstractType
{
    public function getParent()
    {
        return 'Mapbender\ManagerBundle\Form\Type\SourceInstanceType';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('layers', 'Mapbender\ManagerBundle\Form\Type\SourceInstanceLayerCollectionType', array(
                'entry_type' => 'Mapbender\WmtsBundle\Form\Type\WmtsInstanceLayerType',
                'entry_options' => array(
                    'data_class' => 'Mapbender\WmtsBundle\Entity\WmtsInstanceLayer',
                ),
            ))
        ;
    }
}

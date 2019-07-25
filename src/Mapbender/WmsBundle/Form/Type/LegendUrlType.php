<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LegendUrlType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'legendurl';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('width', 'text', array(
                'required' => false,
            ))
            ->add('height', 'text', array(
                'required' => false,
            ))
            ->add('onlineResource', new OnlineResourceType(), array(
                'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource',
            ))
        ;
    }

}


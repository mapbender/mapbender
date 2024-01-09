<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LegendUrlType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.admin.legendurltype.width',
            ))
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.core.admin.legendurltype.height',
            ))
            ->add('onlineResource', 'Mapbender\WmsBundle\Form\Type\OnlineResourceType', array(
                'data_class' => 'Mapbender\WmsBundle\Component\OnlineResource',
                'label' => 'mb.core.admin.legendurltype.onlineresource',
            ))
        ;
    }

}


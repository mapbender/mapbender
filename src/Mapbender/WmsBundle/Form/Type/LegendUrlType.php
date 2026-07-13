<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Mapbender\WmsBundle\Component\OnlineResource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LegendUrlType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('width', TextType::class, [
                'required' => false,
                'label' => 'mb.core.admin.legendurltype.width',
            ])
            ->add('height', TextType::class, [
                'required' => false,
                'label' => 'mb.core.admin.legendurltype.height',
            ])
            ->add('onlineResource', OnlineResourceType::class, [
                'data_class' => OnlineResource::class,
                'label' => 'mb.core.admin.legendurltype.onlineresource',
            ])
        ;
    }

}


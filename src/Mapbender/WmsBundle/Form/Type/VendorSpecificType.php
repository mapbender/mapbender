<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\WmsBundle\Component\VendorSpecific as VS;

class VendorSpecificType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'vstype' => VS::TYPE_VS_SIMPLE,
            'hidden' => false,
            'data_class' => 'Mapbender\WmsBundle\Component\VendorSpecific',
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vstype', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'label' => 'mb.core.vendorspecifictype.admin.vstype',
                'required' => true,
                'choices' => array(
                    VS::TYPE_VS_SIMPLE => VS::TYPE_VS_SIMPLE,
                    VS::TYPE_VS_USER => VS::TYPE_VS_USER,
                    VS::TYPE_VS_GROUP => VS::TYPE_VS_GROUP,
                ),
            ))
            ->add('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.core.vendorspecifictype.admin.name',
            ))
            ->add('default', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.core.vendorspecifictype.admin.default',
            ))
            ->add('hidden', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.vendorspecifictype.admin.hidden',
            ))
        ;
    }

}

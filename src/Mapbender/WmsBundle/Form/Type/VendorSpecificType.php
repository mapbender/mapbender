<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
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
        $resolver->setDefaults([
            'vstype' => VS::TYPE_VS_SIMPLE,
            'hidden' => false,
            'data_class' => VS::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vstype', ChoiceType::class, [
                'label' => 'mb.core.vendorspecifictype.admin.vstype',
                'required' => true,
                'choices' => [
                    VS::TYPE_VS_SIMPLE => VS::TYPE_VS_SIMPLE,
                    VS::TYPE_VS_USER => VS::TYPE_VS_USER,
                    VS::TYPE_VS_GROUP => VS::TYPE_VS_GROUP,
                ],
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'mb.core.vendorspecifictype.admin.name',
            ])
            ->add('default', TextType::class, [
                'required' => true,
                'label' => 'mb.core.vendorspecifictype.admin.default',
            ])
            ->add('hidden', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.vendorspecifictype.admin.hidden',
            ])
        ;
    }

}

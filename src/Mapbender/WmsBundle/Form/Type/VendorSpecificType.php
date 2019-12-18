<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Form\DataTransformer\VendorSpecificTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\WmsBundle\Component\VendorSpecific as VS;

class VendorSpecificType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'name' => '',
            'vstype' => VS::TYPE_VS_SIMPLE,
            'hidden' => false,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('vstype', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'choices' => array(
                    VS::TYPE_VS_SIMPLE => VS::TYPE_VS_SIMPLE,
                    VS::TYPE_VS_USER => VS::TYPE_VS_USER,
                    VS::TYPE_VS_GROUP => VS::TYPE_VS_GROUP,
                ),
                'choices_as_values' => true,
            ))
            ->add('name', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('default', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
            ->add('hidden', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->addModelTransformer(new VendorSpecificTransformer())
        ;
    }

}

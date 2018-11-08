<?php

namespace Mapbender\WmsBundle\Form\Type;

use Mapbender\WmsBundle\Form\DataTransformer\VendorSpecificTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\WmsBundle\Component\VendorSpecific as VS;

/**
 * VendorSpecificInstType class
 */
class VendorSpecificType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return "vendorspecific";
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
            ->add('vstype', 'choice', array(
                'required' => true,
                'choices' => array(
                    VS::TYPE_VS_SIMPLE => VS::TYPE_VS_SIMPLE,
                    VS::TYPE_VS_USER => VS::TYPE_VS_USER,
                    VS::TYPE_VS_GROUP => VS::TYPE_VS_GROUP
                ),
            ))
            ->add('name', 'text', array(
                'required' => true,
            ))
            ->add('default', 'text', array(
                'required' => true,
            ))
            ->add('hidden', 'checkbox', array(
                'required' => false,
            ))
            ->addModelTransformer(new VendorSpecificTransformer());
    }

}

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
            'type' => VS::TYPE_SINGLE,
            'name' => '',
            'extent' => '',
            'vstype' => VS::TYPE_VS_SIMPLE,
            'usetunnel' => false,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice',
                      array(
                'required' => true,
                'choices' => array(
                    VS::TYPE_SINGLE => VS::TYPE_SINGLE,
                    VS::TYPE_MULTIPLE => VS::TYPE_MULTIPLE,
                    VS::TYPE_INTERVAL => VS::TYPE_INTERVAL)))
            ->add('name', 'text', array(
                'required' => true))
            ->add('default', 'text', array(
                'required' => true,))
            ->add('extent', 'text', array(
                'required' => true,))
            ->add('vstype', 'choice',
                  array(
                'required' => true,
                'choices' => array(
                    VS::TYPE_VS_SIMPLE => VS::TYPE_VS_SIMPLE,
                    VS::TYPE_VS_USERNAME => VS::TYPE_VS_USERNAME,
                    VS::TYPE_VS_GROUPNAME => VS::TYPE_VS_GROUPNAME)))
            ->add('usetunnel', 'checkbox',
                  array(
                'required' => false))
            ->addModelTransformer(new VendorSpecificTransformer());
    }

}

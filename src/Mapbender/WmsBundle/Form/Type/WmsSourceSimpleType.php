<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * WmsSourceSimpleType class
 */
class WmsSourceSimpleType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmssource';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                // Base data
                ->add('originUrl', 'text',
                      array(
                    'required' => true,
                    'attr' => array(
                        'title' => 'The wms GetCapabilities url.')))
                ->add('username', 'text',
                      array(
                    'required' => false,
                    'attr' => array(
                        'title' => 'The usename.')))
                ->add('password', 'text',
                      array(
                    'required' => false,
                    'attr' => array(
                        'title' => 'The password.')));
    }

}


<?php

namespace Mapbender\WmtsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * WmtsSourceSimpleType class
 */
class WmtsSourceSimpleType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'wmtssource';
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Base data
            ->add('onlyvalid', 'checkbox',
                array(
                'mapped' => false,
                'data' => true,
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                    . 'title bar and in lists.')))
            ->add('originUrl', 'text',
                array(
                'required' => true,
                'attr' => array(
                    'title' => 'The wmts GetCapabilities url.')))
            ->add('username', 'text',
                array(
                'required' => false,
                'attr' => array(
                    'title' => 'The username.',
                    'autocomplete' => 'off')))
            ->add('password', 'password',
                array(
                'required' => false,
                'attr' => array(
                    'title' => 'The password.',
                    'autocomplete' => 'off')));
    }

}

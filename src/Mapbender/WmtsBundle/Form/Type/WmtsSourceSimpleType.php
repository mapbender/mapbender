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
            ->add('originUrl', 'text', array(
                'required' => true,
                'label' => 'mb.wmts.wmtsloader.repo.form.label.serviceurl',
                'attr' => array(
                    'title' => 'The wmts GetCapabilities url',
                ),
            ))
            ->add('username', 'text', array(
                'required' => false,
                'label' => 'mb.wmts.wmtsloader.repo.form.label.username',
                'attr' => array(
                    'title' => 'The username.',
                    'autocomplete' => 'off',
                ),
            ))
            ->add('password', 'password',
                array(
                'required' => false,
                'label' => 'mb.wmts.wmtsloader.repo.form.label.password',
                'attr' => array(
                    'autocomplete' => 'off',
                ),
            ))
        ;
    }

}

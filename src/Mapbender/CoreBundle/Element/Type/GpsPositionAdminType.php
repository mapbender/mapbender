<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class GpsPositionAdminType
 * @package Mapbender\CoreBundle\Element\Type
 */
class GpsPositionAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'gpsposition';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'average'     => 1
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'text', array('required' => false))
            ->add('label', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.gpsposition.show_label',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('autoStart', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.element.autostart',
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add(
                'target',
                'target_element',
                array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false
                )
            )
            ->add('icon', new IconClassType(), array('required' => false))
            ->add('action', 'text', array('required' => false))
            ->add('refreshinterval', 'text', array('required' => false))
            ->add('average', 'text', array(
                'required' => false,
            ))
            ->add('follow', 'checkbox', array(
                'required' => false,
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('centerOnFirstPosition', 'checkbox', array(
                'required' => false,
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
            ->add('zoomToAccuracyOnFirstPosition', 'checkbox', array(
                'required' => false,
                'label_attr' => array(
                    'class' => 'labelCheck',
                ),
            ))
        ;
    }
}

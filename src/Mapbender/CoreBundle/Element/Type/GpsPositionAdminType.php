<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class GpsPositionAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
            'average'     => 1,
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
            ))
            ->add('autoStart', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.element.autostart',
            ))
            ->add('target',  'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('icon', new IconClassType(), array('required' => false))
            ->add('average', 'text', array(
                'required' => false,
            ))
            ->add('refreshinterval', 'Symfony\Component\Form\Extension\Core\Type\IntegerType', array(
                'required' => false,
                'constraints' => array(
                    new Range(array(
                        'min' => 0,
                    )),
                ),
                'attr' => array(
                    'min' => 0,
                    'step' => 200,
                ),
            ))
            ->add('follow', 'checkbox', array(
                'required' => false,
            ))
            ->add('centerOnFirstPosition', 'checkbox', array(
                'required' => false,
            ))
            ->add('zoomToAccuracyOnFirstPosition', 'checkbox', array(
                'required' => false,
            ))
        ;
    }
}

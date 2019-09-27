<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverviewAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'text', array('required' => false))
            ->add('layerset', 'app_layerset', array(
                'application' => $options['application'],
                'required' => true,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application'   => $options['application'],
                'required' => false,
            ))
            ->add('anchor', "choice", array(
                'required' => true,
                "choices"  => array(
                    'left-top'     => 'left-top',
                    'left-bottom'  => 'left-bottom',
                    'right-top'    => 'right-top',
                    'right-bottom' => 'right-bottom',
                ),
                'choices_as_values' => true,
            ))
            ->add('maximized', 'checkbox', array(
                'required' => false,
                'label' => 'mb.manager.admin.overview.maximize',
            ))
            ->add('fixed', 'checkbox', array(
                'required' => false,
                'label' => 'mb.manager.admin.overview.fix',
            ))
            ->add('width', 'text', array('required' => true))
            ->add('height', 'text', array('required' => true))
        ;
    }

}

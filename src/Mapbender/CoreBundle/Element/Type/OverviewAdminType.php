<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\Overview;
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
        // @todo: add missing field labels
        $builder
            ->add('layerset', 'Mapbender\CoreBundle\Element\Type\LayersetAdminType', array(
                'application' => $options['application'],
                'required' => true,
            ))
            ->add('fixed', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.manager.admin.overview.fix',
            ))
            ->add('visibility', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'label' => 'mb.manager.visibility',
                'choices' => array(
                    'mb.core.overview.admin.visibility.closed_initially' => Overview::VISIBILITY_CLOSED_INITIALLY,
                    'mb.core.overview.admin.visibility.open_initially' => Overview::VISIBILITY_OPEN_INITIALLY,
                    'mb.core.overview.admin.visibility.open_permanent' => Overview::VISIBILITY_OPEN_PERMANENT,
                ),
            ))
            // @todo: this should be a positive integer
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            // @todo: this should be a positive integer
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\TextType')
        ;
    }
}

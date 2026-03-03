<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Element\Overview;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class OverviewAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('layerset', 'Mapbender\CoreBundle\Element\Type\LayersetAdminType', array(
                'application' => $options['application'],
                'required' => true,
                'label' => 'mb.core.overview.admin.layerset',
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
            ->add('width', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.manager.popup_width',
                'attr' => array(
                    'placeholder' => 'mb.manager.automatic',
                ),
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ))
            ->add('height', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.manager.popup_height',
                'attr' => array(
                    'placeholder' => 'mb.manager.automatic',
                ),
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ))
        ;
    }
}

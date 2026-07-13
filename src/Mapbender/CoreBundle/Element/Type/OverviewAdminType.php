<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $resolver->setDefaults([
            'application' => null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('layerset', LayersetAdminType::class, [
                'application' => $options['application'],
                'required' => true,
                'label' => 'mb.core.overview.admin.layerset',
            ])
            ->add('fixed', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.manager.admin.overview.fix',
            ])
            ->add('visibility', ChoiceType::class, [
                'required' => true,
                'label' => 'mb.manager.visibility',
                'choices' => [
                    'mb.core.overview.admin.visibility.closed_initially' => Overview::VISIBILITY_CLOSED_INITIALLY,
                    'mb.core.overview.admin.visibility.open_initially' => Overview::VISIBILITY_OPEN_INITIALLY,
                    'mb.core.overview.admin.visibility.open_permanent' => Overview::VISIBILITY_OPEN_PERMANENT,
                ],
            ])
            ->add('width', TextType::class, [
                'label' => 'mb.manager.popup_width',
                'attr' => [
                    'placeholder' => 'mb.manager.automatic',
                ],
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ])
            ->add('height', TextType::class, [
                'label' => 'mb.manager.popup_height',
                'attr' => [
                    'placeholder' => 'mb.manager.automatic',
                ],
                'constraints' => [
                    new Type('numeric'),
                    new PositiveOrZero(),
                ],
            ])
        ;
    }
}

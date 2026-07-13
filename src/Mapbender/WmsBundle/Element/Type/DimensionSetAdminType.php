<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dimensions' => [],
            'title' => null,
            'group' => null,
            'dimension' => null,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'mb.core.dimensionset.admin.title',
                'attr' => [
                    'data-name' => 'title',
                ],
            ])
            ->add('group', DimensionSetDimensionChoiceType::class, [
                'required' => true,
                'label' => 'mb.core.dimensionset.admin.group',
                'multiple' => true,
                'mapped' => true,
                'dimensions' => $options['dimensions'],
                'attr' => [
                    'data-name' => 'group',
                ],
            ])
            ->add('extent', TextType::class, [
                'required' => true,
                'label' => 'mb.core.dimensionset.admin.extent',
                'attr' => [
                    'data-extent-range' => 'extent-range-hidden',
                ],
            ])
        ;
    }
}

<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LayerThemeType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('useTheme', CheckboxType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'title' => 'mb.core.admin.layertree.label.theme.useTheme',
                ],
            ])
            ->add('opened', CheckboxType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'title' => 'mb.core.admin.layertree.label.theme.opened',
                ],
            ])
        ;
    }
}

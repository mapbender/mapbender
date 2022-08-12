<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LayerThemeType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('useTheme', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'title' => 'mb.core.admin.layertree.label.theme.useTheme',
                ),
            ))
            ->add('opened', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => false,
                'attr' => array(
                    'title' => 'mb.core.admin.layertree.label.theme.opened',
                ),
            ))
        ;
    }
}

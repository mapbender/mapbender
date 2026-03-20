<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Type;

class ScaleBarAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('maxWidth', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                    'label' => 'mb.core.scalebar.admin.maxwidth',
                    'constraints' => [
                        new Type('numeric'),
                        new PositiveOrZero(),
                    ],
                )
            )
            ->add('units', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'label' => 'mb.core.scalebar.admin.units',
                'choices' => array(
                    'Kilometer' => 'km',
                    'Mile' => 'ml',
                ),
            ))
        ;
    }

}

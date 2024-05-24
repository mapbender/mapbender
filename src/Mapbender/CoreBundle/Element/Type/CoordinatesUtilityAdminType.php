<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoordinatesUtilityAdminType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'application' => null
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('srsList', 'Mapbender\CoreBundle\Element\Type\CoordinatesUtilitySrsListType', array(
                'required' => false,
                'label' => 'mb.core.coordinatesutility.admin.srslist',
            ))
            ->add('zoomlevel', 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
                [
                    'label' => "mb.core.coordinatesutility.admin.zoomlevel",
                    'empty_data'  => 0,
                    'attr' => [
                        'type' => 'number',
                        'min' => 0
                    ]
                ])
            ->add('addMapSrsList', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', [
                'label' => 'mb.core.coordinatesutility.backend.addMapSrsList',
                'required' => false,
            ])
        ;
    }
}

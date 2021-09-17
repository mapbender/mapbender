<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'dimensions' => array(),
            'title' => null,
            'group' => null,
            'dimension' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'attr' => array(
                    'data-name' => 'title',
                ),
            ))
            ->add('group', 'Mapbender\WmsBundle\Element\Type\DimensionSetDimensionChoiceType', array(
                'required' => true,
                'multiple' => true,
                'mapped' => true,
                'dimensions' => $options['dimensions'],
                'attr' => array(
                    'data-name' => 'group',
                ),
            ))
            ->add('extent', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
            ))
        ;
    }
}

<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\WmsBundle\Element\Type\Transformer\DimensionSetTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class DimensionSetAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'dimensionset';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
            ->add('title', 'text', array(
                'required' => true,
                'attr' => array(
                    'data-name' => 'title',
                ),
            ))
            ->add('group', new DimensionSetDimensionChoiceType(), array(
                'required' => true,
                'multiple' => true,
                'mapped' => true,
                'dimensions' => $options['dimensions'],
                'attr' => array(
                    'data-name' => 'group',
                ),
            ))
            ->add('dimension', 'hidden', array(
                'required' => true,
                'mapped' => true,
                'attr' => array(
                    'data-name' => 'dimension',
                ),
            ))
        ;
        $builder->addModelTransformer(new DimensionSetTransformer($options['dimensions']));
    }
}

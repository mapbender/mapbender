<?php

namespace Mapbender\WmsBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class DimensionSetAdminType extends AbstractType implements DataTransformerInterface
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
            'error_bubbling' => false,
            'allow_extra_fields' => true,
            'title' => null,
            'group' => null,
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
                'required' => false,
                'multiple' => true,
                'mapped' => true,
                'dimensionInsts' => $options['dimensions'],
                'attr' => array(
                    'data-name' => 'group',
                ),
            ))
            ->add('dimension', new DimensionSetExtentType(), array(
                'required' => true,
                'mapped' => true,
                'attr' => array(
                    'data-name' => 'dimension',
                ),
            ))
        ;
        $builder->addModelTransformer($this);
    }

    public function transform($value)
    {
        if ($value && !empty($value['dimension'])) {
            if (!is_string($value['dimension'])) {
                $value['dimension'] = json_encode($value['dimension']);
            } else {
                $value['dimension'] = null;
            }
        }
        return $value;
    }

    public function reverseTransform($value)
    {
        if ($value && !empty($value['dimension'])) {
            if (is_string($value['dimension'])) {
                $value['dimension'] = json_decode($value['dimension']);
            } else {
                $value['dimension'] = null;
            }
        }
        return $value;
    }
}

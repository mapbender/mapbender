<?php

namespace Mapbender\WmsBundle\Element\Type;

use Mapbender\WmsBundle\Component\DimensionInst;
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
            'dimensions' => array()
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dimChioces = array();
        $dimJson = array();
        foreach ($options['dimensions'] as $instId => $dim) {
            /** @var DimensionInst $dim */
            $dimChioces[$instId] = $instId . "-" . $dim->getName() . "-" . $dim->getType();
            $dimJson[$instId] = $dim->getConfiguration();
        }
        // die(var_export($dimJson, true));
        $builder
            ->add('title', 'text', array(
                'required' => true,
            ))
            ->add('group', 'choice', array(
                'required' => true,
                'choices' => $dimChioces,
                'multiple' => true,
                'attr' => array(
                    'data-dimension-group' => json_encode($dimJson),
                ),
            ))
            ->add('extent', 'text', array(
                'required' => false,
                'mapped' => false,
                'property_path' => '[display]',
                'read_only' => true,
                'attr' => array(
                    'data-name' => 'display',
                ),
            ))
            ->add('dimension', new DimensionInstElmType(), array(
                'required' => false,
            ))
        ;
    }

}

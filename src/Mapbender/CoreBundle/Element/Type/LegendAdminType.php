<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class LegendAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'legend';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
                ->add('elementType', 'choice',
                      array(
                    'required' => true,
                    'choices' => array(
                        "dialog" => "dialog",
                        "blockelement" => "blockelement")))
                ->add('autoOpen', 'checkbox', array('required' => false))
                ->add('displayType', 'choice',
                      array(
                    'required' => true,
                    'choices' => array(
                        "list" => "list",
                        "accordion" => "accordion"
                        )))
                ->add('target', 'target_element',
                      array(
                    'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
                ->add('hideEmptyLayers', 'checkbox', array('required' => false))
                
                ->add('noLegend', 'text', array('required' => false))
                ->add('generateGetLegendGraphicUrl', 'checkbox',
                      array('required' => false))
                ->add('showWmsTitle', 'checkbox', array('required' => false))
                ->add('showLayerTitle', 'checkbox', array('required' => false))
                ->add('showGroupedLayerTitle', 'checkbox',
                      array('required' => false))
        ;
    }

}
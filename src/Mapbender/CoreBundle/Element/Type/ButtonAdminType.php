<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class ButtonAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'button';
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
                ->add('icon', 'choice',
                      array(
                    'required' => false,
                    "choices" => array(
                        "" => "None",
                        "iconAbout" => "About",
                        "iconInfo" => "Feature info",
                        "iconGps" => "GPS",
                        "iconLegend" => "Legend",
                        "iconPrint" => "Print",
                        "iconSearch" => "Search",
                        "iconLayertree" => "Layer tree",
                        "iconWms" => "WMS"
                        )))
                ->add('label', 'checkbox', array('required' => false))
                ->add('target', 'target_element',
                      array(
                    'element_class' => '%Element%',
                    'application' => $options['application'],
                    'property_path' => '[target]',
                    'required' => false))
                ->add('click', 'text', array('required' => false))
                ->add('group', 'text', array('required' => false))
                ->add('action', 'text', array('required' => false))
                ->add('deactivate', 'text', array('required' => false));
    }

}
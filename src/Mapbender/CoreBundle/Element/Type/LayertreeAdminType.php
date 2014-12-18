<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * 
 */
class LayertreeAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'layertree';
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
        $menuComponents = array(
            "layerremove" => "Remove layer",
            "opacity" => "Opacity",
            "zoomtolayer" => "Zoom to layer",
            "metadata" => "Metadata",
//            "legend" => "Legend",
//            "kmlexport" => "KML export",
            "dimension" => "Dimension",
        );
        $builder->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('type', 'choice',
                array(
                'required' => true,
                'choices' => array(
                    'dialog' => 'Dialog',
                    'element' => 'Element')))
            ->add('autoOpen', 'checkbox',
                array(
                'required' => false))
            ->add('displaytype', 'choice',
                array(
                'required' => true,
                'choices' => array('tree' => 'Tree')))
            ->add('titlemaxlength', 'text', array('required' => true))
            ->add('showBaseSource', 'checkbox',
                array(
                'required' => false))
            ->add('showHeader', 'checkbox',
                array(
                'required' => false))
            ->add('menu', 'choice',
                array(
                'required' => false,
                "multiple" => true,
                'choices' => $menuComponents));
    }

}
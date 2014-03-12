<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 *
 */
class GpsPositionAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'gpsposition';
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
            ->add('label', 'checkbox', array('required' => false))
            ->add('autoStart', 'checkbox', array('required' => false))
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('icon', 'choice',
                array(
                'required' => false,
                "choices" => array(
                    "" => "None",
                    "iconAbout" => "About",
                    "iconAreaRuler" => "Area ruler",
                    "iconInfoActive" => "Feature info",
                    "iconGps" => "GPS",
                    "iconLegend" => "Legend",
                    "iconPrint" => "Print",
                    "iconSearch" => "Search",
                    "iconLayertree" => "Layer tree",
                    "iconWms" => "WMS",
                    "iconHelp" => "Help",
                    "iconWmcEditor" => "WMC Editor",
                    "iconWmcLoader" => "WMC Loader",
                    "iconPoi" => "POI",
                    "iconImageExport" => "Image Export",
                    "iconSketch" => "Sketch"
            )))
            ->add('action', 'text', array('required' => false))
            ->add('refreshinterval', 'text', array('required' => false));
    }

}

<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IconClassType extends AbstractType
{
    public function getName()
    {
        return 'iconclass';
    }

    public function getParent()
    {
        return 'choice';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'empty_value' => 'Choose an option',
            'empty_data' => 'XXX',
            'choices' => array(
                'iconAbout' => 'About',
                'iconAreaRuler' => 'Area ruler',
                'iconInfoActive' => 'Feature info',
                'iconGps' => 'GPS',
                'iconLegend' => 'Legend',
                'iconPrint' => 'Print',
                'iconSearch' => 'Search',
                'iconLayertree' => 'Layer tree',
                'iconWms' => 'WMS',
                'iconHelp' => 'Help',
                'iconWmcEditor' => 'WMC Editor',
                'iconWmcLoader' => 'WMC Loader',
                'iconCoordinates' => 'Coordinates',
                'iconGpsTarget' => 'Gps Target',
                'iconPoi' => 'POI',
                'iconImageExport' => 'Image Export',
                'iconSketch' => 'Sketch'),
        ));
    }
}

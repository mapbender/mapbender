<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    public function configureOptions(OptionsResolver $resolver)
    {
        $icons = array(

            // Mapbender Icons
            'icon-layer-tree'   => 'Layer tree',
            'icon-feature-info' => 'Feature Info',
            'icon-area-ruler'   => 'Area ruler',
            'icon-polygon'      => 'Polygon',
            'icon-line-ruler'   => 'Line ruler',
            'icon-image-export' => 'Image Export',
            'icon-legend'       => 'Legend',
            'icon-about'        => 'About',

            // FontAwesome
            'iconAbout'         => 'About (FontAwesome)',
            'iconAreaRuler'     => 'Area ruler (FontAwesome)',
            'iconInfoActive'    => 'Feature info (FontAwesome)',
            'iconGps'           => 'GPS (FontAwesome)',
            'iconLegend'        => 'Legend (FontAwesome)',
            'iconPrint'         => 'Print (FontAwesome)',
            'iconSearch'        => 'Search (FontAwesome)',
            'iconLayertree'     => 'Layer tree (FontAwesome)',
            'iconWms'           => 'WMS (FontAwesome)',
            'iconHelp'          => 'Help (FontAwesome)',
            'iconWmcEditor'     => 'WMC Editor (FontAwesome)',
            'iconWmcLoader'     => 'WMC Loader (FontAwesome)',
            'iconCoordinates'   => 'Coordinates (FontAwesome)',
            'iconGpsTarget'     => 'Gps Target (FontAwesome)',
            'iconPoi'           => 'POI (FontAwesome)',
            'iconImageExport'   => 'Image Export (FontAwesome)',
            'iconSketch'        => 'Sketch (FontAwesome)');

        asort($icons);

        $resolver->setDefaults(array(
            'empty_value' => 'Choose an option',
            'empty_data' => '',
            'choices' => $icons,
        ));
    }
}

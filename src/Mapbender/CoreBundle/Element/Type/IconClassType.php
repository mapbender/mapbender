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
        $icons = array(

            // New Icons
            'icon-layer-tree'   => 'Layer tree (New)',
            'icon-feature-info' => 'Feature Info (New)',
            'icon-area-ruler'   => 'Area ruler (New)',
            'icon-polygone'     => 'Polygone (New)',
            'icon-line-ruler'   => 'Line ruler (New)',
            'icon-image-export' => 'Image Export (New)',
            'icon-legend'       => 'Legend (New)',
            'icon-about'        => 'About (New)',

            // Deprecated
            'iconAbout'         => 'About',
            'iconAreaRuler'     => 'Area ruler',
            'iconInfoActive'    => 'Feature info',
            'iconGps'           => 'GPS',
            'iconLegend'        => 'Legend',
            'iconPrint'         => 'Print',
            'iconSearch'        => 'Search',
            'iconLayertree'     => 'Layer tree',
            'iconWms'           => 'WMS',
            'iconHelp'          => 'Help',
            'iconWmcEditor'     => 'WMC Editor',
            'iconWmcLoader'     => 'WMC Loader',
            'iconCoordinates'   => 'Coordinates',
            'iconGpsTarget'     => 'Gps Target',
            'iconPoi'           => 'POI',
            'iconImageExport'   => 'Image Export',
            'iconSketch'        => 'Sketch');

        asort($icons);

        $resolver->setDefaults(array(
            'empty_value' => 'Choose an option',
            'empty_data' => '',
            'choices' => $icons,
        ));
    }
}

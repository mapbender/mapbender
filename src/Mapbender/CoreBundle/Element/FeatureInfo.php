<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Featureinfo element
 *
 * This element will provide feature info for most layer types
 *
 * @author Christian Wygoda
 */
class FeatureInfo extends Element
{
    static public function getClassTitle()
    {
        return "Feature info";
    }

    static public function getClassDescription()
    {
        return "Feature info tool for most layer types";
    }

    static public function getClassTags()
    {
        return array('button', 'featureinfo');
    }
    
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Feature Info',
            "target" => null);
    }

    public function getWidgetName()
    {
        return 'mapbender.mbFeatureInfo';
    }

    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.button.js',
                'mapbender.element.featureInfo.js'
            ),
            'css' => array(
                'mapbender.element.featureInfo.css'
            )
        );
    }

    public function render()
    {
        $configuration = parent::getConfiguration();
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:button.html.twig', array(
                'id' => $this->getId(),
                'configuration' => $configuration,
                'title' => $this->getTitle()));
    }
}


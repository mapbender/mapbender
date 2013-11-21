<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//use Symfony\Component\HttpFoundation\Response;
//use PDO;

class POI extends Element
{
    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return 'POI';
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return 'Create a POI for sharing';
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array('POI');
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'body' => 'Please take a look at this POI',
        );
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                'mapbender.element.poi.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js'),
            'css' => array());
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPOI';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render('MapbenderCoreBundle:Element:poi.html.twig', array(
            'id' => $this->getId(),
            'title' => $this->getTitle(),
            'configuration' => $this->getConfiguration())
        );
    }
}
